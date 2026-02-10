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

final class UsuariosController
{
    /** @return array<string,bool> */
    private function columns(string $table): array
    {
        static $cache = [];
        if (isset($cache[$table]) && is_array($cache[$table])) {
            return $cache[$table];
        }

        try {
            $pdo = Database::pdo();
            $st = $pdo->prepare(
                "SELECT column_name
                 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name = :t"
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
    private function rolesAll(): array
    {
        $pdo = Database::pdo();
        $st = $pdo->query("SELECT * FROM roles ORDER BY id ASC");
        return $st ? (array)$st->fetchAll() : [];
    }

    /** @return int[] */
    private function roleIdsByUser(int $userId): array
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare("SELECT rol_id FROM usuario_roles WHERE usuario_id = :uid ORDER BY rol_id ASC");
        $st->execute([':uid' => $userId]);

        $ids = [];
        while ($r = $st->fetch()) {
            $rid = (int)($r['rol_id'] ?? 0);
            if ($rid > 0) $ids[] = $rid;
        }
        return $ids;
    }

    public function index(): void
    {
        // ✅ ADMIN-ONLY (id=1) para TODO el módulo Seguridad
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
            $cols = $this->columns('usuarios');
            if (empty($cols)) {
                $cols = ['email' => true, 'nombre' => true, 'nombres' => true];
            }

            $where = [];
            $params = [];

            if ($q !== '') {
                $or = [];

                if (ctype_digit($q) && (int)$q > 0) {
                    $or[] = 'id = :id';
                    $params[':id'] = (int)$q;
                }

                $like = '%' . $q . '%';
                $k = 1;

                if (isset($cols['email'])) {
                    $or[] = 'email LIKE :q' . $k;
                    $params[':q' . $k] = $like;
                    $k++;
                }
                if (isset($cols['nombre'])) {
                    $or[] = 'nombre LIKE :q' . $k;
                    $params[':q' . $k] = $like;
                    $k++;
                }
                if (isset($cols['nombres'])) {
                    $or[] = 'nombres LIKE :q' . $k;
                    $params[':q' . $k] = $like;
                    $k++;
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
                $items = $st ? (array)$st->fetchAll() : [];
            } else {
                $st = $pdo->prepare($sql);
                $st->execute($params);
                $items = (array)$st->fetchAll();
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
            'is_admin' => $this->isAdminId1(),
        ]);
    }

    public function show(int $id): void
    {
        // ✅ ADMIN-ONLY (id=1) para TODO el módulo Seguridad
        if (!$this->adminOnlyOr403()) return;

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

            $sqlRoles = "
                SELECT r.*
                FROM roles r
                INNER JOIN usuario_roles ur ON ur.rol_id = r.id
                WHERE ur.usuario_id = :uid
                ORDER BY r.id ASC
            ";
            $sr = $pdo->prepare($sqlRoles);
            $sr->execute([':uid' => $id]);
            $roles = (array)$sr->fetchAll();
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
            'is_admin' => $this->isAdminId1(),
        ]);
    }

    // =========================
    // ✅ NUEVO (solo admin id=1)
    // =========================

    public function createForm(): void
    {
        if (!$this->adminOnlyOr403()) return;

        try {
            $roles = $this->rolesAll();
        } catch (Throwable $e) {
            error_log('[usuarios.createForm] ' . $e->getMessage());
            Flash::set('error', 'No se pudo cargar roles para el formulario.');
            $roles = [];
        }

        View::render('usuarios/create', [
            'title' => 'Crear usuario',
            'roles' => $roles,
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
            $this->redirect303('/usuarios/crear');
        }

        $email = trim(strtolower((string)($_POST['email'] ?? '')));
        $nombre = trim((string)($_POST['nombre'] ?? $_POST['nombres'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $rolesRaw = $_POST['roles'] ?? [];
        $roleIds = [];
        if (is_array($rolesRaw)) {
            foreach ($rolesRaw as $rid) {
                $rid = (int)$rid;
                if ($rid > 0) $roleIds[] = $rid;
            }
        }
        $roleIds = array_values(array_unique($roleIds));

        $errors = [];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido.';
        }

        if ($password === '' || mb_strlen($password) < 6) {
            $errors['password'] = 'Password requerido (mínimo 6 caracteres).';
        }

        // nombre puede depender del schema, pero lo validamos si existe columna
        $cols = $this->columns('usuarios');
        if (empty($cols)) $cols = ['nombre' => true, 'nombres' => true];
        $nameCol = isset($cols['nombre']) ? 'nombre' : (isset($cols['nombres']) ? 'nombres' : null);

        if ($nameCol !== null && $nombre === '') {
            $errors['nombre'] = 'Nombre requerido.';
        }

        if (!empty($errors)) {
            Flash::setData('old', [
                'email' => $email,
                'nombre' => $nombre,
                'roles' => $roleIds,
            ]);
            Flash::setData('errors', $errors);
            Flash::set('error', 'Revisa los campos marcados.');
            $this->redirect303('/usuarios/crear');
        }

        try {
            $pdo = Database::pdo();

            // Unicidad email
            $stE = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
            $stE->execute([':email' => $email]);
            $exists = $stE->fetchColumn();
            if ($exists) {
                Flash::setData('old', ['email' => $email, 'nombre' => $nombre, 'roles' => $roleIds]);
                Flash::setData('errors', ['email' => 'Ya existe un usuario con ese email.']);
                Flash::set('error', 'No se pudo crear el usuario.');
                $this->redirect303('/usuarios/crear');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $pdo->beginTransaction();

            // Insert dinámico según columnas existentes
            $sqlCols = ['email', 'password_hash'];
            $sqlVals = [':email', ':hash'];
            $params = [':email' => $email, ':hash' => $hash];

            if ($nameCol !== null) {
                $sqlCols[] = $nameCol;
                $sqlVals[] = ':nombre';
                $params[':nombre'] = $nombre;
            }

            $sql = 'INSERT INTO usuarios (' . implode(',', $sqlCols) . ') VALUES (' . implode(',', $sqlVals) . ')';
            $st = $pdo->prepare($sql);
            $st->execute($params);

            $newId = (int)$pdo->lastInsertId();

            // Roles
            if (!empty($roleIds)) {
                $ins = $pdo->prepare('INSERT INTO usuario_roles (usuario_id, rol_id) VALUES (:uid, :rid)');
                foreach ($roleIds as $rid) {
                    $ins->execute([':uid' => $newId, ':rid' => $rid]);
                }
            }

            $pdo->commit();

            // Auditoría (firma correcta) - NO rompe si falla
            try {
                Auditoria::log(
                    $this->userId(),
                    'crear',
                    'usuarios',
                    (int)$newId,
                    [
                        'email' => $email,
                        'roles' => $roleIds,
                    ]
                );
            } catch (Throwable) {
                // no-op
            }

            Flash::set('success', 'Usuario creado correctamente.');
            $this->redirect303('/usuarios/' . $newId);
        } catch (Throwable $e) {
            try {
                $pdo = Database::pdo();
                if ($pdo->inTransaction()) $pdo->rollBack();
            } catch (Throwable) {
                // no-op
            }

            error_log('[usuarios.create] ' . $e->getMessage());
            Flash::setData('old', ['email' => $email, 'nombre' => $nombre, 'roles' => $roleIds]);
            Flash::set('error', 'No se pudo crear el usuario.');
            $this->redirect303('/usuarios/crear');
        }
    }

    public function editForm(int $id): void
    {
        if (!$this->adminOnlyOr403()) return;

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

            $roles = $this->rolesAll();
            $userRoleIds = $this->roleIdsByUser($id);

            View::render('usuarios/edit', [
                'title' => 'Editar usuario',
                'user' => $user,
                'roles' => $roles,
                'user_role_ids' => $userRoleIds,
                'csrf' => Csrf::token(),
                'error' => Flash::get('error'),
                'success' => Flash::get('success'),
            ]);
        } catch (Throwable $e) {
            error_log('[usuarios.editForm] ' . $e->getMessage() . ' id=' . $id);
            Flash::set('error', 'No se pudo cargar el formulario de edición.');
            $this->redirect303('/usuarios/' . $id);
        }
    }

    public function update(int $id): void
    {
        if (!$this->adminOnlyOr403()) return;

        if (!Csrf::validate((string)($_POST['csrf'] ?? ''))) {
            Flash::set('error', 'CSRF inválido.');
            $this->redirect303('/usuarios/' . $id . '/editar');
        }

        $email = trim(strtolower((string)($_POST['email'] ?? '')));
        $nombre = trim((string)($_POST['nombre'] ?? $_POST['nombres'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $rolesRaw = $_POST['roles'] ?? [];
        $roleIds = [];
        if (is_array($rolesRaw)) {
            foreach ($rolesRaw as $rid) {
                $rid = (int)$rid;
                if ($rid > 0) $roleIds[] = $rid;
            }
        }
        $roleIds = array_values(array_unique($roleIds));

        $errors = [];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido.';
        }

        $cols = $this->columns('usuarios');
        if (empty($cols)) $cols = ['nombre' => true, 'nombres' => true];
        $nameCol = isset($cols['nombre']) ? 'nombre' : (isset($cols['nombres']) ? 'nombres' : null);

        if ($nameCol !== null && $nombre === '') {
            $errors['nombre'] = 'Nombre requerido.';
        }

        if ($password !== '' && mb_strlen($password) < 6) {
            $errors['password'] = 'Si cambias password, mínimo 6 caracteres.';
        }

        if (!empty($errors)) {
            Flash::setData('old', [
                'email' => $email,
                'nombre' => $nombre,
                'roles' => $roleIds,
            ]);
            Flash::setData('errors', $errors);
            Flash::set('error', 'Revisa los campos marcados.');
            $this->redirect303('/usuarios/' . $id . '/editar');
        }

        try {
            $pdo = Database::pdo();

            // Validar existencia
            $st0 = $pdo->prepare('SELECT id FROM usuarios WHERE id = :id LIMIT 1');
            $st0->execute([':id' => $id]);
            if (!$st0->fetchColumn()) {
                http_response_code(404);
                echo '404 Not Found';
                return;
            }

            // Unicidad email (excluye id)
            $stE = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email AND id <> :id LIMIT 1');
            $stE->execute([':email' => $email, ':id' => $id]);
            if ($stE->fetchColumn()) {
                Flash::setData('old', ['email' => $email, 'nombre' => $nombre, 'roles' => $roleIds]);
                Flash::setData('errors', ['email' => 'Ya existe otro usuario con ese email.']);
                Flash::set('error', 'No se pudo actualizar el usuario.');
                $this->redirect303('/usuarios/' . $id . '/editar');
            }

            $pdo->beginTransaction();

            $sets = ['email = :email'];
            $params = [':email' => $email, ':id' => $id];

            if ($nameCol !== null) {
                $sets[] = $nameCol . ' = :nombre';
                $params[':nombre'] = $nombre;
            }

            if ($password !== '') {
                $sets[] = 'password_hash = :hash';
                $params[':hash'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $sqlU = 'UPDATE usuarios SET ' . implode(', ', $sets) . ' WHERE id = :id';
            $stU = $pdo->prepare($sqlU);
            $stU->execute($params);

            // Sync roles
            $del = $pdo->prepare('DELETE FROM usuario_roles WHERE usuario_id = :uid');
            $del->execute([':uid' => $id]);

            if (!empty($roleIds)) {
                $ins = $pdo->prepare('INSERT INTO usuario_roles (usuario_id, rol_id) VALUES (:uid, :rid)');
                foreach ($roleIds as $rid) {
                    $ins->execute([':uid' => $id, ':rid' => $rid]);
                }
            }

            $pdo->commit();

            // Auditoría (firma correcta) - NO rompe si falla
            try {
                Auditoria::log(
                    $this->userId(),
                    'editar',
                    'usuarios',
                    (int)$id,
                    [
                        'email' => $email,
                        'roles' => $roleIds,
                        'password_changed' => ($password !== ''),
                    ]
                );
            } catch (Throwable) {
                // no-op
            }

            Flash::set('success', 'Usuario actualizado.');
            $this->redirect303('/usuarios/' . $id);
        } catch (Throwable $e) {
            try {
                $pdo = Database::pdo();
                if ($pdo->inTransaction()) $pdo->rollBack();
            } catch (Throwable) {
                // no-op
            }

            error_log('[usuarios.update] ' . $e->getMessage() . ' id=' . $id);
            Flash::setData('old', ['email' => $email, 'nombre' => $nombre, 'roles' => $roleIds]);
            Flash::set('error', 'No se pudo actualizar el usuario.');
            $this->redirect303('/usuarios/' . $id . '/editar');
        }
    }
}