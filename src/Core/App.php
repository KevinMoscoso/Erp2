<?php
declare(strict_types=1);

namespace Erp2\Core;

use Dotenv\Dotenv;

final class App
{
    private Router $router;

    private function __construct(Router $router)
    {
        $this->router = $router;
    }

    public static function bootstrap(): self
    {
        // Cargar .env si existe
        $root = dirname(__DIR__, 2);
        if (is_file($root . '/.env')) {
            Dotenv::createImmutable($root)->safeLoad();
        }

        $router = new Router();

        // Auth (público)
        $router->get('/login', [\Erp2\Controller\AuthController::class, 'loginForm']);
        $router->post('/login', [\Erp2\Controller\AuthController::class, 'login']);
        $router->get('/logout', [\Erp2\Controller\AuthController::class, 'logout']);

        // Rutas mínimas (salud + home)
        $router->get('/', [\Erp2\Controller\HomeController::class, 'index']);
        $router->get('/health', [\Erp2\Controller\HealthController::class, 'index']);

        return new self($router);
    }

    public function run(): void
    {
        $this->router->dispatch();
    }
}