<?php
declare(strict_types=1);

namespace Erp2\Core;

use PDOException;

final class Auth
{
    private const SESSION_USER_ID = 'user_id';

    private static ?array $cachedUser = null;
    private static bool $loaded = false;

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$cachedUser;
        }

        self::$loaded = true;

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
            // Fail-closed: si hay cualquier problema, se considera sin sesión válida
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

            // Reset cache
            self::$cachedUser = null;
            self::$loaded = false;

            return true;
        } catch (PDOException) {
            return false;
        }
    }

    public static function logout(): void
    {
        self::$cachedUser = null;
        self::$loaded = false;

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        // Limpiar datos de sesión
        $_SESSION = [];

        // Eliminar cookie de sesión si existe
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
}