<?php
declare(strict_types=1);

namespace Erp2\Controller;

use Erp2\Core\Auth;
use Erp2\Core\Csrf;
use Erp2\Core\Database;
use Erp2\Core\Flash;
use Erp2\Core\View;
use Erp2\Model\Auditoria;
use PDO;
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
            $cache[$table] = [];
            return [];
        }
    }

    private function isAdminId1(): bool
    {
        $u = Auth::user();
        return (int)($u['id'] ?? 0) === 1;
    }

    private function userId(): int
    {
        $u = Auth::user();
        return is_array($u) ? (int)($u['id'] ?? 0) : 0;
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

    /** @return array<int,array<string,mixed>> */
    private function permisosAll(): array
    {
        $pdo = Database::pdo();
        $st = $pdo->query("SELECT id, codigo FROM permisos ORDER BY codigo ASC");
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

    /** @return int[] */
    private function sanitizeIntIds(mixed $raw): array
    {
        $ids = [];
        if (is_array($raw)) {
            foreach ($raw as $v) {
                $i = (int)$v;
                if ($i > 0) $ids[] = $i;
            }
        }
        $ids = array_values(array_unique($ids));
        return $ids;
    }

    /**
     * Valida que los permiso_id existan en tabla permisos.
     * @return array{valid:int[], invalid:bool}
     */
    private function validatePermIds(PDO $pdo, array $permIds): array
    {
        if (empty($permIds)) {
            return ['valid' => [], 'invalid' => false];
        }

        $ph = [];
        $params = [];
        $k = 1;
        foreach ($permIds as $pid) {
            $name = ':p' . $k;
            $ph[] = $name;
            $params[$name] = (int)$pid;
            $k++;
        }

        $sql = "SELECT id FROM permisos WHERE id IN (" . implode(',', $ph) . ")";
        $st = $pdo->prepare($sql);
        $st->execute($params);

        $found = [];
        while ($r = $st->fetch()) {
            $id = (int)($r['id'] ?? 0);
            if ($id > 0) $found[] = $id;
        }

        $found = array_values(array_unique($found));
        sort($found);

        $orig = $permIds;
        sort($orig);

        $invalid = (count($found) !== count($orig));
        return ['valid' => $found, 'invalid' => $invalid];
    }

    /**
     * Asegura el rol personal USR_{userId}. Devuelve roleId.
     */
    private function ensurePersonalRole(PDO $pdo, int $userId, string $email): int
    {
        $roleName = 'USR_' . $userId;

        $st = $pdo->prepare("SELECT id FROM roles WHERE nombre = :n LIMIT 1");
        $st->execute([':n' => $roleName]);
        $rid = (int)($st->fetchColumn() ?: 0);
        if ($rid > 0) {
            return $rid;
        }

        // Insert dinámico según columnas reales
        $cols = $this->columns('roles');
        if (empty($cols)) {
            // fallback
            $cols = ['nombre' => true, 'descripcion' => true, 'created_at' => true];
        }

        $sqlCols = ['nombre'];
        $sqlVals = [':nombre'];
        $params = [':nombre' => $roleName];

        if (isset($cols['descripcion'])) {
            $sqlCols[] = 'descripcion';
            $sqlVals[] = ':desc';
            $params[':desc'] = 'Permisos directos usuario ' . $email;
        }

        if (isset($cols['created_at'])) {
            $sqlCols[] = 'created_at';
            $sqlVals[] = 'NOW()';
        }

        $sql = 'INSERT INTO roles (' . implode(',', $sqlCols) . ') VALUES (' . implode(',', $sqlVals) . ')';
        $ins = $pdo->prepare($sql);
        $ins->execute($params);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Sincroniza permisos del rol (borra todo y re-inserta seleccionados).
     */
    private function syncRolePerms(PDO $pdo, int $roleId, array $permIds): void
    {
        $del = $pdo->prepare("DELETE FROM rol_permisos WHERE rol_id = :rid");
        $del->execute([':rid' => $roleId]);

        if (empty($permIds)) {
            return;
        }

        $ins = $pdo->prepare("INSERT INTO rol_permisos (rol_id, permiso_id) VALUES (:rid, :pid)");
        foreach ($permIds as $pid) {
            $ins->execute([':rid' => $roleId, ':pid' => (int)$pid]);
        }
    }

    public function index(): void
    {
        if (!$this->adminOnlyOr403()) return;

        // 1) Leer y NORMALIZAR q (elimina espacios raros / invisibles)
        $qRaw = (string)($_GET['q'] ?? '');
        // Normaliza espacios unicode (incluye NBSP) a espacio normal
        $q = preg_replace('/[[:space:]\x{00A0}]+/u', ' ', $qRaw) ?? '';
        $q = trim($q);

        // Limitar longitud
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($q) > 120) $q = mb_substr($q, 0, 120);
        } else {
            if (strlen($q) > 120) $q = substr($q, 0, 120);
        }

        $limit = (int)($_GET['limit'] ?? 200);
        if ($limit <= 0) $limit = 200;
        if ($limit > 500) $limit = 500;

        $items = [];

        try {
            $pdo = Database::pdo();
            $cols = $this->columns('usuarios');
            if (empty($cols)) {
                $cols = ['email' => true, 'nombre' => true];
            }

            $where = [];
            $params = [];

            if ($q !== '') {
                $or = [];

                // 2) Buscar ID sin depender de ctype_digit()
                // Esto permite encontrar por "1", " 1 ", "1\u00A0", etc.
                $or[] = 'CAST(id AS CHAR) = :id_str';
                $params[':id_str'] = $q;

                // 3) LIKE robusto (sin LOWER, y sin asumir collation)
                // Si tu DB ya es case-insensitive, esto funciona normal.
                // Si fuese case-sensitive, igual te matchea por exactitud de patrón, pero tu caso B demuestra que LIKE funciona.
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
                // IMPORTANTE: NO usar nombres si no existe
                // if (isset($cols['nombres'])) { ... }

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

            $st = $pdo->prepare($sql);
            $st->execute($params);
            $items = (array)$st->fetchAll();

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
        if (!$this->adminOnlyOr403()) return;

        $user = null;
        $roles = [];
        $permsDirectos = [];
        $permsEfectivos = [];

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

            // Roles del usuario
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

            // Permisos directos (rol personal USR_{id})
            $roleName = 'USR_' . $id;
            $stR = $pdo->prepare("SELECT id FROM roles WHERE nombre = :n LIMIT 1");
            $stR->execute([':n' => $roleName]);
            $personalRoleId = (int)($stR->fetchColumn() ?: 0);

            if ($personalRoleId > 0) {
                $stPD = $pdo->prepare("
                    SELECT p.codigo
                    FROM permisos p
                    INNER JOIN rol_permisos rp ON rp.permiso_id = p.id
                    WHERE rp.rol_id = :rid
                    ORDER BY p.codigo ASC
                ");
                $stPD->execute([':rid' => $personalRoleId]);
                $rows = (array)$stPD->fetchAll();
                foreach ($rows as $r) {
                    $c = (string)($r['codigo'] ?? '');
                    if ($c !== '') $permsDirectos[] = $c;
                }
            }

            // Permisos efectivos (DISTINCT por todos los roles del usuario)
            $stPE = $pdo->prepare("
                SELECT DISTINCT p.codigo
                FROM permisos p
                INNER JOIN rol_permisos rp ON rp.permiso_id = p.id
                INNER JOIN usuario_roles ur ON ur.rol_id = rp.rol_id
                WHERE ur.usuario_id = :uid
                ORDER BY p.codigo ASC
            ");
            $stPE->execute([':uid' => $id]);
            $rows2 = (array)$stPE->fetchAll();
            foreach ($rows2 as $r) {
                $c = (string)($r['codigo'] ?? '');
                if ($c !== '') $permsEfectivos[] = $c;
            }
        } catch (Throwable $e) {
            error_log('[usuarios.show] ' . $e->getMessage() . ' id=' . $id);
            Flash::set('error', 'No se pudo cargar el detalle del usuario.');
            $user = is_array($user) ? $user : ['id' => $id];
            $roles = [];
            $permsDirectos = [];
            $permsEfectivos = [];
        }

        View::render('usuarios/show', [
            'title' => 'Detalle usuario',
            'user' => $user,
            'roles' => $roles,
            'perms_directos' => $permsDirectos,
            'perms_efectivos' => $permsEfectivos,
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
            'is_admin' => $this->isAdminId1(),
        ]);
    }

    // =========================
    // ✅ ADMIN-ONLY (id=1)
    // =========================

    public function createForm(): void
    {
        if (!$this->adminOnlyOr403()) return;

        try {
            $roles = $this->rolesAll();
            $permisos = $this->permisosAll();
        } catch (Throwable $e) {
            error_log('[usuarios.createForm] ' . $e->getMessage());
            Flash::set('error', 'No se pudo cargar datos para el formulario.');
            $roles = [];
            $permisos = [];
        }

        View::render('usuarios/create', [
            'title' => 'Crear usuario',
            'roles' => $roles,
            'permisos' => $permisos,
            'csrf' => Csrf::token(),
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
        ]);
    }

    public function create(): void
    {
        if (!$this->adminOnlyOr403()) return;

        // ✅ 1) Captura inputs temprano (para no perderlos en CSRF inválido)
        $email = trim(strtolower((string)($_POST['email'] ?? '')));
        $nombre = trim((string)($_POST['nombre'] ?? $_POST['nombres'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $roleIds = $this->sanitizeIntIds($_POST['roles'] ?? []);
        $permIds = $this->sanitizeIntIds($_POST['perm_ids'] ?? []);

        $old = [
            'email' => $email,
            'nombre' => $nombre,
            'roles' => $roleIds,
            'perm_ids' => $permIds,
        ];

        // ✅ 2) CSRF: persistir old/errors y cortar ejecución
        if (!Csrf::validate((string)($_POST['csrf'] ?? ''))) {
            Flash::setData('old', $old);
            Flash::setData('errors', ['csrf' => 'CSRF inválido.']);
            Flash::set('error', 'CSRF inválido.');
            $this->redirect303('/usuarios/crear');
            return; // ✅ IMPORTANTE
        }

        $errors = [];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido.';
        }

        if ($password === '' || mb_strlen($password) < 6) {
            $errors['password'] = 'Password requerido (mínimo 6 caracteres).';
        }

        $cols = $this->columns('usuarios');
        if (empty($cols)) $cols = ['nombre' => true, 'nombres' => true];
        $nameCol = isset($cols['nombre']) ? 'nombre' : (isset($cols['nombres']) ? 'nombres' : null);

        if ($nameCol !== null && $nombre === '') {
            $errors['nombre'] = 'Nombre requerido.';
        }

        try {
            $pdo = Database::pdo();
            $val = $this->validatePermIds($pdo, $permIds);
            $permIds = $val['valid'];

            // ✅ mantener old actualizado con permIds ya validados
            $old['perm_ids'] = $permIds;

            if ($val['invalid']) {
                $errors['perm_ids'] = 'Permisos inválidos.';
            }
        } catch (Throwable $e) {
            error_log('[usuarios.create.validatePermIds] ' . $e->getMessage());
            $errors['perm_ids'] = 'No se pudo validar permisos.';
        }

        if (!empty($errors)) {
            Flash::setData('old', $old);
            Flash::setData('errors', $errors);
            Flash::set('error', 'Revisa los campos marcados.');
            $this->redirect303('/usuarios/crear');
            return; // ✅
        }

        try {
            $pdo = Database::pdo();

            // Unicidad email
            $stE = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
            $stE->execute([':email' => $email]);
            if ($stE->fetchColumn()) {
                Flash::setData('old', $old);
                Flash::setData('errors', ['email' => 'Ya existe un usuario con ese email.']);
                Flash::set('error', 'No se pudo crear el usuario.');
                $this->redirect303('/usuarios/crear');
                return; // ✅
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

            // Rol personal SIEMPRE
            $personalRoleId = $this->ensurePersonalRole($pdo, $newId, $email);

            // Sync permisos directos en rol personal
            $this->syncRolePerms($pdo, $personalRoleId, $permIds);

            // Roles finales = roles seleccionados + rol personal
            $finalRoleIds = $roleIds;
            $finalRoleIds[] = $personalRoleId;
            $finalRoleIds = array_values(array_unique(array_map('intval', $finalRoleIds)));

            // Insert roles usuario
            $ins = $pdo->prepare('INSERT INTO usuario_roles (usuario_id, rol_id) VALUES (:uid, :rid)');
            foreach ($finalRoleIds as $rid) {
                if ((int)$rid > 0) {
                    $ins->execute([':uid' => $newId, ':rid' => (int)$rid]);
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
                        'roles_seleccionados' => $roleIds,
                        'roles_finales' => $finalRoleIds,     // ✅ recomendado
                        'perm_directos' => $permIds,
                        'role_personal' => $personalRoleId,
                    ]
                );
            } catch (Throwable) {
                // no-op
            }

            Flash::set('success', 'Usuario creado correctamente.');
            $this->redirect303('/usuarios/' . $newId);
            return; // ✅
        } catch (Throwable $e) {
            try {
                $pdo = Database::pdo();
                if ($pdo->inTransaction()) $pdo->rollBack();
            } catch (Throwable) {
                // no-op
            }

            error_log('[usuarios.create] ' . $e->getMessage());
            Flash::setData('old', $old);
            Flash::set('error', 'No se pudo crear el usuario.');
            $this->redirect303('/usuarios/crear');
            return; // ✅
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

            // Permisos (lista completa)
            $permisos = $this->permisosAll();

            // Permisos directos del rol personal USR_{id}
            $directPermIds = [];
            $roleName = 'USR_' . $id;
            $stR = $pdo->prepare("SELECT id FROM roles WHERE nombre = :n LIMIT 1");
            $stR->execute([':n' => $roleName]);
            $personalRoleId = (int)($stR->fetchColumn() ?: 0);

            if ($personalRoleId > 0) {
                $stD = $pdo->prepare("SELECT permiso_id FROM rol_permisos WHERE rol_id = :rid ORDER BY permiso_id ASC");
                $stD->execute([':rid' => $personalRoleId]);
                while ($r = $stD->fetch()) {
                    $pid = (int)($r['permiso_id'] ?? 0);
                    if ($pid > 0) $directPermIds[] = $pid;
                }
                $directPermIds = array_values(array_unique($directPermIds));
            }

            View::render('usuarios/edit', [
                'title' => 'Editar usuario',
                'user' => $user,
                'roles' => $roles,
                'user_role_ids' => $userRoleIds,
                'permisos' => $permisos,
                'direct_perm_ids' => $directPermIds,
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

        // ✅ 1) Captura inputs temprano (para no perderlos en CSRF inválido)
        $email = trim(strtolower((string)($_POST['email'] ?? '')));
        $nombre = trim((string)($_POST['nombre'] ?? $_POST['nombres'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $roleIds = $this->sanitizeIntIds($_POST['roles'] ?? []);
        $permIds = $this->sanitizeIntIds($_POST['perm_ids'] ?? []);

        $old = [
            'email' => $email,
            'nombre' => $nombre,
            'roles' => $roleIds,
            'perm_ids' => $permIds,
        ];

        // ✅ 2) CSRF: persistir old/errors y cortar ejecución
        if (!Csrf::validate((string)($_POST['csrf'] ?? ''))) {
            Flash::setData('old', $old);
            Flash::setData('errors', ['csrf' => 'CSRF inválido.']);
            Flash::set('error', 'CSRF inválido.');
            $this->redirect303('/usuarios/' . $id . '/editar');
            return; // ✅ IMPORTANTE
        }

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

        try {
            $pdo = Database::pdo();
            $val = $this->validatePermIds($pdo, $permIds);
            $permIds = $val['valid'];

            // ✅ mantener old actualizado con permIds ya validados
            $old['perm_ids'] = $permIds;

            if ($val['invalid']) {
                $errors['perm_ids'] = 'Permisos inválidos.';
            }
        } catch (Throwable $e) {
            error_log('[usuarios.update.validatePermIds] ' . $e->getMessage());
            $errors['perm_ids'] = 'No se pudo validar permisos.';
        }

        if (!empty($errors)) {
            Flash::setData('old', $old);
            Flash::setData('errors', $errors);
            Flash::set('error', 'Revisa los campos marcados.');
            $this->redirect303('/usuarios/' . $id . '/editar');
            return; // ✅
        }

        try {
            $pdo = Database::pdo();

            // Validar existencia
            $st0 = $pdo->prepare('SELECT id, email FROM usuarios WHERE id = :id LIMIT 1');
            $st0->execute([':id' => $id]);
            $row0 = $st0->fetch();
            if (!is_array($row0)) {
                http_response_code(404);
                echo '404 Not Found';
                return;
            }

            // Unicidad email (excluye id)
            $stE = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email AND id <> :id LIMIT 1');
            $stE->execute([':email' => $email, ':id' => $id]);
            if ($stE->fetchColumn()) {
                Flash::setData('old', $old);
                Flash::setData('errors', ['email' => 'Ya existe otro usuario con ese email.']);
                Flash::set('error', 'No se pudo actualizar el usuario.');
                $this->redirect303('/usuarios/' . $id . '/editar');
                return; // ✅
            }

            $pdo->beginTransaction();

            // Update usuario
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

            // Rol personal SIEMPRE (asegurar existencia)
            $personalRoleId = $this->ensurePersonalRole($pdo, $id, $email);

            // Sync permisos directos del rol personal
            $this->syncRolePerms($pdo, $personalRoleId, $permIds);

            // Roles finales = roles seleccionados + rol personal
            $finalRoleIds = $roleIds;
            $finalRoleIds[] = $personalRoleId;
            $finalRoleIds = array_values(array_unique(array_map('intval', $finalRoleIds)));

            // Sync usuario_roles (reemplazo)
            $del = $pdo->prepare('DELETE FROM usuario_roles WHERE usuario_id = :uid');
            $del->execute([':uid' => $id]);

            $ins = $pdo->prepare('INSERT INTO usuario_roles (usuario_id, rol_id) VALUES (:uid, :rid)');
            foreach ($finalRoleIds as $rid) {
                if ((int)$rid > 0) {
                    $ins->execute([':uid' => $id, ':rid' => (int)$rid]);
                }
            }

            $pdo->commit();

            // Auditoría (firma correcta)
            try {
                Auditoria::log(
                    $this->userId(),
                    'editar',
                    'usuarios',
                    (int)$id,
                    [
                        'email' => $email,
                        'roles_seleccionados' => $roleIds,
                        'roles_finales' => $finalRoleIds,     // ✅ recomendado
                        'perm_directos' => $permIds,
                        'role_personal' => $personalRoleId,
                        'password_changed' => ($password !== ''),
                    ]
                );
            } catch (Throwable) {
                // no-op
            }

            Flash::set('success', 'Usuario actualizado.');
            $this->redirect303('/usuarios/' . $id);
            return; // ✅
        } catch (Throwable $e) {
            try {
                $pdo = Database::pdo();
                if ($pdo->inTransaction()) $pdo->rollBack();
            } catch (Throwable) {
                // no-op
            }

            error_log('[usuarios.update] ' . $e->getMessage() . ' id=' . $id);
            Flash::setData('old', $old);
            Flash::set('error', 'No se pudo actualizar el usuario.');
            $this->redirect303('/usuarios/' . $id . '/editar');
            return; // ✅
        }
    }
}