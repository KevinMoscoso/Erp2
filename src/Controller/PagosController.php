<?php
declare(strict_types=1);

namespace Erp2\Controller;

use Erp2\Core\Auth;
use Erp2\Core\Csrf;
use Erp2\Core\Database;
use Erp2\Core\Flash;
use Erp2\Core\View;
use Erp2\Model\Auditoria;
use Erp2\Model\Pago;
use Erp2\Model\Tercero;
use Throwable;

final class PagosController
{
    private function userId(): int
    {
        $u = Auth::user();
        return (int)($u['id'] ?? 0);
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::can('pagos.ver');

        $q = trim((string)($_GET['q'] ?? ''));
        $tipoRef = trim((string)($_GET['tipo_ref'] ?? ''));
        $refId = (int)($_GET['ref_id'] ?? 0);

        if (!in_array($tipoRef, ['factura', 'compra'], true)) {
            $tipoRef = '';
        }

        $items = Pago::search($q, $tipoRef !== '' ? $tipoRef : null, $refId > 0 ? $refId : null);

        View::render('pagos/index', [
            'title' => 'Pagos',
            'q' => $q,
            'tipo_ref' => $tipoRef,
            'ref_id' => $refId,
            'items' => $items,
            'csrf' => Csrf::token(),
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
        ]);
    }

    public function createForm(): void
    {
        Auth::requireLogin();
        Auth::can('pagos.crear');

        $tipoRef = trim((string)($_GET['tipo_ref'] ?? ''));
        $refId = (int)($_GET['ref_id'] ?? 0);

        if (!in_array($tipoRef, ['factura', 'compra'], true)) {
            $tipoRef = 'factura';
        }
        if ($refId < 0) $refId = 0;

        $pdo = Database::pdo();

        // Listas rápidas para selects
        $facturas = $pdo->query("
            SELECT id, numero, estado, total, tercero_id
            FROM facturas
            ORDER BY id DESC
            LIMIT 200
        ")->fetchAll();

        $compras = $pdo->query("
            SELECT id, numero, estado, total, tercero_id
            FROM compras
            ORDER BY id DESC
            LIMIT 200
        ")->fetchAll();

        $terceros = Tercero::search('');

        // Info opcional si viene preseleccionado
        $selected = null;
        if ($refId > 0) {
            if ($tipoRef === 'factura') {
                $st = $pdo->prepare("SELECT id, numero, estado, total, tercero_id FROM facturas WHERE id = :id");
                $st->execute([':id' => $refId]);
                $selected = $st->fetch();
            } else {
                $st = $pdo->prepare("SELECT id, numero, estado, total, tercero_id FROM compras WHERE id = :id");
                $st->execute([':id' => $refId]);
                $selected = $st->fetch();
            }
        }

        $selectedInfo = null;
        if (is_array($selected)) {
            $total = (float)($selected['total'] ?? 0);
            $pagado = Pago::sumByRef($tipoRef, (int)($selected['id'] ?? 0));
            $saldo = round($total - $pagado, 2);

            $terceroId = (int)($selected['tercero_id'] ?? 0);
            $terceroNombre = '';
            foreach ($terceros as $t) {
                if ((int)($t['id'] ?? 0) === $terceroId) {
                    $terceroNombre = (string)($t['nombre_comercial'] ?? '');
                    break;
                }
            }

            $selectedInfo = [
                'numero' => (string)($selected['numero'] ?? ''),
                'estado' => (string)($selected['estado'] ?? ''),
                'total' => $this->formatDecimal($total),
                'pagado' => $this->formatDecimal($pagado),
                'saldo' => $this->formatDecimal(max(0.0, $saldo)),
                'tercero_id' => $terceroId,
                'tercero_nombre' => $terceroNombre,
            ];
        }

        View::render('pagos/form', [
            'title' => 'Registrar pago',
            'csrf' => Csrf::token(),
            'error' => Flash::get('error'),
            'today' => date('Y-m-d'),
            'tipo_ref' => $tipoRef,
            'ref_id' => $refId,
            'facturas' => $facturas,
            'compras' => $compras,
            'terceros' => $terceros,
            'selectedInfo' => $selectedInfo,
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        Auth::can('pagos.crear');

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('error', 'Solicitud inválida (CSRF).');
            $this->redirect303('/pagos/crear');
        }

        $tipoRef = trim((string)($_POST['tipo_ref'] ?? ''));
        if (!in_array($tipoRef, ['factura', 'compra'], true)) {
            Flash::set('error', 'Tipo de referencia inválido.');
            $this->redirect303('/pagos/crear');
        }

        $refId = 0;
        if ($tipoRef === 'factura') {
            $refId = (int)($_POST['ref_id_factura'] ?? 0);
        } else {
            $refId = (int)($_POST['ref_id_compra'] ?? 0);
        }
        if ($refId <= 0) {
            Flash::set('error', 'Debe seleccionar una factura/compra válida.');
            $this->redirect303('/pagos/crear?tipo_ref=' . $tipoRef);
        }

        $fecha = trim((string)($_POST['fecha'] ?? date('Y-m-d')));
        if (!$this->isValidDate($fecha)) {
            Flash::set('error', 'Fecha inválida.');
            $this->redirect303('/pagos/crear?tipo_ref=' . $tipoRef . '&ref_id=' . $refId);
        }

        $montoRaw = trim((string)($_POST['monto'] ?? ''));
        $montoStr = $this->normalizeDecimal($montoRaw);
        if ($montoStr === null) {
            Flash::set('error', 'Monto inválido.');
            $this->redirect303('/pagos/crear?tipo_ref=' . $tipoRef . '&ref_id=' . $refId);
        }
        $monto = (float)$montoStr;
        if ($monto <= 0) {
            Flash::set('error', 'El monto debe ser mayor a 0.');
            $this->redirect303('/pagos/crear?tipo_ref=' . $tipoRef . '&ref_id=' . $refId);
        }

        $metodo = trim((string)($_POST['metodo'] ?? ''));
        if (mb_strlen($metodo) > 30) {
            $metodo = mb_substr($metodo, 0, 30);
        }

        $referencia = trim((string)($_POST['referencia'] ?? ''));
        if ($referencia === '') $referencia = null;
        if (is_string($referencia) && mb_strlen($referencia) > 100) {
            $referencia = mb_substr($referencia, 0, 100);
        }

        $nota = trim((string)($_POST['nota'] ?? ''));
        if ($nota === '') $nota = null;
        if (is_string($nota) && mb_strlen($nota) > 255) {
            $nota = mb_substr($nota, 0, 255);
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            // 1) Lock cabecera FOR UPDATE (serializa pagos concurrentes)
            if ($tipoRef === 'factura') {
                $st = $pdo->prepare("SELECT id, numero, estado, total, tercero_id FROM facturas WHERE id = :id FOR UPDATE");
            } else {
                $st = $pdo->prepare("SELECT id, numero, estado, total, tercero_id FROM compras WHERE id = :id FOR UPDATE");
            }
            $st->execute([':id' => $refId]);
            $head = $st->fetch();
            if (!is_array($head)) {
                throw new \RuntimeException('La referencia no existe.');
            }

            $estado = (string)($head['estado'] ?? '');
            if ($estado === 'anulada') {
                throw new \RuntimeException('No se permiten pagos sobre una entidad anulada.');
            }

            $total = (float)($head['total'] ?? 0);
            if ($total <= 0) {
                // Mantener fail-closed: no permitir pagos sin total coherente
                throw new \RuntimeException('Total inválido en la referencia.');
            }

            $terceroId = (int)($head['tercero_id'] ?? 0);
            if ($terceroId <= 0) {
                throw new \RuntimeException('Tercero inválido en la referencia.');
            }

            // 2) Recalcular sum dentro de transacción (bajo lock de cabecera)
            $stSum = $pdo->prepare("
                SELECT COALESCE(SUM(monto), 0) AS pagado
                FROM pagos
                WHERE tipo_ref = :t AND ref_id = :rid
            ");
            $stSum->execute([':t' => $tipoRef, ':rid' => $refId]);
            $row = $stSum->fetch();
            $pagado = is_array($row) ? (float)($row['pagado'] ?? 0) : 0.0;

            $saldo = round($total - $pagado, 2);
            if ($saldo < 0) $saldo = 0.0;

            // Comparación tolerante a redondeos mínimos
            if ($monto > ($saldo + 0.00001)) {
                throw new \RuntimeException('El monto excede el saldo pendiente (' . $this->formatDecimal($saldo) . ').');
            }

            // 3) Insert pago (tercero_id se toma del documento, no del form)
            $pagoId = \Erp2\Model\Pago::createWithPdo($pdo, [
                'tipo_ref' => $tipoRef,
                'ref_id' => $refId,
                'tercero_id' => $terceroId,
                'fecha' => $fecha,
                'monto' => $this->formatDecimal($monto),
                'metodo' => $metodo,
                'referencia' => $referencia,
                'nota' => $nota,
                'usuario_id' => $this->userId() ?: null,
            ]);

            Auditoria::log($this->userId(), 'crear', 'pagos', $pagoId, [
                'tipo_ref' => $tipoRef,
                'ref_id' => $refId,
                'tercero_id' => $terceroId,
                'monto' => $this->formatDecimal($monto),
                'pagado_antes' => $this->formatDecimal($pagado),
                'saldo_antes' => $this->formatDecimal($saldo),
            ]);

            $pdo->commit();

            Flash::set('success', 'Pago registrado correctamente.');
            // Redirige a la entidad para ver sección pagos
            if ($tipoRef === 'factura') {
                $this->redirect303('/facturas/' . $refId);
            }
            $this->redirect303('/compras/' . $refId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            Flash::set('error', 'No se pudo registrar el pago: ' . $e->getMessage());
            $this->redirect303('/pagos/crear?tipo_ref=' . urlencode($tipoRef) . '&ref_id=' . $refId);
        }
    }

    public function delete(int $id): void
    {
        Auth::requireLogin();
        Auth::can('pagos.eliminar');

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('error', 'Solicitud inválida (CSRF).');
            $this->redirect303('/pagos');
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $pago = Pago::findByIdForUpdate($pdo, $id);
            if (!$pago) {
                throw new \RuntimeException('Pago no encontrado.');
            }

            $tipoRef = (string)($pago['tipo_ref'] ?? '');
            $refId = (int)($pago['ref_id'] ?? 0);

            if (!in_array($tipoRef, ['factura', 'compra'], true) || $refId <= 0) {
                throw new \RuntimeException('Referencia inválida en el pago.');
            }

            // Lock cabecera para evitar carreras con creación de pagos concurrentes
            if ($tipoRef === 'factura') {
                $st = $pdo->prepare("SELECT id, estado FROM facturas WHERE id = :id FOR UPDATE");
            } else {
                $st = $pdo->prepare("SELECT id, estado FROM compras WHERE id = :id FOR UPDATE");
            }
            $st->execute([':id' => $refId]);
            $head = $st->fetch();
            if (!is_array($head)) {
                throw new \RuntimeException('La referencia ya no existe.');
            }
            if ((string)($head['estado'] ?? '') === 'anulada') {
                // Se podría permitir igual, pero mantenemos comportamiento estricto
                throw new \RuntimeException('No se puede eliminar pago asociado a una entidad anulada.');
            }

            $ok = Pago::deleteByIdWithPdo($pdo, $id);
            if (!$ok) {
                throw new \RuntimeException('No se pudo eliminar el pago.');
            }

            Auditoria::log($this->userId(), 'eliminar', 'pagos', $id, [
                'tipo_ref' => $tipoRef,
                'ref_id' => $refId,
                'monto' => (string)($pago['monto'] ?? ''),
            ]);

            $pdo->commit();
            Flash::set('success', 'Pago eliminado.');
            if ($tipoRef === 'factura') $this->redirect303('/facturas/' . $refId);
            $this->redirect303('/compras/' . $refId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            Flash::set('error', 'No se pudo eliminar: ' . $e->getMessage());
            $this->redirect303('/pagos');
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

    private function formatDecimal(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
        $p = explode('-', $date);
        return checkdate((int)$p[1], (int)$p[2], (int)$p[0]);
    }

    private function redirect303(string $to): void
    {
        header('Location: ' . $to, true, 303);
        exit;
    }
}