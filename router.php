<?php
declare(strict_types=1);

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uriPath = str_replace("\0", '', $uriPath);

// Bloquea traversal
if (str_contains($uriPath, '..')) {
    http_response_code(400);
    echo "Bad Request";
    exit;
}

$publicDir = __DIR__ . DIRECTORY_SEPARATOR . 'public';

if ($uriPath !== '/') {
    $rel = ltrim($uriPath, '/');
    $candidate = $publicDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);

    if (is_file($candidate)) {
        // Seguridad: asegurar que el archivo esté dentro de /public
        $realPublic = realpath($publicDir);
        $realFile = realpath($candidate);
        if (!$realPublic || !$realFile || !str_starts_with($realFile, $realPublic)) {
            http_response_code(403);
            echo "Forbidden";
            exit;
        }

        // Content-Type básico
        $ext = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'css'  => 'text/css; charset=utf-8',
            'js'   => 'application/javascript; charset=utf-8',
            'png'  => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'webp' => 'image/webp',
            'ico'  => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2'=> 'font/woff2',
            'ttf'  => 'font/ttf',
            'map'  => 'application/json; charset=utf-8',
            default => 'application/octet-stream',
        };

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($realFile));
        header('Cache-Control: no-cache');

        readfile($realFile);
        exit;
    }
}

// No es archivo estático: delega a la app
require $publicDir . DIRECTORY_SEPARATOR . 'index.php';