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

        $token = is_string($_POST['_csrf'] ?? null) ? (string)$_POST['_csrf'] : null;
        if (!Csrf::validate($token)) {
            Flash::set('error', 'Solicitud inválida. Intenta nuevamente.');
            header('Location: /facturas/crear', true, 303);
            exit;
        }

        $fecha = trim((string)($_POST['fecha'] ?? date('Y-m-d')));
        if (!$this->isValidDate($fecha)) {
            $fecha = date('Y-m-d');
        }

        $terceroId = (int)($_POST['tercero_id'] ?? 0);
        if ($terceroId <= 0 || !Tercero::find($terceroId)) {
            Flash::set('error', 'Tercero inválido o no existe.');
            header('Location: /facturas/crear', true, 303);
            exit;
        }

        $lines = $this->readLines();
        if (count($lines) < 1) {
            Flash::set('error', 'Debe ingresar al menos 1 línea válida.');
            header('Location: /facturas/crear', true, 303);
            exit;
        }

        // Calcular totales
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

        $header = [
            'fecha' => $fecha,
            'tercero_id' => $terceroId,
            // Sin flujo de emisión por ahora: dejamos borrador para futuro "emitir"
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
            header('Location: /facturas/' . $facturaId, true, 303);
            exit;
        } catch (PDOException) {
            Flash::set('error', 'No se pudo crear la factura.');
            header('Location: /facturas/crear', true, 303);
            exit;
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
            // Bloquear factura
            $st = $pdo->prepare("SELECT * FROM facturas WHERE id = :id FOR UPDATE");
            $st->execute([':id' => $id]);
            $factura = $st->fetch();

            if (!$factura) {
                throw new \RuntimeException('Factura no encontrada.');
            }

            if ((string)($factura['estado'] ?? '') !== 'borrador') {
                throw new \RuntimeException('Solo se puede emitir una factura en estado borrador.');
            }

            // Detalles
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
                if ($cantidad <= 0) {
                    throw new \RuntimeException('Cantidad inválida en una línea.');
                }

                // Bloquear producto
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

            // Cambiar estado a emitida
            $upF = $pdo->prepare("UPDATE facturas SET estado = 'emitida', updated_at = NOW() WHERE id = :id");
            $upF->execute([':id' => $id]);

            Auditoria::log(
                $usuarioId,
                'emitir',
                'facturas',
                (int)$id,
                ['estado_anterior' => 'borrador', 'estado_nuevo' => 'emitida']
            );

            $pdo->commit();
            Flash::set('success', 'Factura emitida correctamente.');
            $this->redirect('/facturas/' . $id);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
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
                    if ($productoId === null) {
                        continue;
                    }

                    $cantidad = (float)($linea['cantidad'] ?? 0);
                    if ($cantidad <= 0) {
                        throw new \RuntimeException('Cantidad inválida en una línea.');
                    }

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

            Auditoria::log(
                $usuarioId,
                'anular',
                'facturas',
                (int)$id,
                ['estado_anterior' => $estado, 'estado_nuevo' => 'anulada']
            );

            $pdo->commit();
            Flash::set('success', 'Factura anulada correctamente.');
            $this->redirect('/facturas/' . $id);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
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
        $pids = $_POST['line_producto_id'] ?? [];
        $descs = $_POST['line_descripcion'] ?? [];
        $qtys = $_POST['line_cantidad'] ?? [];
        $prices = $_POST['line_precio_unitario'] ?? [];

        if (!is_array($pids) || !is_array($descs) || !is_array($qtys) || !is_array($prices)) {
            return [];
        }

        $lines = [];
        $max = max(count($pids), count($descs), count($qtys), count($prices));
        $max = min($max, 20); // límite razonable

        for ($i = 0; $i < $max; $i++) {
            $pidRaw = $pids[$i] ?? '';
            $desc = trim((string)($descs[$i] ?? ''));
            $qtyRaw = trim((string)($qtys[$i] ?? ''));
            $priceRaw = trim((string)($prices[$i] ?? ''));

            $pid = (int)$pidRaw;
            $productoId = $pid > 0 ? $pid : null;

            // Normalizar decimales
            $qty = $this->normalizeDecimal($qtyRaw);
            $price = $this->normalizeDecimal($priceRaw);

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

            // Validar producto si se envía
            if ($productoId !== null) {
                $prod = Producto::find($productoId);
                if (!$prod) {
                    continue; // línea inválida si apunta a producto inexistente
                }
                if ($desc === '') {
                    $desc = (string)($prod['nombre'] ?? '');
                }
            }

            if ($desc === '' || mb_strlen($desc) > 255) {
                continue;
            }

            $lines[] = [
                'producto_id' => $productoId,
                'descripcion' => $desc,
                'cantidad' => $qty,
                'precio_unitario' => $price,
                'subtotal_linea' => '0.00', // se calcula luego
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