<?php
declare(strict_types=1);

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((string)($_SERVER['SERVER_PORT'] ?? '') === '443');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'use_strict_mode' => true,
        'use_only_cookies' => true,
        'cookie_httponly' => true,
        'cookie_secure' => $secure,
        'cookie_samesite' => 'Lax',
    ]);
}

require __DIR__ . '/../vendor/autoload.php';

use Erp2\Core\App;

$app = App::bootstrap();
$app->run();