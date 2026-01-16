<?php
declare(strict_types=1);

namespace Erp2\Controller;

use Erp2\Core\Auth;
use Erp2\Core\Csrf;
use Erp2\Core\Flash;
use Erp2\Core\View;
use Erp2\Model\Auditoria;
use Erp2\Model\Factura;
use Erp2\Model\FacturaDetalle;
use Erp2\Model\Producto;
use Erp2\Model\Tercero;
use PDOException;
use Erp2\Core\Database;
use Erp2\Model\InventarioMovimiento;
use Throwable;

final class FacturasController
{
    private function userId(): int
    {
        $u = Auth::user();
        return (int)($u['id'] ?? 0);
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::can('facturas.ver');

        $q = trim((string)($_GET['q'] ?? ''));
        $items = Factura::search($q);

        View::render('facturas/index', [
            'title' =>'Facturas',
            'q' => $q,
            'items' => $items,
            'csrf' => Csrf::token(),
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
        ]);
    }

    public function createForm(): void
    {
        Auth::requireLogin();
        Auth::can('facturas.crear');

        // Catálogos simples
        $terceros = Tercero::search('');
        $productos = Producto::search('');

        View::render('facturas/form', [
            'title' => 'Crear factura',
            'action' => '/facturas/crear',
            'csrf' => Csrf::token(),
            'error' => Flash::get('error'),
            'terceros' => $terceros,
            'productos' => $productos,
            'today' => date('Y-m-d'),
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        Auth::can('facturas.crear');

        $errors = [];

        $token = is_string($_POST['_csrf'] ?? null) ? (string)$_POST['_csrf'] : null;
        if (!Csrf::validate($token)) {
            Flash::set('error', 'Solicitud inválida. Intenta nuevamente.');
            $this->redirect('/facturas/crear');
        }

        $fecha = trim((string)($_POST['fecha'] ?? date('Y-m-d')));
        if (!$this->isValidDate($fecha)) {
            $errors['fecha'] = 'Fecha inválida.';
            $fecha = date('Y-m-d');
        }

        $terceroId = (int)($_POST['tercero_id'] ?? 0);
        if ($terceroId <= 0 || !Tercero::find($terceroId)) {
            $errors['tercero_id'] = 'Tercero inválido o no existe.';
        }

        $lines = $this->readLines();
        if (count($lines) < 1) {
            $errors['lines'] = 'Debe ingresar al menos 1 línea válida.';
        }

        // old para repoblar (HITO 9A)
        $old = [
            'fecha' => $fecha,
            'tercero_id' => (string)$terceroId,
            'line_producto_id' => $_POST['line_producto_id'] ?? [],
            'line_descripcion' => $_POST['line_descripcion'] ?? [],
            'line_cantidad' => $_POST['line_cantidad'] ?? [],
            'line_precio_unitario' => $_POST['line_precio_unitario'] ?? [],
        ];

        if (!empty($errors)) {
            Flash::setData('old', $old);
            Flash::setData('errors', $errors);
            Flash::set('error', 'Revisa los campos marcados e intenta nuevamente.');
            $this->redirect('/facturas/crear');
        }

        // Calcular totales desde líneas (fuente de verdad)
        $subtotal = 0.0;
        foreach ($lines as &$ln) {
            $qty = (float)$ln['cantidad'];
            $price = (float)$ln['precio_unitario'];
            $lineSub = round($qty * $price, 2);
            $ln['subtotal_linea'] = $this->formatDecimal($lineSub);
            $subtotal += $lineSub;
        }
        unset($ln);

        $subtotal = round($subtotal, 2);
        $total = $subtotal;

        // ✅ HITO 9B-C: si viene total/subtotal por POST, validar coherencia (epsilon 0.01)
        $postedTotal = null;
        if (isset($_POST['total'])) {
            $pt = $this->normalizeDecimal((string)$_POST['total']);
            if ($pt !== null) $postedTotal = (float)$pt;
        }
        if ($postedTotal !== null && abs($postedTotal - $total) > 0.01) {
            Flash::setData('old', $old);
            Flash::setData('errors', ['total' => 'Se detectó inconsistencia de totales. Revisa las líneas.']);
            Flash::set('error', 'Se detectó inconsistencia de totales / revisa las líneas.');
            $this->redirect('/facturas/crear');
        }

        $header = [
            'fecha' => $fecha,
            'tercero_id' => $terceroId,
            'estado' => 'borrador',
            'subtotal' => $this->formatDecimal($subtotal),
            'total' => $this->formatDecimal($total),
        ];

        try {
            $facturaId = Factura::createWithDetails($header, $lines);

            Auditoria::log($this->userId(), 'crear', 'facturas', $facturaId, [
                'tercero_id' => $terceroId,
                'fecha' => $fecha,
                'lines' => count($lines),
                'subtotal' => $header['subtotal'],
                'total' => $header['total'],
            ]);

            Flash::set('success', 'Factura creada correctamente.');
            $this->redirect('/facturas/' . $facturaId);
        } catch (PDOException $e) {
            error_log('[facturas.create] error: ' . $e->getMessage() . ' user=' . $this->userId());
            Flash::setData('old', $old);
            Flash::set('error', 'No se pudo crear la factura.');
            $this->redirect('/facturas/crear');
        }
    }

    public function show(int $id): void
    {
        Auth::requireLogin();
        Auth::can('facturas.ver');

        $factura = Factura::find($id);
        if (!$factura) {
            http_response_code(404);
            echo "404 Not Found";
            return;
        }

        $detalles = FacturaDetalle::listByFactura($id);

        View::render('facturas/show', [
            'title' => 'Detalle factura',
            'factura' => $factura,
            'detalles' => $detalles,
            'csrf' => Csrf::token(),
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
        ]);
    }

    public function emitir(int $id): void
    {
        Auth::requireLogin();
        Auth::can('facturas.emitir');

        $token = is_string($_POST['_csrf'] ?? null) ? (string)$_POST['_csrf'] : null;
        if (!Csrf::validate($token)) {
            Flash::set('error', 'Solicitud inválida (CSRF).');
            $this->redirect('/facturas/' . $id);
        }

        $usuarioId = $this->userId();
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            // Lock factura
            $st = $pdo->prepare("SELECT * FROM facturas WHERE id = :id FOR UPDATE");
            $st->execute([':id' => $id]);
            $factura = $st->fetch();

            if (!$factura) {
                throw new \RuntimeException('Factura no encontrada.');
            }

            if ((string)($factura['estado'] ?? '') !== 'borrador') {
                throw new \RuntimeException('Solo se puede emitir una factura en estado borrador.');
            }

            // ✅ Recalcular subtotal_linea en DB y total desde DB (fuente de verdad)
            $pdo->prepare("UPDATE factura_detalles SET subtotal_linea = ROUND(cantidad * precio_unitario, 2) WHERE factura_id = :fid")
                ->execute([':fid' => $id]);

            $stSum = $pdo->prepare("SELECT COALESCE(SUM(subtotal_linea), 0) FROM factura_detalles WHERE factura_id = :fid");
            $stSum->execute([':fid' => $id]);
            $totalCalc = round((float)$stSum->fetchColumn(), 2);

            if ($totalCalc <= 0) {
                throw new \RuntimeException('Total inválido: la factura debe tener líneas válidas con total mayor a 0.');
            }

            // Detalles para stock
            $stD = $pdo->prepare("
                SELECT id, factura_id, producto_id, descripcion, cantidad, precio_unitario, subtotal_linea
                FROM factura_detalles
                WHERE factura_id = :fid
                ORDER BY id ASC
            ");
            $stD->execute([':fid' => $id]);
            $detalles = $stD->fetchAll();

            if (empty($detalles)) {
                throw new \RuntimeException('La factura debe tener al menos una línea.');
            }

            foreach ($detalles as $linea) {
                $productoId = $linea['producto_id'] ?? null;
                if ($productoId === null) {
                    continue; // línea libre sin producto
                }

                $cantidad = (float)($linea['cantidad'] ?? 0);
                $precio = (float)($linea['precio_unitario'] ?? 0);

                if ($cantidad <= 0) {
                    throw new \RuntimeException('Cantidad inválida en una línea.');
                }
                if ($precio < 0) {
                    throw new \RuntimeException('Precio inválido en una línea.');
                }

                // Lock producto
                $stP = $pdo->prepare("SELECT id, tipo, stock_actual, estado FROM productos WHERE id = :pid FOR UPDATE");
                $stP->execute([':pid' => (int)$productoId]);
                $p = $stP->fetch();

                if (!$p) {
                    throw new \RuntimeException('Producto no existe (id ' . (int)$productoId . ').');
                }

                // Servicios no afectan stock
                if ((string)($p['tipo'] ?? '') !== 'producto') {
                    continue;
                }

                if ((int)($p['estado'] ?? 1) === 0) {
                    throw new \RuntimeException('Producto inactivo (id ' . (int)$productoId . ').');
                }

                $stockAnterior = (float)($p['stock_actual'] ?? 0);
                $stockNuevo = $stockAnterior - $cantidad;

                if ($stockNuevo < 0) {
                    throw new \RuntimeException('Stock insuficiente para emitir (producto ' . (int)$productoId . ').');
                }

                $up = $pdo->prepare("UPDATE productos SET stock_actual = :s, updated_at = NOW() WHERE id = :pid");
                $up->execute([':s' => $stockNuevo, ':pid' => (int)$productoId]);

                InventarioMovimiento::insert(
                    $pdo,
                    (int)$productoId,
                    'salida',
                    $cantidad,
                    $stockAnterior,
                    $stockNuevo,
                    $usuarioId ?: null,
                    'Salida por emisión de factura',
                    'factura',
                    (int)$id
                );
            }

            // ✅ Emitir + fijar totales calculados
            $upF = $pdo->prepare("
                UPDATE facturas
                SET subtotal = :t, total = :t, estado = 'emitida', updated_at = NOW()
                WHERE id = :id
            ");
            $val = $this->formatDecimal($totalCalc);
            $upF->execute([':t' => $val, ':id' => $id]);

            Auditoria::log(
                $usuarioId,
                'emitir',
                'facturas',
                (int)$id,
                ['estado_anterior' => 'borrador', 'estado_nuevo' => 'emitida', 'total' => $this->formatDecimal($totalCalc)]
            );

            $pdo->commit();
            Flash::set('success', 'Factura emitida correctamente.');
            $this->redirect('/facturas/' . $id);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[facturas.emitir] error: ' . $e->getMessage() . ' id=' . $id . ' user=' . $usuarioId);
            Flash::set('error', 'No se pudo emitir: ' . $e->getMessage());
            $this->redirect('/facturas/' . $id);
        }
    }

    public function anular(int $id): void
    {
        Auth::requireLogin();
        Auth::can('facturas.anular');

        $token = is_string($_POST['_csrf'] ?? null) ? (string)$_POST['_csrf'] : null;
        if (!Csrf::validate($token)) {
            Flash::set('error', 'Solicitud inválida (CSRF).');
            $this->redirect('/facturas/' . $id);
        }

        $usuarioId = $this->userId();
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $st = $pdo->prepare("SELECT * FROM facturas WHERE id = :id FOR UPDATE");
            $st->execute([':id' => $id]);
            $factura = $st->fetch();

            if (!$factura) {
                throw new \RuntimeException('Factura no encontrada.');
            }

            $estado = (string)($factura['estado'] ?? '');

            if ($estado === 'anulada') {
                $pdo->commit();
                Flash::set('success', 'La factura ya estaba anulada.');
                $this->redirect('/facturas/' . $id);
            }

            // ✅ HITO 9B-B: NO permitir anular si existen pagos aplicados
            $stPagado = $pdo->prepare("SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE tipo_ref = 'factura' AND ref_id = :rid");
            $stPagado->execute([':rid' => $id]);
            $pagado = (float)$stPagado->fetchColumn();

            if ($pagado > 0.00001) {
                Auditoria::log($usuarioId, 'anular_bloqueado', 'facturas', (int)$id, [
                    'motivo' => 'tiene_pagos',
                    'estado' => $estado,
                    'pagado' => $this->formatDecimal($pagado),
                ]);
                $pdo->rollBack();
                Flash::set('error', 'No se puede anular una factura con pagos aplicados.');
                $this->redirect('/facturas/' . $id);
            }

            // Si estaba emitida, revertir stock
            if ($estado === 'emitida') {
                $stD = $pdo->prepare("
                    SELECT id, producto_id, cantidad
                    FROM factura_detalles
                    WHERE factura_id = :fid
                    ORDER BY id ASC
                ");
                $stD->execute([':fid' => $id]);
                $detalles = $stD->fetchAll();

                foreach ($detalles as $linea) {
                    $productoId = $linea['producto_id'] ?? null;
                    if ($productoId === null) continue;

                    $cantidad = (float)($linea['cantidad'] ?? 0);
                    if ($cantidad <= 0) throw new \RuntimeException('Cantidad inválida en una línea.');

                    $stP = $pdo->prepare("SELECT id, tipo, stock_actual, estado FROM productos WHERE id = :pid FOR UPDATE");
                    $stP->execute([':pid' => (int)$productoId]);
                    $p = $stP->fetch();

                    if (!$p) throw new \RuntimeException('Producto no existe (id ' . (int)$productoId . ').');

                    // Servicios no afectan stock
                    if ((string)($p['tipo'] ?? '') !== 'producto') continue;

                    $stockAnterior = (float)($p['stock_actual'] ?? 0);
                    $stockNuevo = $stockAnterior + $cantidad;

                    $up = $pdo->prepare("UPDATE productos SET stock_actual = :s, updated_at = NOW() WHERE id = :pid");
                    $up->execute([':s' => $stockNuevo, ':pid' => (int)$productoId]);

                    InventarioMovimiento::insert(
                        $pdo,
                        (int)$productoId,
                        'entrada',
                        $cantidad,
                        $stockAnterior,
                        $stockNuevo,
                        $usuarioId ?: null,
                        'Entrada por anulación (reversa de factura)',
                        'factura',
                        (int)$id
                    );
                }
            }

            $upF = $pdo->prepare("UPDATE facturas SET estado = 'anulada', updated_at = NOW() WHERE id = :id");
            $upF->execute([':id' => $id]);

            Auditoria::log($usuarioId, 'anular', 'facturas', (int)$id, [
                'estado_anterior' => $estado,
                'estado_nuevo' => 'anulada',
            ]);

            $pdo->commit();
            Flash::set('success', 'Factura anulada correctamente.');
            $this->redirect('/facturas/' . $id);

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[facturas.anular] error: ' . $e->getMessage() . ' id=' . $id . ' user=' . $usuarioId);
            Flash::set('error', 'No se pudo anular: ' . $e->getMessage());
            $this->redirect('/facturas/' . $id);
        }
    }

    /**
    * Lee líneas desde arrays POST: producto_id[], descripcion[], cantidad[], precio_unitario[]
    * @return array<int, array{producto_id:?int,descripcion:string,cantidad:string,precio_unitario:string,subtotal_linea:string}>
    */
    private function readLines(): array
    {
        $pids   = $_POST['line_producto_id'] ?? [];
        $descs  = $_POST['line_descripcion'] ?? [];
        $qtys   = $_POST['line_cantidad'] ?? [];
        $prices = $_POST['line_precio_unitario'] ?? [];

        if (!is_array($pids) || !is_array($descs) || !is_array($qtys) || !is_array($prices)) {
            return [];
        }

        // Reindexar para evitar huecos
        $pids   = array_values($pids);
        $descs  = array_values($descs);
        $qtys   = array_values($qtys);
        $prices = array_values($prices);

        $lines = [];
        $max = max(count($pids), count($descs), count($qtys), count($prices));
        $max = min($max, 20);

        for ($i = 0; $i < $max; $i++) {
            $pidRaw   = $pids[$i] ?? '';
            $desc     = trim((string)($descs[$i] ?? ''));
            $qtyRaw   = trim((string)($qtys[$i] ?? ''));
            $priceRaw = trim((string)($prices[$i] ?? ''));

            $pid = (int)$pidRaw;
            $productoId = $pid > 0 ? $pid : null;

            $rowTouched = ($productoId !== null || $desc !== '' || $qtyRaw !== '' || $priceRaw !== '');
            if (!$rowTouched) {
                continue;
            }

            // ✅ FIX: si la fila está tocada pero qty viene vacío, asumir 1 (evita “solo guarda 1ra línea”)
            if ($qtyRaw === '') {
                $qtyRaw = '1';
            }

            $qty = $this->normalizeDecimal($qtyRaw);
            $price = $this->normalizeDecimal($priceRaw);

            // Validar/Resolver producto o servicio si se selecciona ID
            if ($productoId !== null) {
                $prod = Producto::find($productoId);

                // ✅ FIX: fallback directo a DB por si Producto::find filtra servicios o estado
                if (!$prod) {
                    $pdo = Database::pdo();
                    $st = $pdo->prepare("SELECT id, nombre, precio_venta, tipo, estado FROM productos WHERE id = :id LIMIT 1");
                    $st->execute([':id' => $productoId]);
                    $row = $st->fetch();
                    $prod = is_array($row) ? $row : null;
                }

                if (!$prod) {
                    // Si ni así existe, descartar (manipulación POST o inconsistencia)
                    continue;
                }

                if ($desc === '') {
                    $desc = (string)($prod['nombre'] ?? '');
                }

                // Si no vino precio, tomar precio_venta si está disponible
                if ($price === null) {
                    $pdef = $this->normalizeDecimal((string)($prod['precio_venta'] ?? ''));
                    if ($pdef !== null) {
                        $price = $pdef;
                    }
                }
            }

            // Si sigue faltando qty o price, descartar
            if ($qty === null || $price === null) {
                continue;
            }

            $qtyF = (float)$qty;
            $priceF = (float)$price;

            if ($qtyF <= 0) {
                continue;
            }
            if ($priceF < 0) {
                continue;
            }

            // Servicios (producto_id null) requieren descripción
            if ($desc === '' || mb_strlen($desc) > 255) {
                continue;
            }

            $lines[] = [
                'producto_id' => $productoId,     // null = servicio / línea libre
                'descripcion' => $desc,
                'cantidad' => $qty,
                'precio_unitario' => $price,
                'subtotal_linea' => '0.00',
            ];
        }

        return $lines;
    }

    private function normalizeDecimal(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $value = str_replace(',', '.', $value);
        if (!preg_match('/^\d+(\.\d+)?$/', $value)) {
            return null;
        }

        return $value;
    }

    private function formatDecimal(float $value): string
    {
        // Mantener punto decimal, 2 decimales
        return number_format($value, 2, '.', '');
    }

    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        $parts = explode('-', $date);
        return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
    }
    
    private function redirect(string $to): void
    {
        header('Location: ' . $to, true, 303);
        exit;
    }
}