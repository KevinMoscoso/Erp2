<?php
declare(strict_types=1);

/**
 * Helpers mínimos para UX:
 * - old('campo', $default)
 * - err('campo')
 * - hasErr('campo')
 *
 * Los datos se leen desde Flash::setData('old', ...) y Flash::setData('errors', ...).
 *
 * Importante: NO escapamos aquí. En las vistas sigue siendo obligatorio usar htmlspecialchars().
 */

use Erp2\Core\Flash;

if (!function_exists('form__flash_load')) {
    function form__flash_load(): array
    {
        static $loaded = false;
        static $old = [];
        static $errors = [];

        if (!$loaded) {
            $loaded = true;

            $o = Flash::getData('old', []);
            $e = Flash::getData('errors', []);

            $old = is_array($o) ? $o : [];
            $errors = is_array($e) ? $e : [];
        }

        return [$old, $errors];
    }
}

if (!function_exists('form__key_path')) {
    function form__key_path(string $key): array
    {
        // soporta: campo, items[0][cantidad], items.0.cantidad
        $key = str_replace([']'], '', $key);
        $parts = preg_split('/\\[|\\./', $key) ?: [];
        $parts = array_values(array_filter($parts, static fn($p) => $p !== '' && $p !== null));
        return $parts;
    }
}

if (!function_exists('form__array_get')) {
    function form__array_get(array $arr, string $key, mixed $default = null): mixed
    {
        $path = form__key_path($key);
        $cur = $arr;

        foreach ($path as $seg) {
            if (is_array($cur) && array_key_exists($seg, $cur)) {
                $cur = $cur[$seg];
                continue;
            }
            return $default;
        }

        return $cur;
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        [$old, $errors] = form__flash_load();
        return form__array_get($old, $key, $default);
    }
}

if (!function_exists('err')) {
    function err(string $key): ?string
    {
        [$old, $errors] = form__flash_load();
        $v = form__array_get($errors, $key, null);
        return is_string($v) ? $v : null;
    }
}

if (!function_exists('hasErr')) {
    function hasErr(string $key): bool
    {
        return err($key) !== null;
    }
}