<?php
declare(strict_types=1);

namespace Erp2\Controller;

use Erp2\Core\Auth;
use Erp2\Core\Csrf;
use Erp2\Core\Database;
use Erp2\Core\Flash;
use Erp2\Core\View;
use Erp2\Model\Auditoria;
use Erp2\Model\InventarioMovimiento;
use PDO;
use Throwable;

final class InventarioController
{
    public function index(): void
    {
        Auth::requireLogin();
        Auth::can('inventario.ver');

        $q = trim((string)($_GET['q'] ?? ''));

        $pdo = Database::pdo();

        $sql = "
            SELECT id, tipo, referencia, nombre, stock_actual, estado
            FROM productos
            WHERE 1=1
        ";
        $params = [];

        // ✅ FIX HY093 preventivo: NO repetir :q en el mismo statement
        if ($q !== '') {
            $sql .= " AND (referencia LIKE :q1 OR nombre LIKE :q2)";
            $params[':q1'] = '%' . $q . '%';
            $params[':q2'] = '%' . $q . '%';
        }

        $sql .= " ORDER BY id DESC";

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $productos = $st->fetchAll();

        View::render('inventario/index', [
            'q' => $q,
            'productos' => $productos,
            'flash_error' => Flash::get('error'),
            'flash_success' => Flash::get('success'),
        ]);
    }

    public function show(int $id): void
    {
        Auth::requireLogin();
        Auth::can('inventario.ver');

        $pdo = Database::pdo();

        $st = $pdo->prepare("SELECT * FROM productos WHERE id = :id");
        $st->execute([':id' => $id]);
        $producto = $st->fetch();

        if (!$producto) {
            http_response_code(404);
            echo "Producto no encontrado";
            return;
        }

        $movs = InventarioMovimiento::listByProducto($id);

        View::render('inventario/show', [
            'producto' => $producto,
            'movimientos' => $movs,
            'csrf' => Csrf::token(),
            'flash_error' => Flash::get('error'),
            'flash_success' => Flash::get('success'),
        ]);
    }

    public function ajustar(int $id): void
    {
        Auth::requireLogin();
        Auth::can('inventario.ajustar');

        // Normalizar OLD para repoblar en caso de error
        $accion = (string)($_POST['accion'] ?? '');
        $cantidadRaw = trim((string)($_POST['cantidad'] ?? ''));
        $nota = trim((string)($_POST['nota'] ?? ''));

        $old = [
            'accion' => $accion,
            'cantidad' => $cantidadRaw,
            'nota' => $nota,
        ];

        // CSRF primero
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::setData('old', $old);
            Flash::set('error', 'Solicitud inválida (CSRF).');
            $this->redirect303('/inventario/' . $id);
        }

        // Validaciones por campo (UX consistente)
        $errors = [];

        if (!in_array($accion, ['sumar', 'restar'], true)) {
            $errors['accion'] = 'Acción inválida.';
        }

        $cantidadStr = $this->normalizeDecimal($cantidadRaw);
        $cantidad = (float)($cantidadStr ?? 0.0);
        if ($cantidadStr === null || $cantidad <= 0) {
            $errors['cantidad'] = 'La cantidad debe ser mayor a 0.';
        }

        if (mb_strlen($nota) > 255) {
            $errors['nota'] = 'La nota no debe exceder 255 caracteres.';
        }

        if (!empty($errors)) {
            Flash::setData('old', $old);
            Flash::setData('errors', $errors);
            Flash::set('error', 'Revisa los campos marcados e intenta nuevamente.');
            $this->redirect303('/inventario/' . $id);
        }

        $user = Auth::user();
        $usuarioId = (int)($user['id'] ?? 0);

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            // Bloqueo del producto para evitar carreras
            $st = $pdo->prepare("SELECT id, tipo, stock_actual, estado FROM productos WHERE id = :id FOR UPDATE");
            $st->execute([':id' => $id]);
            $p = $st->fetch();

            if (!$p) {
                throw new \RuntimeException('Producto no existe.');
            }

            $tipoProducto = (string)($p['tipo'] ?? '');
            if ($tipoProducto !== 'producto') {
                throw new \RuntimeException('No se puede ajustar stock de un servicio.');
            }

            $estado = (int)($p['estado'] ?? 1);
            if ($estado === 0) {
                throw new \RuntimeException('No se puede ajustar un producto inactivo.');
            }

            $stockAnterior = (float)($p['stock_actual'] ?? 0);

            $nuevo = $stockAnterior;
            $movTipo = 'entrada';

            if ($accion === 'sumar') {
                $nuevo = $stockAnterior + $cantidad;
                $movTipo = 'entrada';
            } else {
                $nuevo = $stockAnterior - $cantidad;
                $movTipo = 'salida';
                if ($nuevo < 0) {
                    throw new \RuntimeException('Stock insuficiente para el ajuste.');
                }
            }

            $up = $pdo->prepare("UPDATE productos SET stock_actual = :s, updated_at = NOW() WHERE id = :id");
            $up->execute([':s' => $nuevo, ':id' => $id]);

            InventarioMovimiento::insert(
                $pdo,
                (int)$id,
                $movTipo,
                $cantidad,
                $stockAnterior,
                $nuevo,
                $usuarioId ?: null,
                $nota !== '' ? $nota : 'Ajuste manual',
                'ajuste',
                null
            );

            Auditoria::log(
                $usuarioId,
                'ajustar',
                'inventario',
                (int)$id,
                [
                    'accion' => $accion,
                    'cantidad' => $cantidad,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $nuevo,
                ]
            );

            $pdo->commit();
            Flash::set('success', 'Stock ajustado correctamente.');
            $this->redirect303('/inventario/' . $id);

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[inventario.ajustar] ' . $e->getMessage() . ' producto_id=' . $id . ' user=' . $usuarioId);

            Flash::setData('old', $old);
            Flash::set('error', 'No se pudo ajustar inventario: ' . $e->getMessage());
            $this->redirect303('/inventario/' . $id);
        }
    }

    private function normalizeDecimal(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;
        $value = str_replace(',', '.', $value);
        if (!preg_match('/^\d+(\.\d+)?$/', $value)) return null;
        return $value;
    }

    private function redirect303(string $to): void
    {
        header('Location: ' . $to, true, 303);
        exit;
    }
}