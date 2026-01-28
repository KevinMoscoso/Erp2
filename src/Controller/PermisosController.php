<?php
declare(strict_types=1);

namespace Erp2\Controller;

use Erp2\Core\Auth;
use Erp2\Core\Database;
use Erp2\Core\Flash;
use Erp2\Core\View;
use Throwable;

/**
 * Seguridad (RBAC) - Permisos (solo lectura)
 */
final class PermisosController
{
    public function index(): void
    {
        Auth::requireLogin();
        Auth::can('permisos.ver');

        $q = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($q) > 120) {
            $q = mb_substr($q, 0, 120);
        }

        $limit = (int)($_GET['limit'] ?? 200);
        if ($limit <= 0) $limit = 200;
        if ($limit > 500) $limit = 500;

        $items = [];

        try {
            $pdo = Database::pdo();

            if ($q === '') {
                $sql = "SELECT * FROM permisos ORDER BY codigo ASC LIMIT {$limit}";
                $st = $pdo->query($sql);
                $items = $st ? $st->fetchAll() : [];
            } else {
                $like = '%' . $q . '%';

                // Nota: evitamos HY093 usando placeholders distintos.
                $sql = "
                    SELECT *
                    FROM permisos
                    WHERE codigo LIKE :q1
                       OR CAST(id AS CHAR) = :q2
                    ORDER BY codigo ASC
                    LIMIT {$limit}
                ";
                $st = $pdo->prepare($sql);
                $st->execute([
                    ':q1' => $like,
                    ':q2' => $q,
                ]);
                $items = $st->fetchAll();
            }
        } catch (Throwable $e) {
            error_log('[permisos.index] ' . $e->getMessage());
            Flash::set('error', 'No se pudo cargar el listado de permisos.');
            $items = [];
        }

        View::render('permisos/index', [
            'title' => 'Permisos',
            'q' => $q,
            'limit' => $limit,
            'items' => $items,
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
        ]);
    }
}