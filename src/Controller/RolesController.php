<?php
declare(strict_types=1);

namespace Erp2\Controller;

use Erp2\Core\Auth;
use Erp2\Core\Csrf;
use Erp2\Core\Database;
use Erp2\Core\Flash;
use Erp2\Core\View;
use Erp2\Model\Auditoria;
use Throwable;

final class RolesController
{
    /** @return array<string,bool> */
    private function columns(string $table): array
    {
        static $cache = [];
        if (isset($cache[$table]) && is_array($cache[$table])) {
            return $cache[$table];
        }

        $cols = [];

        // 1) Intento por information_schema (si hay permisos)
        try {
            $pdo = Database::pdo();
            $st = $pdo->prepare(
                "SELECT column_name
                 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name = :t"
            );
            $st->execute([':t' => $table]);

            while ($row = $st->fetch()) {
                $c = $row['column_name'] ?? null;
                if (is_string($c) && $c !== '') {
                    $cols[$c] = true;
                }
            }

            if (!empty($cols)) {
                $cache[$table] = $cols;
                return $cols;
            }
        } catch (Throwable) {
            // seguimos al fallback
        }

        // 2) Fallback por DESCRIBE (normalmente permitido)
        try {
            $pdo = Database::pdo();
            $st = $pdo->query('DESCRIBE ' . $table);
            if ($st) {
                while ($row = $st->fetch()) {
                    $f = $row['Field'] ?? null;
                    if (is_string($f) && $f !== '') {
                        $cols[$f] = true;
                    }
                }
            }
        } catch (Throwable) {
            // no-op
        }

        $cache[$table] = $cols;
        return $cols;
    }

    private function isAdminId1(): bool
    {
        $u = Auth::user();
        return (int)($u['id'] ?? 0) === 1;
    }

    private function adminOnlyOr403(): bool
    {
        Auth::requireLogin();
        if (!$this->isAdminId1()) {
            http_response_code(403);
            echo '403 Forbidden';
            return false;
        }
        return true;
    }

    private function redirect303(string $to): void
    {
        header('Location: ' . $to, true, 303);
        exit;
    }

    /** @return array<int,array<string,mixed>> */
    private function permisosAll(): array
    {
        $pdo = Database::pdo();
        $st = $pdo->query('SELECT * FROM permisos ORDER BY codigo ASC');
        return $st ? (array)$st->fetchAll() : [];
    }

    /** @return int[] */
    private function permisoIdsByRol(int $rolId): array
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT permiso_id FROM rol_permisos WHERE rol_id = :rid ORDER BY permiso_id ASC');
        $st->execute([':rid' => $rolId]);

        $ids = [];
        while ($r = $st->fetch()) {
            $pid = (int)($r['permiso_id'] ?? 0);
            if ($pid > 0) $ids[] = $pid;
        }
        return $ids;
    }

    public function index(): void
    {
        // ✅ ADMIN-ONLY (id=1) también para lectura
        if (!$this->adminOnlyOr403()) return;

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

            $where = [];
            $params = [];

            if ($q !== '') {
                $or = [];

                if (ctype_digit($q) && (int)$q > 0) {
                    $or[] = 'id = :id';
                    $params[':id'] = (int)$q;
                }

                // roles.nombre es el identificador principal
                $or[] = 'nombre LIKE :q1';
                $params[':q1'] = '%' . $q . '%';

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
                $items = $st ? (array)$st->fetchAll() : [];
            } else {
                $st = $pdo->prepare($sql);
                $st->execute($params);
                $items = (array)$st->fetchAll();
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
            'is_admin' => true, // aquí siempre será admin por el guard
        ]);
    }

    public function show(int $id): void
    {
        // ✅ ADMIN-ONLY (id=1) también para lectura
        if (!$this->adminOnlyOr403()) return;

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

            $sqlPerms = "
                SELECT p.*
                FROM permisos p
                INNER JOIN rol_permisos rp ON rp.permiso_id = p.id
                WHERE rp.rol_id = :rid
                ORDER BY p.codigo ASC
            ";
            $stp = $pdo->prepare($sqlPerms);
            $stp->execute([':rid' => $id]);
            $perms = (array)$stp->fetchAll();
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
            'is_admin' => true,
        ]);
    }

    // =========================
    // ✅ NUEVO (solo admin id=1)
    // =========================

    public function createForm(): void
    {
        if (!$this->adminOnlyOr403()) return;

        try {
            $permisos = $this->permisosAll();
        } catch (Throwable $e) {
            error_log('[roles.createForm] ' . $e->getMessage());
            Flash::set('error', 'No se pudo cargar permisos para el formulario.');
            $permisos = [];
        }

        View::render('roles/create', [
            'title' => 'Crear rol',
            'permisos_all' => $permisos,
            'csrf' => Csrf::token(),
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
        ]);
    }

    public function create(): void
    {
        if (!$this->adminOnlyOr403()) return;

        if (!Csrf::validate((string)($_POST['csrf'] ?? ''))) {
            Flash::set('error', 'CSRF inválido.');
            $this->redirect303('/roles/crear');
        }

        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));

        $permsRaw = $_POST['permisos'] ?? [];
        $permIds = [];
        if (is_array($permsRaw)) {
            foreach ($permsRaw as $pid) {
                $pid = (int)$pid;
                if ($pid > 0) $permIds[] = $pid;
            }
        }
        $permIds = array_values(array_unique($permIds));

        $errors = [];
        if ($nombre === '') {
            $errors['nombre'] = 'Nombre requerido.';
        }

        if (!empty($errors)) {
            Flash::setData('old', ['nombre' => $nombre, 'descripcion' => $descripcion, 'permisos' => $permIds]);
            Flash::setData('errors', $errors);
            Flash::set('error', 'Revisa los campos marcados.');
            $this->redirect303('/roles/crear');
        }

        try {
            $pdo = Database::pdo();

            // Unicidad por nombre (recomendado, para evitar duplicados)
            $stE = $pdo->prepare('SELECT id FROM roles WHERE nombre = :nombre LIMIT 1');
            $stE->execute([':nombre' => $nombre]);
            if ($stE->fetchColumn()) {
                Flash::setData('old', ['nombre' => $nombre, 'descripcion' => $descripcion, 'permisos' => $permIds]);
                Flash::setData('errors', ['nombre' => 'Ya existe un rol con ese nombre.']);
                Flash::set('error', 'No se pudo crear el rol.');
                $this->redirect303('/roles/crear');
            }

            $cols = $this->columns('roles');
            $createdCol = isset($cols['created_at']) ? 'created_at' : (isset($cols['creted_at']) ? 'creted_at' : null);

            $pdo->beginTransaction();

            // Insert compatible con tu schema: nombre, descripcion, (created_at si existe)
            $sqlCols = ['nombre', 'descripcion'];
            $sqlVals = [':nombre', ':descripcion'];
            $params = [':nombre' => $nombre, ':descripcion' => $descripcion];

            if ($createdCol !== null) {
                $sqlCols[] = $createdCol;
                $sqlVals[] = 'NOW()';
            }

            $sql = 'INSERT INTO roles (' . implode(',', $sqlCols) . ') VALUES (' . implode(',', $sqlVals) . ')';
            $st = $pdo->prepare($sql);
            $st->execute($params);

            $rolId = (int)$pdo->lastInsertId();

            // Sync permisos (delete+insert)
            $del = $pdo->prepare('DELETE FROM rol_permisos WHERE rol_id = :rid');
            $del->execute([':rid' => $rolId]);

            if (!empty($permIds)) {
                $ins = $pdo->prepare('INSERT INTO rol_permisos (rol_id, permiso_id) VALUES (:rid, :pid)');
                foreach ($permIds as $pid) {
                    $ins->execute([':rid' => $rolId, ':pid' => $pid]);
                }
            }

            $pdo->commit();

            try {
                Auditoria::log('seguridad.rol.crear', [
                    'admin_id' => (int)(Auth::user()['id'] ?? 0),
                    'rol_id' => $rolId,
                    'nombre' => $nombre,
                    'permisos' => $permIds,
                ]);
            } catch (Throwable) {}

            Flash::set('success', 'Rol creado correctamente.');
            $this->redirect303('/roles/' . $rolId);
        } catch (Throwable $e) {
            try {
                $pdo = Database::pdo();
                if ($pdo->inTransaction()) $pdo->rollBack();
            } catch (Throwable) {}

            error_log('[roles.create] ' . $e->getMessage());
            Flash::setData('old', ['nombre' => $nombre, 'descripcion' => $descripcion, 'permisos' => $permIds]);
            Flash::set('error', 'No se pudo crear el rol.');
            $this->redirect303('/roles/crear');
        }
    }

    public function editForm(int $id): void
    {
        if (!$this->adminOnlyOr403()) return;

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

            $permisosAll = $this->permisosAll();
            $assigned = $this->permisoIdsByRol($id);

            View::render('roles/edit', [
                'title' => 'Editar rol',
                'role' => $role,
                'permisos_all' => $permisosAll,
                'permiso_ids' => $assigned,
                'csrf' => Csrf::token(),
                'error' => Flash::get('error'),
                'success' => Flash::get('success'),
            ]);
        } catch (Throwable $e) {
            error_log('[roles.editForm] ' . $e->getMessage() . ' id=' . $id);
            Flash::set('error', 'No se pudo cargar el formulario de edición.');
            $this->redirect303('/roles/' . $id);
        }
    }

    public function update(int $id): void
    {
        if (!$this->adminOnlyOr403()) return;

        if (!Csrf::validate((string)($_POST['csrf'] ?? ''))) {
            Flash::set('error', 'CSRF inválido.');
            $this->redirect303('/roles/' . $id . '/editar');
        }

        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));

        $permsRaw = $_POST['permisos'] ?? [];
        $permIds = [];
        if (is_array($permsRaw)) {
            foreach ($permsRaw as $pid) {
                $pid = (int)$pid;
                if ($pid > 0) $permIds[] = $pid;
            }
        }
        $permIds = array_values(array_unique($permIds));

        $errors = [];
        if ($nombre === '') {
            $errors['nombre'] = 'Nombre requerido.';
        }

        if (!empty($errors)) {
            Flash::setData('old', ['nombre' => $nombre, 'descripcion' => $descripcion, 'permisos' => $permIds]);
            Flash::setData('errors', $errors);
            Flash::set('error', 'Revisa los campos marcados.');
            $this->redirect303('/roles/' . $id . '/editar');
        }

        try {
            $pdo = Database::pdo();

            // existencia
            $st0 = $pdo->prepare('SELECT id FROM roles WHERE id = :id LIMIT 1');
            $st0->execute([':id' => $id]);
            if (!$st0->fetchColumn()) {
                http_response_code(404);
                echo '404 Not Found';
                return;
            }

            // unicidad nombre (excluye id)
            $stE = $pdo->prepare('SELECT id FROM roles WHERE nombre = :nombre AND id <> :id LIMIT 1');
            $stE->execute([':nombre' => $nombre, ':id' => $id]);
            if ($stE->fetchColumn()) {
                Flash::setData('old', ['nombre' => $nombre, 'descripcion' => $descripcion, 'permisos' => $permIds]);
                Flash::setData('errors', ['nombre' => 'Ya existe otro rol con ese nombre.']);
                Flash::set('error', 'No se pudo actualizar el rol.');
                $this->redirect303('/roles/' . $id . '/editar');
            }

            $pdo->beginTransaction();

            $stU = $pdo->prepare('UPDATE roles SET nombre = :nombre, descripcion = :descripcion WHERE id = :id');
            $stU->execute([':nombre' => $nombre, ':descripcion' => $descripcion, ':id' => $id]);

            // sync permisos
            $del = $pdo->prepare('DELETE FROM rol_permisos WHERE rol_id = :rid');
            $del->execute([':rid' => $id]);

            if (!empty($permIds)) {
                $ins = $pdo->prepare('INSERT INTO rol_permisos (rol_id, permiso_id) VALUES (:rid, :pid)');
                foreach ($permIds as $pid) {
                    $ins->execute([':rid' => $id, ':pid' => $pid]);
                }
            }

            $pdo->commit();

            try {
                Auditoria::log('seguridad.rol.editar', [
                    'admin_id' => (int)(Auth::user()['id'] ?? 0),
                    'rol_id' => $id,
                    'nombre' => $nombre,
                    'permisos' => $permIds,
                ]);
            } catch (Throwable) {}

            Flash::set('success', 'Rol actualizado.');
            $this->redirect303('/roles/' . $id);
        } catch (Throwable $e) {
            try {
                $pdo = Database::pdo();
                if ($pdo->inTransaction()) $pdo->rollBack();
            } catch (Throwable) {}

            error_log('[roles.update] ' . $e->getMessage() . ' id=' . $id);
            Flash::setData('old', ['nombre' => $nombre, 'descripcion' => $descripcion, 'permisos' => $permIds]);
            Flash::set('error', 'No se pudo actualizar el rol.');
            $this->redirect303('/roles/' . $id . '/editar');
        }
    }
}