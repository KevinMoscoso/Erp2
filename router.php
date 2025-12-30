<?php
declare(strict_types=1);

/**
 * Router para el servidor embebido:
 * php -S 127.0.0.1:8000 router.php
 */

// Si el archivo existe físicamente en /public, servirlo directamente
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . '/public' . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

// Si no existe como archivo, delegar al front controller
require __DIR__ . '/public/index.php';