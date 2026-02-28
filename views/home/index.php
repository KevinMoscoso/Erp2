<?php
declare(strict_types=1);

use Erp2\Core\Auth;

$title = 'Dashboard';

require __DIR__ . '/../partials/app_shell_top.php';

// RBAC seguro para Home (si no se puede comprobar, no mostramos)
$can = static function (string $perm): bool {
    return (class_exists(Auth::class) && method_exists(Auth::class, 'has')) ? Auth::has($perm) : false;
};

// Placeholders (no asumimos variables del controlador)
$kpiFacturas = '—';
$kpiCompras  = '—';
$kpiPagos    = '—';
$kpiCartera  = '—';
?>
<div class="dashboard-grid">
  <div class="card stat-card">
    <div class="card-title">Facturas (hoy)</div>
    <div class="card-value"><?= htmlspecialchars((string)$kpiFacturas, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="card-meta">Indicador demo (validar fuente real en implementación)</div>
  </div>

  <div class="card stat-card">
    <div class="card-title">Compras (hoy)</div>
    <div class="card-value"><?= htmlspecialchars((string)$kpiCompras, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="card-meta">Indicador demo (validar fuente real en implementación)</div>
  </div>

  <div class="card stat-card">
    <div class="card-title">Pagos (hoy)</div>
    <div class="card-value"><?= htmlspecialchars((string)$kpiPagos, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="card-meta">Indicador demo (validar fuente real en implementación)</div>
  </div>

  <div class="card stat-card">
    <div class="card-title">Cartera</div>
    <div class="card-value"><?= htmlspecialchars((string)$kpiCartera, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="card-meta">Pendiente / Parcial / Pagado</div>
  </div>
</div>

<div class="section">
  <div class="section-header">
    <h3>Accesos rápidos</h3>
    <span class="muted">Accede a los módulos según tus permisos</span>
  </div>

  <div class="quick-grid">
    <?php if ($can('terceros.ver')): ?>
      <div class="card quick-card">
        <h4>Gestión de Terceros</h4>
        <p>Clientes, proveedores y contactos asociados.</p>
        <div class="quick-actions">
          <a class="btn btn-primary" href="/terceros">Abrir</a>
          <?php if ($can('terceros.crear')): ?>
            <a class="btn btn-secondary" href="/terceros/crear">Nuevo</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($can('productos.ver')): ?>
      <div class="card quick-card">
        <h4>Productos / Servicios</h4>
        <p>Catálogo y configuración de precios.</p>
        <div class="quick-actions">
          <a class="btn btn-primary" href="/productos">Abrir</a>
          <?php if ($can('productos.crear')): ?>
            <a class="btn btn-secondary" href="/productos/crear">Nuevo</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($can('facturas.ver')): ?>
      <div class="card quick-card">
        <h4>Facturación</h4>
        <p>Crear, emitir y controlar facturas.</p>
        <div class="quick-actions">
          <a class="btn btn-primary" href="/facturas">Abrir</a>
          <?php if ($can('facturas.crear')): ?>
            <a class="btn btn-secondary" href="/facturas/crear">Nueva</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($can('compras.ver')): ?>
      <div class="card quick-card">
        <h4>Compras</h4>
        <p>Registro de compras y control de costos.</p>
        <div class="quick-actions">
          <a class="btn btn-primary" href="/compras">Abrir</a>
          <?php if ($can('compras.crear')): ?>
            <a class="btn btn-secondary" href="/compras/crear">Nueva</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($can('pagos.ver')): ?>
      <div class="card quick-card">
        <h4>Pagos</h4>
        <p>Registrar pagos sobre documentos emitidos.</p>
        <div class="quick-actions">
          <a class="btn btn-primary" href="/pagos">Abrir</a>
          <?php if ($can('pagos.crear')): ?>
            <a class="btn btn-secondary" href="/pagos/crear">Registrar</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($can('inventario.ver')): ?>
      <div class="card quick-card">
        <h4>Inventario</h4>
        <p>Stock, movimientos y kardex.</p>
        <div class="quick-actions">
          <a class="btn btn-primary" href="/inventario">Abrir</a>
          <?php if ($can('inventario.ajustar')): ?>
            <a class="btn btn-secondary" href="/inventario/ajustar">Ajuste</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($can('cartera.ver')): ?>
      <div class="card quick-card">
        <h4>Cartera</h4>
        <p>Estados: pendiente, parcial y pagado.</p>
        <div class="quick-actions">
          <a class="btn btn-primary" href="/cartera">Abrir</a>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($can('auditoria.ver')): ?>
      <div class="card quick-card">
        <h4>Auditoría</h4>
        <p>Registro de acciones y trazabilidad.</p>
        <div class="quick-actions">
          <a class="btn btn-primary" href="/auditoria">Abrir</a>
        </div>
      </div>
    <?php endif; ?>

    <?php if (isset($userId) && (int)$userId === 1): ?>
      <div class="card quick-card">
        <h4>Seguridad</h4>
        <p>Usuarios, roles y permisos (solo admin id=1).</p>
        <div class="quick-actions">
          <a class="btn btn-primary" href="/usuarios">Usuarios</a>
          <a class="btn btn-secondary" href="/roles">Roles</a>
          <a class="btn btn-secondary" href="/permisos">Permisos</a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>