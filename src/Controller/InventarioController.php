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

        if ($q !== '') {
            $sql .= " AND (referencia LIKE :q OR nombre LIKE :q)";
            $params[':q'] = '%' . $q . '%';
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

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('error', 'Solicitud inválida (CSRF).');
            $this->redirect('/inventario/' . $id);
        }

        $accion = (string)($_POST['accion'] ?? ''); // sumar|restar
        $cantidadRaw = trim((string)($_POST['cantidad'] ?? ''));
        $nota = trim((string)($_POST['nota'] ?? ''));

        $cantidad = is_numeric($cantidadRaw) ? (float)$cantidadRaw : 0.0;
        if (!in_array($accion, ['sumar', 'restar'], true)) {
            Flash::set('error', 'Acción inválida.');
            $this->redirect('/inventario/' . $id);
        }
        if ($cantidad <= 0) {
            Flash::set('error', 'La cantidad debe ser mayor a 0.');
            $this->redirect('/inventario/' . $id);
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

            // Auditoría (mínima)
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
            $this->redirect('/inventario/' . $id);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Flash::set('error', 'No se pudo ajustar inventario: ' . $e->getMessage());
            $this->redirect('/inventario/' . $id);
        }
    }

    private function redirect(string $to): void
    {
        header('Location: ' . $to);
        exit;
    }
}