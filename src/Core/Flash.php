<?php
declare(strict_types=1);

namespace Erp2\Core;

final class Flash
{
    private const SESSION_KEY_MSG  = '_flash';
    private const SESSION_KEY_DATA = '_flash_data';

    public static function set(string $key, string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        if (!isset($_SESSION[self::SESSION_KEY_MSG]) || !is_array($_SESSION[self::SESSION_KEY_MSG])) {
            $_SESSION[self::SESSION_KEY_MSG] = [];
        }

        $_SESSION[self::SESSION_KEY_MSG][$key] = $message;
    }

    public static function get(string $key): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        if (!isset($_SESSION[self::SESSION_KEY_MSG]) || !is_array($_SESSION[self::SESSION_KEY_MSG])) {
            return null;
        }

        $msg = $_SESSION[self::SESSION_KEY_MSG][$key] ?? null;

        if ($msg !== null) {
            unset($_SESSION[self::SESSION_KEY_MSG][$key]);
        }

        return is_string($msg) ? $msg : null;
    }

    /**
     * Guarda datos "flash" (arrays/objetos simples) para el siguiente request.
     * Útil para old input / errores por campo.
     */
    public static function setData(string $key, mixed $value): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        if (!isset($_SESSION[self::SESSION_KEY_DATA]) || !is_array($_SESSION[self::SESSION_KEY_DATA])) {
            $_SESSION[self::SESSION_KEY_DATA] = [];
        }

        $_SESSION[self::SESSION_KEY_DATA][$key] = $value;
    }

    /**
     * Obtiene datos flash y los elimina (flash-like).
     */
    public static function getData(string $key, mixed $default = null): mixed
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        if (!isset($_SESSION[self::SESSION_KEY_DATA]) || !is_array($_SESSION[self::SESSION_KEY_DATA])) {
            return $default;
        }

        if (!array_key_exists($key, $_SESSION[self::SESSION_KEY_DATA])) {
            return $default;
        }

        $val = $_SESSION[self::SESSION_KEY_DATA][$key];
        unset($_SESSION[self::SESSION_KEY_DATA][$key]);

        return $val;
    }

    /**
     * Lee datos flash sin consumirlos (no los elimina).
     */
    public static function peekData(string $key, mixed $default = null): mixed
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        if (!isset($_SESSION[self::SESSION_KEY_DATA]) || !is_array($_SESSION[self::SESSION_KEY_DATA])) {
            return $default;
        }

        return array_key_exists($key, $_SESSION[self::SESSION_KEY_DATA])
            ? $_SESSION[self::SESSION_KEY_DATA][$key]
            : $default;
    }

    public static function clearData(string $key): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        if (isset($_SESSION[self::SESSION_KEY_DATA]) && is_array($_SESSION[self::SESSION_KEY_DATA])) {
            unset($_SESSION[self::SESSION_KEY_DATA][$key]);
        }
    }
}