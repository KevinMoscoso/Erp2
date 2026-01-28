<?php
declare(strict_types=1);

namespace Erp2\Controller;

use Erp2\Core\Auth;
use Erp2\Core\Database;
use Erp2\Core\Flash;
use Erp2\Core\View;
use Throwable;

/**
 * Seguridad (RBAC) - Usuarios (solo lectura)
 */
final class UsuariosController
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
            $cache[$table] = [];
            return [];
        }
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::can('usuarios.ver');

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
            $cols = $this->columns('usuarios');

            $where = [];
            $params = [];

            if ($q !== '') {
                $or = [];

                if (ctype_digit($q) && (int)$q > 0) {
                    $or[] = 'id = :id';
                    $params[':id'] = (int)$q;
                }

                $like = '%' . $q . '%';
                if (isset($cols['email'])) {
                    $or[] = 'email LIKE :q1';
                    $params[':q1'] = $like;
                }
                if (isset($cols['nombre'])) {
                    $or[] = 'nombre LIKE :q2';
                    $params[':q2'] = $like;
                }
                if (isset($cols['nombres'])) {
                    $or[] = 'nombres LIKE :q3';
                    $params[':q3'] = $like;
                }

                if (!empty($or)) {
                    $where[] = '(' . implode(' OR ', $or) . ')';
                }
            }

            $sql = 'SELECT * FROM usuarios';
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

            // Nunca exponer hash
            foreach ($items as &$u) {
                if (is_array($u)) {
                    unset($u['password_hash']);
                }
            }
            unset($u);
        } catch (Throwable $e) {
            error_log('[usuarios.index] ' . $e->getMessage());
            Flash::set('error', 'No se pudo cargar el listado de usuarios.');
            $items = [];
        }

        View::render('usuarios/index', [
            'title' => 'Usuarios',
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
        Auth::can('usuarios.ver');

        $user = null;
        $roles = [];

        try {
            $pdo = Database::pdo();

            $st = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id LIMIT 1');
            $st->execute([':id' => $id]);
            $user = $st->fetch();

            if (!is_array($user)) {
                http_response_code(404);
                echo '404 Not Found';
                return;
            }

            unset($user['password_hash']);

            // Roles asignados al usuario
            $sqlRoles = "
                SELECT r.*
                FROM roles r
                INNER JOIN usuario_roles ur ON ur.rol_id = r.id
                WHERE ur.usuario_id = :uid
                ORDER BY r.id ASC
            ";
            $sr = $pdo->prepare($sqlRoles);
            $sr->execute([':uid' => $id]);
            $roles = $sr->fetchAll();
        } catch (Throwable $e) {
            error_log('[usuarios.show] ' . $e->getMessage() . ' id=' . $id);
            Flash::set('error', 'No se pudo cargar el detalle del usuario.');
            $user = is_array($user) ? $user : ['id' => $id];
            $roles = [];
        }

        View::render('usuarios/show', [
            'title' => 'Detalle usuario',
            'user' => $user,
            'roles' => $roles,
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
        ]);
    }
}