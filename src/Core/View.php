<?php
declare(strict_types=1);

namespace Erpia2\Core;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        $viewsRoot = dirname(__DIR__, 2) . '/views';
        $file = $viewsRoot . '/' . ltrim($view, '/') . '.php';

        if (!is_file($file)) {
            http_response_code(500);
            echo "View not found: " . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
            return;
        }

        extract($data, EXTR_SKIP);
        require $file;
    }
}