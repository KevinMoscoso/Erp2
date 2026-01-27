<?php
declare(strict_types=1);

namespace Erp2\Controller;

use Erp2\Core\Auth;
use Erp2\Core\Database;
use Erp2\Core\Flash;
use Erp2\Core\View;
use Throwable;

/**
 * Auditoría (solo lectura).
 *
 * Objetivo: exponer un listado consultable de la tabla `auditoria`.
 * - GET con filtros; no hay cambios de negocio.
 * - Hardening: try/catch para evitar pantallas 500.
 */
final class AuditoriaController
{
    public function index(): void
    {
        Auth::requireLogin();
        Auth::can('auditoria.ver');

        $q = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($q) > 120) {
            $q = mb_substr($q, 0, 120);
        }

        $usuarioId = null;
        $uidRaw = trim((string)($_GET['usuario_id'] ?? ''));
        if ($uidRaw !== '') {
            $tmp = (int)$uidRaw;
            $usuarioId = $tmp > 0 ? $tmp : null;
        }

        $accion = trim((string)($_GET['accion'] ?? ''));
        if (mb_strlen($accion) > 60) {
            $accion = mb_substr($accion, 0, 60);
        }

        $entidad = trim((string)($_GET['entidad'] ?? ''));
        if (mb_strlen($entidad) > 60) {
            $entidad = mb_substr($entidad, 0, 60);
        }

        $desde = trim((string)($_GET['desde'] ?? ''));
        $hasta = trim((string)($_GET['hasta'] ?? ''));

        $limit = (int)($_GET['limit'] ?? 200);
        if ($limit <= 0) {
            $limit = 200;
        }
        if ($limit > 500) {
            $limit = 500;
        }

        $errors = [];
        if ($desde !== '' && !$this->isValidDate($desde)) {
            $errors[] = 'Fecha "desde" inválida. Use YYYY-MM-DD.';
            $desde = '';
        }
        if ($hasta !== '' && !$this->isValidDate($hasta)) {
            $errors[] = 'Fecha "hasta" inválida. Use YYYY-MM-DD.';
            $hasta = '';
        }

        if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
            $errors[] = 'Rango de fechas inválido: "desde" no puede ser mayor que "hasta".';
            // Auto-corregir invirtiendo el rango
            $tmp = $desde;
            $desde = $hasta;
            $hasta = $tmp;
        }

        $rows = [];

        try {
            $pdo = Database::pdo();

            $where = [];
            $params = [];

            if ($usuarioId !== null) {
                $where[] = 'a.usuario_id = :uid';
                $params[':uid'] = $usuarioId;
            }

            if ($accion !== '') {
                $where[] = 'a.accion = :accion';
                $params[':accion'] = $accion;
            }

            if ($entidad !== '') {
                $where[] = 'a.entidad = :entidad';
                $params[':entidad'] = $entidad;
            }

            if ($desde !== '') {
                $where[] = 'a.created_at >= :desde';
                $params[':desde'] = $desde . ' 00:00:00';
            }

            if ($hasta !== '') {
                $where[] = 'a.created_at <= :hasta';
                $params[':hasta'] = $hasta . ' 23:59:59';
            }

            if ($q !== '') {
                $like = '%' . $q . '%';
                // Placeholders únicos (evitar HY093)
                $where[] = '(
                    a.accion LIKE :q1
                    OR a.entidad LIKE :q2
                    OR u.email LIKE :q3
                    OR CAST(a.entidad_id AS CHAR) LIKE :q4
                    OR a.ip LIKE :q5
                )';
                $params[':q1'] = $like;
                $params[':q2'] = $like;
                $params[':q3'] = $like;
                $params[':q4'] = $like;
                $params[':q5'] = $like;
            }

            $sql = '
                SELECT
                    a.id,
                    a.created_at,
                    a.usuario_id,
                    u.email AS usuario_email,
                    a.accion,
                    a.entidad,
                    a.entidad_id,
                    a.ip,
                    a.user_agent,
                    a.detalle_json
                FROM auditoria a
                LEFT JOIN usuarios u ON u.id = a.usuario_id
            ';

            if (!empty($where)) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }

            $sql .= ' ORDER BY a.id DESC LIMIT ' . (int)$limit;

            $st = $pdo->prepare($sql);
            $st->execute($params);

            $rows = $st->fetchAll();
            if (!is_array($rows)) {
                $rows = [];
            }
        } catch (Throwable $e) {
            error_log('[auditoria.index] ' . $e->getMessage());
            Flash::set('error', 'No se pudo cargar la auditoría.');
            $rows = [];
        }

        View::render('auditoria/index', [
            'title' => 'Auditoría',
            'q' => $q,
            'usuario_id' => $usuarioId,
            'accion' => $accion,
            'entidad' => $entidad,
            'desde' => $desde,
            'hasta' => $hasta,
            'limit' => $limit,
            'flash_error' => Flash::get('error'),
            'flash_success' => Flash::get('success'),
            'errors' => $errors,
            'rows' => $rows,
        ]);
    }

    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        $p = explode('-', $date);
        return checkdate((int)$p[1], (int)$p[2], (int)$p[0]);
    }
}