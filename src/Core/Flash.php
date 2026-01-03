<?php
declare(strict_types=1);

namespace Erp2\Core;

final class Flash
{
    private const SESSION_KEY = '_flash';

    public static function set(string $key, string $message): void
    {
        $key = trim($key);
        if ($key === '') {
            return;
        }

        $_SESSION[self::SESSION_KEY][$key] = $message;
    }

    public static function get(string $key): ?string
    {
        $key = trim($key);
        if ($key === '') {
            return null;
        }

        $msg = $_SESSION[self::SESSION_KEY][$key] ?? null;
        unset($_SESSION[self::SESSION_KEY][$key]);

        if (!is_string($msg) || $msg === '') {
            return null;
        }

        return $msg;
    }
}