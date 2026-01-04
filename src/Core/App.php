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

        // Base
        $router->get('/', [\Erp2\Controller\HomeController::class, 'index']);
        $router->get('/health', [\Erp2\Controller\HealthController::class, 'index']);

        // Módulo: Terceros / Contactos (todas requieren login dentro del controller)
        $router->get('/terceros', [\Erp2\Controller\TercerosController::class, 'index']);
        $router->get('/terceros/crear', [\Erp2\Controller\TercerosController::class, 'createForm']);
        $router->post('/terceros/crear', [\Erp2\Controller\TercerosController::class, 'create']);

        $router->get('/terceros/{id}', [\Erp2\Controller\TercerosController::class, 'show']);
        $router->get('/terceros/{id}/editar', [\Erp2\Controller\TercerosController::class, 'editForm']);
        $router->post('/terceros/{id}/editar', [\Erp2\Controller\TercerosController::class, 'update']);
        $router->post('/terceros/{id}/eliminar', [\Erp2\Controller\TercerosController::class, 'delete']);

        // Contactos
        $router->post('/terceros/{id}/contactos/crear', [\Erp2\Controller\TercerosController::class, 'createContacto']);
        $router->post('/terceros/{id}/contactos/{cid}/eliminar', [\Erp2\Controller\TercerosController::class, 'deleteContacto']);

        return new self($router);
    }

    public function run(): void
    {
        $this->router->dispatch();
    }
}