<?php
declare(strict_types=1);

namespace Erp2\Controller;

use Erp2\Core\Auth;
use Erp2\Core\Csrf;
use Erp2\Core\Database;
use Erp2\Core\Flash;
use Erp2\Core\View;
use Erp2\Model\Auditoria;
use Erp2\Model\Compra;
use Erp2\Model\CompraDetalle;
use Erp2\Model\InventarioMovimiento;
use Erp2\Model\Producto;
use Erp2\Model\Tercero;
use Throwable;

final class ComprasController
{
    private function userId(): int
    {
        $u = Auth::user();
        return (int)($u['id'] ?? 0);
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::can('compras.ver');

        $q = trim((string)($_GET['q'] ?? ''));
        $items = Compra::search($q);

        View::render('compras/index', [
            'title' => 'Compras',
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
        Auth::can('compras.crear');

        // Proveedores: filtrar por tipo proveedor|ambos
        $allTerceros = Tercero::search('');
        $proveedores = [];
        foreach ($allTerceros as $t) {
            $tipo = (string)($t['tipo'] ?? '');
            if (in_array($tipo, ['proveedor', 'ambos'], true)) {
                $proveedores[] = $t;
            }
        }

        $productos = Producto::search('');

        View::render('compras/form', [
            'title' => 'Crear compra',
            'action' => '/compras/crear',
            'csrf' => Csrf::token(),
            'error' => Flash::get('error'),
            'proveedores' => $proveedores,
            'productos' => $productos,
            'today' => date('Y-m-d'),
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        Auth::can('compras.crear');

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('error', 'Solicitud inválida. Intenta nuevamente.');
            $this->redirect303('/compras/crear');
        }

        $fecha = trim((string)($_POST['fecha'] ?? date('Y-m-d')));
        if (!$this->isValidDate($fecha)) {
            $fecha = date('Y-m-d');
        }

        $terceroId = (int)($_POST['tercero_id'] ?? 0);
        $tercero = $terceroId > 0 ? Tercero::find($terceroId) : null;
        if (!$tercero) {
            Flash::set('error', 'Proveedor inválido o no existe.');
            $this->redirect303('/compras/crear');
        }
        $tipoTercero = (string)($tercero['tipo'] ?? '');
        if (!in_array($tipoTercero, ['proveedor', 'ambos'], true)) {
            Flash::set('error', 'El tercero seleccionado no es proveedor.');
            $this->redirect303('/compras/crear');
        }

        $lines = $this->readLines();
        if (count($lines) < 1) {
            Flash::set('error', 'Debe ingresar al menos 1 línea válida.');
            $this->redirect303('/compras/crear');
        }

        $subtotal = 0.0;
        foreach ($lines as &$ln) {
            $qty = (float)$ln['cantidad'];
            $cost = (float)$ln['costo_unitario'];
            $lineSub = round($qty * $cost, 2);
            $ln['subtotal_linea'] = $this->formatDecimal($lineSub);
            $subtotal += $lineSub;
        }
        unset($ln);

        $subtotal = round($subtotal, 2);
        $total = $subtotal;

        try {
            $compraId = Compra::createWithDetails(
                [
                    'fecha' => $fecha,
                    'tercero_id' => $terceroId,
                    'estado' => 'borrador',
                    'subtotal' => $this->formatDecimal($subtotal),
                    'total' => $this->formatDecimal($total),
                ],
                $lines
            );

            Auditoria::log($this->userId(), 'crear', 'compras', $compraId, [
                'tercero_id' => $terceroId,
                'fecha' => $fecha,
                'lines' => count($lines),
                'total' => $this->formatDecimal($total),
            ]);

            Flash::set('success', 'Compra creada correctamente.');
            $this->redirect303('/compras/' . $compraId);
        } catch (Throwable) {
            Flash::set('error', 'No se pudo crear la compra.');
            $this->redirect303('/compras/crear');
        }
    }

    public function show(int $id): void
    {
        Auth::requireLogin();
        Auth::can('compras.ver');

        $compra = Compra::find($id);
        if (!$compra) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $detalles = CompraDetalle::listByCompra($id);

        View::render('compras/show', [
            'title' => 'Detalle compra',
            'compra' => $compra,
            'detalles' => $detalles,
            'csrf' => Csrf::token(),
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
        ]);
    }

    public function emitir(int $id): void
    {
        Auth::requireLogin();
        Auth::can('compras.emitir');

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('error', 'Solicitud inválida (CSRF).');
            $this->redirect303('/compras/' . $id);
        }

        $usuarioId = $this->userId();
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            // Lock compra
            $stC = $pdo->prepare("SELECT * FROM compras WHERE id = :id FOR UPDATE");
            $stC->execute([':id' => $id]);
            $compra = $stC->fetch();
            if (!$compra) {
                throw new \RuntimeException('Compra no encontrada.');
            }
            $estado = (string)($compra['estado'] ?? '');
            if ($estado !== 'borrador') {
                throw new \RuntimeException('Solo se puede emitir una compra en estado borrador.');
            }

            // Detalles
            $stD = $pdo->prepare("
                SELECT id, producto_id, descripcion, cantidad, costo_unitario, subtotal_linea
                FROM compra_detalles
                WHERE compra_id = :cid
                ORDER BY id ASC
            ");
            $stD->execute([':cid' => $id]);
            $detalles = $stD->fetchAll();
            if (empty($detalles)) {
                throw new \RuntimeException('La compra debe tener al menos una línea.');
            }

            // Por cada línea con producto_id: entrada al stock si tipo=producto
            foreach ($detalles as $ln) {
                $productoId = $ln['producto_id'] ?? null;
                if ($productoId === null) {
                    continue;
                }

                $cantidad = (float)($ln['cantidad'] ?? 0);
                if ($cantidad <= 0) {
                    throw new \RuntimeException('Cantidad inválida en una línea.');
                }

                // Lock producto
                $stP = $pdo->prepare("SELECT id, tipo, stock_actual, estado FROM productos WHERE id = :pid FOR UPDATE");
                $stP->execute([':pid' => (int)$productoId]);
                $p = $stP->fetch();
                if (!$p) {
                    throw new \RuntimeException('Producto no existe (id ' . (int)$productoId . ').');
                }

                if ((string)($p['tipo'] ?? '') !== 'producto') {
                    continue; // servicios no afectan stock
                }
                if ((int)($p['estado'] ?? 1) === 0) {
                    throw new \RuntimeException('Producto inactivo (id ' . (int)$productoId . ').');
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
                    'Entrada por emisión de compra',
                    'compra',
                    (int)$id
                );
            }

            // Emitir compra
            $upC = $pdo->prepare("UPDATE compras SET estado = 'emitida', updated_at = NOW() WHERE id = :id");
            $upC->execute([':id' => $id]);

            Auditoria::log($usuarioId, 'emitir', 'compras', (int)$id, [
                'estado_anterior' => 'borrador',
                'estado_nuevo' => 'emitida',
            ]);

            $pdo->commit();
            Flash::set('success', 'Compra emitida correctamente.');
            $this->redirect303('/compras/' . $id);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Flash::set('error', 'No se pudo emitir: ' . $e->getMessage());
            $this->redirect303('/compras/' . $id);
        }
    }

    public function anular(int $id): void
    {
        Auth::requireLogin();
        Auth::can('compras.anular');

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('error', 'Solicitud inválida (CSRF).');
            $this->redirect303('/compras/' . $id);
        }

        $usuarioId = $this->userId();
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            // Lock compra
            $stC = $pdo->prepare("SELECT * FROM compras WHERE id = :id FOR UPDATE");
            $stC->execute([':id' => $id]);
            $compra = $stC->fetch();
            if (!$compra) {
                throw new \RuntimeException('Compra no encontrada.');
            }

            $estado = (string)($compra['estado'] ?? '');
            if ($estado === 'anulada') {
                $pdo->commit();
                Flash::set('success', 'La compra ya estaba anulada.');
                $this->redirect303('/compras/' . $id);
            }

            // Si emitida: reversar stock con SALIDA, evitando negativos
            if ($estado === 'emitida') {
                $stD = $pdo->prepare("
                    SELECT id, producto_id, cantidad
                    FROM compra_detalles
                    WHERE compra_id = :cid
                    ORDER BY id ASC
                ");
                $stD->execute([':cid' => $id]);
                $detalles = $stD->fetchAll();

                foreach ($detalles as $ln) {
                    $productoId = $ln['producto_id'] ?? null;
                    if ($productoId === null) {
                        continue;
                    }

                    $cantidad = (float)($ln['cantidad'] ?? 0);
                    if ($cantidad <= 0) {
                        throw new \RuntimeException('Cantidad inválida en una línea.');
                    }

                    $stP = $pdo->prepare("SELECT id, tipo, stock_actual, estado FROM productos WHERE id = :pid FOR UPDATE");
                    $stP->execute([':pid' => (int)$productoId]);
                    $p = $stP->fetch();
                    if (!$p) {
                        throw new \RuntimeException('Producto no existe (id ' . (int)$productoId . ').');
                    }

                    if ((string)($p['tipo'] ?? '') !== 'producto') {
                        continue; // servicios no afectan stock
                    }

                    $stockAnterior = (float)($p['stock_actual'] ?? 0);
                    $stockNuevo = $stockAnterior - $cantidad;

                    if ($stockNuevo < 0) {
                        throw new \RuntimeException('No hay stock suficiente para revertir la anulación (producto ' . (int)$productoId . ').');
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
                        'Salida por anulación (reversa de compra)',
                        'compra',
                        (int)$id
                    );
                }
            }

            // Anular compra (para borrador y emitida)
            $upC = $pdo->prepare("UPDATE compras SET estado = 'anulada', updated_at = NOW() WHERE id = :id");
            $upC->execute([':id' => $id]);

            Auditoria::log($usuarioId, 'anular', 'compras', (int)$id, [
                'estado_anterior' => $estado,
                'estado_nuevo' => 'anulada',
            ]);

            $pdo->commit();
            Flash::set('success', 'Compra anulada correctamente.');
            $this->redirect303('/compras/' . $id);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Flash::set('error', 'No se pudo anular: ' . $e->getMessage());
            $this->redirect303('/compras/' . $id);
        }
    }

    /**
    * Busca un ítem en `productos` SIN filtrar por tipo (incluye servicios).
    * @return array<string,mixed>|null
    */
    private function fetchProductoAny(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::pdo();
        $st = $pdo->prepare("SELECT id, nombre, tipo, estado FROM productos WHERE id = :id LIMIT 1");
        $st->execute([':id' => $id]);
        $row = $st->fetch();

        return is_array($row) ? $row : null;
    }

    /**
    * Lee líneas desde arrays POST:
    * line_producto_id[], line_descripcion[], line_cantidad[], line_costo_unitario[]
    *
    * Regla clave:
    * - Si el ítem es un SERVICIO y la cantidad llega vacía -> asumir 1.
    *
    * @return array<int, array{producto_id:?int,descripcion:string,cantidad:string,costo_unitario:string,subtotal_linea:string}>
    */
    private function readLines(): array
    {
        $pids  = $_POST['line_producto_id'] ?? [];
        $descs = $_POST['line_descripcion'] ?? [];
        $qtys  = $_POST['line_cantidad'] ?? [];
        $costs = $_POST['line_costo_unitario'] ?? [];

        if (!is_array($pids) || !is_array($descs) || !is_array($qtys) || !is_array($costs)) {
            return [];
        }

        $lines = [];
        $max = max(count($pids), count($descs), count($qtys), count($costs));
        $max = min($max, 20);

        for ($i = 0; $i < $max; $i++) {
            $pidRaw  = $pids[$i] ?? '';
            $desc    = trim((string)($descs[$i] ?? ''));
            $qtyRaw  = trim((string)($qtys[$i] ?? ''));
            $costRaw = trim((string)($costs[$i] ?? ''));

            $pid = (int)$pidRaw;
            $productoId = $pid > 0 ? $pid : null;

            // Costo unitario: obligatorio
            $cost = $this->normalizeDecimal($costRaw);
            if ($cost === null) {
                continue;
            }
            if ((float)$cost < 0) {
                continue;
            }

            $prod = null;
            $tipo = '';

            // Si viene producto_id, obtenerlo SIN filtrar tipo
            if ($productoId !== null) {
                $prod = $this->fetchProductoAny($productoId);

                // Si el id no existe, la línea solo puede ser "manual" si hay descripción
                if (!$prod) {
                    if ($desc === '') {
                        continue;
                    }
                    $productoId = null; // evita FK inválida
                } else {
                    $tipo = (string)($prod['tipo'] ?? '');

                    // Autocompletar descripción si está vacía
                    if ($desc === '') {
                        $desc = (string)($prod['nombre'] ?? '');
                    }
                }
            }

            // Cantidad:
            // - Si es servicio y viene vacía: asumir 1
            // - Caso general: validar decimal
            if ($productoId !== null && $tipo === 'servicio' && $qtyRaw === '') {
                $qty = '1';
            } else {
                $qty = $this->normalizeDecimal($qtyRaw);
                if ($qty === null) {
                    continue;
                }
            }

            $qtyF = (float)$qty;
            if ($qtyF <= 0) {
                continue;
            }

            // Validar descripción final
            if ($desc === '' || mb_strlen($desc) > 255) {
                continue;
            }

            $lines[] = [
                'producto_id' => $productoId,
                'descripcion' => $desc,
                'cantidad' => $qty,
                'costo_unitario' => $cost,
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
        return number_format($value, 2, '.', '');
    }

    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        $p = explode('-', $date);
        return checkdate((int)$p[1], (int)$p[2], (int)$p[0]);
    }

    private function redirect303(string $to): void
    {
        header('Location: ' . $to, true, 303);
        exit;
    }
}