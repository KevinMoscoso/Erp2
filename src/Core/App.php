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

        // Rutas mínimas (salud + home)
        $router->get('/', [\Erpia2\Controller\HomeController::class, 'index']);
        $router->get('/health', [\Erpia2\Controller\HealthController::class, 'index']);

        return new self($router);
    }

    public function run(): void
    {
        $this->router->dispatch();
    }
}