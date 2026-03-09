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

        // Auth
        $router->get('/login', [\Erp2\Controller\AuthController::class, 'loginForm']);
        $router->post('/login', [\Erp2\Controller\AuthController::class, 'login']);
        $router->get('/logout', [\Erp2\Controller\AuthController::class, 'logout']);

        // Base
        $router->get('/', [\Erp2\Controller\HomeController::class, 'index']);
        $router->get('/health', [\Erp2\Controller\HealthController::class, 'index']);

        // Cartera (nuevo)
        $router->get('/cartera', [\Erp2\Controller\CarteraController::class, 'index']);

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

        // Compras
        $router->get('/compras', [\Erp2\Controller\ComprasController::class, 'index']);
        $router->get('/compras/crear', [\Erp2\Controller\ComprasController::class, 'createForm']);
        $router->post('/compras/crear', [\Erp2\Controller\ComprasController::class, 'create']);
        $router->get('/compras/{id}', [\Erp2\Controller\ComprasController::class, 'show']);
        $router->post('/compras/{id}/emitir', [\Erp2\Controller\ComprasController::class, 'emitir']);
        $router->post('/compras/{id}/anular', [\Erp2\Controller\ComprasController::class, 'anular']);

        // Pagos
        $router->get('/pagos', [\Erp2\Controller\PagosController::class, 'index']);
        $router->get('/pagos/crear', [\Erp2\Controller\PagosController::class, 'createForm']);
        $router->post('/pagos/crear', [\Erp2\Controller\PagosController::class, 'create']);
        $router->post('/pagos/{id}/eliminar', [\Erp2\Controller\PagosController::class, 'delete']);

        // Seguridad: Usuarios / Roles / Permisos (lectura + CRUD mínimo solo admin id=1)
        // Auditoria (lectura)
        $router->get('/auditoria', [\Erp2\Controller\AuditoriaController::class, 'index']);
        $router->get('/auditoria/{id}', [\Erp2\Controller\AuditoriaController::class, 'show']);

        // Permisos (lectura)
        $router->get('/permisos', [\Erp2\Controller\PermisosController::class, 'index']);

        // ✅ NUEVO: Permisos CRUD (solo admin id=1) — IMPORTANTE: antes de cualquier /permisos/{id} si existiera
        $router->get('/permisos/crear', [\Erp2\Controller\PermisosController::class, 'createForm']);
        $router->post('/permisos/crear', [\Erp2\Controller\PermisosController::class, 'create']);
        $router->get('/permisos/{id}/editar', [\Erp2\Controller\PermisosController::class, 'editForm']);
        $router->post('/permisos/{id}/editar', [\Erp2\Controller\PermisosController::class, 'update']);
        $router->post('/permisos/{id}/eliminar', [\Erp2\Controller\PermisosController::class, 'delete']);

        // Roles (lectura)
        $router->get('/roles', [\Erp2\Controller\RolesController::class, 'index']);

        // ✅ NUEVO: Roles CRUD (solo admin id=1) — IMPORTANTE: antes de /roles/{id}
        $router->get('/roles/crear', [\Erp2\Controller\RolesController::class, 'createForm']);
        $router->post('/roles/crear', [\Erp2\Controller\RolesController::class, 'create']);
        $router->get('/roles/{id}/editar', [\Erp2\Controller\RolesController::class, 'editForm']);
        $router->post('/roles/{id}/editar', [\Erp2\Controller\RolesController::class, 'update']);

        // Roles (detalle lectura)
        $router->get('/roles/{id}', [\Erp2\Controller\RolesController::class, 'show']);

        // Usuarios (lectura)
        $router->get('/usuarios', [\Erp2\Controller\UsuariosController::class, 'index']);

        // ✅ NUEVO: Usuarios CRUD (solo admin id=1) — IMPORTANTE: antes de /usuarios/{id}
        $router->get('/usuarios/crear', [\Erp2\Controller\UsuariosController::class, 'createForm']);
        $router->post('/usuarios/crear', [\Erp2\Controller\UsuariosController::class, 'create']);
        $router->get('/usuarios/{id}/editar', [\Erp2\Controller\UsuariosController::class, 'editForm']);
        $router->post('/usuarios/{id}/editar', [\Erp2\Controller\UsuariosController::class, 'update']);

        // Usuarios (detalle lectura)
        $router->get('/usuarios/{id}', [\Erp2\Controller\UsuariosController::class, 'show']);

        return new self($router);
    }

    public function run(): void
    {
        $this->router->dispatch();
    }
}