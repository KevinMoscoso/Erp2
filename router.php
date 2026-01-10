<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Normaliza
$path = str_replace("\0", '', $path);

// Bloquea traversal
if (str_contains($path, '..')) {
    http_response_code(400);
    echo "Bad Request";
    exit;
}

$publicDir = realpath(__DIR__ . '/public');
$file = realpath(__DIR__ . '/public' . $path);

// Sirve archivos reales dentro de /public
if ($path !== '/' && $file && $publicDir && str_starts_with($file, $publicDir) && is_file($file)) {
    return false;
}

require __DIR__ . '/public/index.php';