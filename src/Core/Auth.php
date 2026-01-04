<?php
declare(strict_types=1);

namespace Erp2\Core;

use PDOException;

final class Auth
{
    private const SESSION_USER_ID = 'user_id';
    private const SESSION_PERMS = 'user_perms';

    private static ?array $cachedUser = null;
    private static bool $loadedUser = false;

    /** @var array<string, true>|null */
    private static ?array $cachedPerms = null;
    private static bool $loadedPerms = false;

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$loadedUser) {
            return self::$cachedUser;
        }

        self::$loadedUser = true;

        $id = (int) ($_SESSION[self::SESSION_USER_ID] ?? 0);
        if ($id <= 0) {
            self::$cachedUser = null;
            return null;
        }

        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);

            $user = $stmt->fetch();
            if (!is_array($user)) {
                self::logout();
                return null;
            }

            // Nunca exponer hash en el array de usuario
            unset($user['password_hash']);

            self::$cachedUser = $user;
            return self::$cachedUser;
        } catch (PDOException) {
            self::logout();
            return null;
        }
    }

    public static function loginByEmailPassword(string $email, string $password): bool
    {
        $email = trim(strtolower($email));
        $password = (string) $password;

        if ($email === '' || $password === '') {
            return false;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare('SELECT id, email, password_hash FROM usuarios WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);

            $row = $stmt->fetch();
            if (!is_array($row)) {
                return false;
            }

            $hash = $row['password_hash'] ?? null;
            $id = $row['id'] ?? null;

            if (!is_string($hash) || $hash === '' || !is_numeric($id)) {
                return false;
            }
            if (!password_verify($password, $hash)) {
                return false;
            }

            $_SESSION[self::SESSION_USER_ID] = (int) $id;

            // Reset cache user + perms
            self::resetCache(true);

            return true;
        } catch (PDOException) {
            return false;
        }
    }

    public static function logout(): void
    {
        self::resetCache(false);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'] ?? '/',
                    'domain' => $params['domain'] ?? '',
                    'secure' => (bool) ($params['secure'] ?? false),
                    'httponly' => (bool) ($params['httponly'] ?? true),
                    'samesite' => (string) ($params['samesite'] ?? 'Lax'),
                ]
            );
        }

        session_destroy();
    }

    public static function requireLogin(): void
    {
        if (self::check()) {
            return;
        }

        header('Location: /login', true, 302);
        exit;
    }

    /** RBAC: ¿tiene permiso? */
    public static function has(string $permiso): bool
    {
        $permiso = trim($permiso);
        if ($permiso === '' || !self::check()) {
            return false;
        }

        $perms = self::permissions();
        return isset($perms[$permiso]);
    }

    /** RBAC: exige permiso, si no -> 403 */
    public static function can(string $permiso): void
    {
        if (self::has($permiso)) {
            return;
        }

        http_response_code(403);
        echo "403 Forbidden";
        exit;
    }

    /** @return array<string, true> */
    private static function permissions(): array
    {
        if (self::$loadedPerms && is_array(self::$cachedPerms)) {
            return self::$cachedPerms;
        }

        self::$loadedPerms = true;

        // Cache en sesión (si existe)
        $sess = $_SESSION[self::SESSION_PERMS] ?? null;
        if (is_array($sess)) {
            $map = [];
            foreach ($sess as $p) {
                if (is_string($p) && $p !== '') {
                    $map[$p] = true;
                }
            }
            self::$cachedPerms = $map;
            return $map;
        }

        $u = self::user();
        $uid = (int) ($u['id'] ?? 0);
        if ($uid <= 0) {
            self::$cachedPerms = [];
            return [];
        }

        try {
            $pdo = Database::pdo();

            // Nota: nombres asumidos según tu schema descrito:
            // usuario_roles(usuario_id, rol_id), rol_permisos(rol_id, permiso_id), permisos(id, nombre)
            $sql = "
                SELECT DISTINCT p.codigo
                FROM permisos p
                INNER JOIN rol_permisos rp ON rp.permiso_id = p.id
                INNER JOIN usuario_roles ur ON ur.rol_id = rp.rol_id
                WHERE ur.usuario_id = :uid
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':uid' => $uid]);

            $list = [];
            $map = [];
            while ($row = $stmt->fetch()) {
                $name = $row['codigo'] ?? null;
                if (is_string($name) && $name !== '') {
                    $list[] = $name;
                    $map[$name] = true;
                }
            }

            $_SESSION[self::SESSION_PERMS] = $list;
            self::$cachedPerms = $map;

            return $map;
        } catch (PDOException) {
            self::$cachedPerms = [];
            return [];
        }
    }

    private static function resetCache(bool $keepSessionUserId): void
    {
        self::$cachedUser = null;
        self::$loadedUser = false;

        self::$cachedPerms = null;
        self::$loadedPerms = false;

        if (!$keepSessionUserId) {
            unset($_SESSION[self::SESSION_USER_ID]);
        }
        unset($_SESSION[self::SESSION_PERMS]);
    }
}