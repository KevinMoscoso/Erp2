<?php
declare(strict_types=1);

namespace Erp2\Core;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        $token = $_SESSION[self::SESSION_KEY] ?? null;

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $_SESSION[self::SESSION_KEY] = $token;
        }

        return $token;
    }

    /**
     * Valida el token y lo invalida (one-time token).
     */
    public static function validate(?string $token): bool
    {
        $stored = $_SESSION[self::SESSION_KEY] ?? null;

        if (!is_string($stored) || $stored === '') {
            return false;
        }

        if (!is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($stored, $token);
    }
}