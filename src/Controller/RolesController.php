<?php
declare(strict_types=1);

namespace Erp2\Controller;

use Erp2\Core\Auth;
use Erp2\Core\Database;
use Erp2\Core\Flash;
use Erp2\Core\View;
use Throwable;

/**
 * Seguridad (RBAC) - Roles (solo lectura)
 */
final class RolesController
{
    /** @return array<string,true> */
    private function columns(string $table): array
    {
        static $cache = [];
        if (isset($cache[$table]) && is_array($cache[$table])) {
            return $cache[$table];
        }

        try {
            $pdo = Database::pdo();
            $st = $pdo->prepare(
                "SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t"
            );
            $st->execute([':t' => $table]);

            $cols = [];
            while ($row = $st->fetch()) {
                $c = $row['column_name'] ?? null;
                if (is_string($c) && $c !== '') {
                    $cols[$c] = true;
                }
            }

            $cache[$table] = $cols;
            return $cols;
        } catch (Throwable) {
            // Si no hay permisos para information_schema, devolvemos vacío.
            $cache[$table] = [];
            return [];
        }
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::can('roles.ver');

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
            $cols = $this->columns('roles');

            $where = [];
            $params = [];

            if ($q !== '') {
                $or = [];
                if (ctype_digit($q) && (int)$q > 0) {
                    $or[] = 'id = :id';
                    $params[':id'] = (int)$q;
                }

                $like = '%' . $q . '%';
                if (isset($cols['codigo'])) {
                    $or[] = 'codigo LIKE :q1';
                    $params[':q1'] = $like;
                }
                if (isset($cols['nombre'])) {
                    $or[] = 'nombre LIKE :q2';
                    $params[':q2'] = $like;
                }

                if (!empty($or)) {
                    $where[] = '(' . implode(' OR ', $or) . ')';
                }
            }

            $sql = 'SELECT * FROM roles';
            if (!empty($where)) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }
            $sql .= ' ORDER BY id ASC';
            $sql .= " LIMIT {$limit}";

            if (empty($params)) {
                $st = $pdo->query($sql);
                $items = $st ? $st->fetchAll() : [];
            } else {
                $st = $pdo->prepare($sql);
                $st->execute($params);
                $items = $st->fetchAll();
            }
        } catch (Throwable $e) {
            error_log('[roles.index] ' . $e->getMessage());
            Flash::set('error', 'No se pudo cargar el listado de roles.');
            $items = [];
        }

        View::render('roles/index', [
            'title' => 'Roles',
            'q' => $q,
            'limit' => $limit,
            'items' => $items,
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
        ]);
    }

    public function show(int $id): void
    {
        Auth::requireLogin();
        Auth::can('roles.ver');

        $role = null;
        $perms = [];

        try {
            $pdo = Database::pdo();

            $st = $pdo->prepare('SELECT * FROM roles WHERE id = :id LIMIT 1');
            $st->execute([':id' => $id]);
            $role = $st->fetch();

            if (!is_array($role)) {
                http_response_code(404);
                echo '404 Not Found';
                return;
            }

            // Permisos asignados al rol
            $sqlPerms = "
                SELECT p.*
                FROM permisos p
                INNER JOIN rol_permisos rp ON rp.permiso_id = p.id
                WHERE rp.rol_id = :rid
                ORDER BY p.codigo ASC
            ";
            $stp = $pdo->prepare($sqlPerms);
            $stp->execute([':rid' => $id]);
            $perms = $stp->fetchAll();
        } catch (Throwable $e) {
            error_log('[roles.show] ' . $e->getMessage() . ' id=' . $id);
            Flash::set('error', 'No se pudo cargar el detalle del rol.');
            $role = is_array($role) ? $role : ['id' => $id];
            $perms = [];
        }

        View::render('roles/show', [
            'title' => 'Detalle rol',
            'role' => $role,
            'permisos' => $perms,
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
        ]);
    }
}