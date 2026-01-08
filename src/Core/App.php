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

        // Terceros / Contactos
        $router->get('/terceros', [\Erp2\Controller\TercerosController::class, 'index']);
        $router->get('/terceros/crear', [\Erp2\Controller\TercerosController::class, 'createForm']);
        $router->post('/terceros/crear', [\Erp2\Controller\TercerosController::class, 'create']);
        $router->get('/terceros/{id}', [\Erp2\Controller\TercerosController::class, 'show']);
        $router->get('/terceros/{id}/editar', [\Erp2\Controller\TercerosController::class, 'editForm']);
        $router->post('/terceros/{id}/editar', [\Erp2\Controller\TercerosController::class, 'update']);
        $router->post('/terceros/{id}/eliminar', [\Erp2\Controller\TercerosController::class, 'delete']);
        $router->post('/terceros/{id}/contactos/crear', [\Erp2\Controller\TercerosController::class, 'createContacto']);
        $router->post('/terceros/{id}/contactos/{cid}/eliminar', [\Erp2\Controller\TercerosController::class, 'deleteContacto']);

        // Productos / Servicios
        $router->get('/productos', [\Erp2\Controller\ProductosController::class, 'index']);
        $router->get('/productos/crear', [\Erp2\Controller\ProductosController::class, 'createForm']);
        $router->post('/productos/crear', [\Erp2\Controller\ProductosController::class, 'create']);
        $router->get('/productos/{id}', [\Erp2\Controller\ProductosController::class, 'show']);
        $router->get('/productos/{id}/editar', [\Erp2\Controller\ProductosController::class, 'editForm']);
        $router->post('/productos/{id}/editar', [\Erp2\Controller\ProductosController::class, 'update']);
        $router->post('/productos/{id}/eliminar', [\Erp2\Controller\ProductosController::class, 'delete']);

        // Facturas
        $router->get('/facturas', [\Erp2\Controller\FacturasController::class, 'index']);
        $router->get('/facturas/crear', [\Erp2\Controller\FacturasController::class, 'createForm']);
        $router->post('/facturas/crear', [\Erp2\Controller\FacturasController::class, 'create']);
        $router->get('/facturas/{id}', [\Erp2\Controller\FacturasController::class, 'show']);
        $router->post('/facturas/{id}/emitir', [\Erp2\Controller\FacturasController::class, 'emitir']);
        $router->post('/facturas/{id}/anular', [\Erp2\Controller\FacturasController::class, 'anular']);

        // Inventario
        $router->get('/inventario', [\Erp2\Controller\InventarioController::class, 'index']);
        $router->get('/inventario/{id}', [\Erp2\Controller\InventarioController::class, 'show']);
        $router->post('/inventario/{id}/ajustar', [\Erp2\Controller\InventarioController::class, 'ajustar']);

        // Compras (nuevo)
        $router->get('/compras', [\Erp2\Controller\ComprasController::class, 'index']);
        $router->get('/compras/crear', [\Erp2\Controller\ComprasController::class, 'createForm']);
        $router->post('/compras/crear', [\Erp2\Controller\ComprasController::class, 'create']);
        $router->get('/compras/{id}', [\Erp2\Controller\ComprasController::class, 'show']);
        $router->post('/compras/{id}/emitir', [\Erp2\Controller\ComprasController::class, 'emitir']);
        $router->post('/compras/{id}/anular', [\Erp2\Controller\ComprasController::class, 'anular']);

        return new self($router);
    }

    public function run(): void
    {
        $this->router->dispatch();
    }
}