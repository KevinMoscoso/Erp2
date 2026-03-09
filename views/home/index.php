<?php
declare(strict_types=1);

use Erp2\Core\Auth;
use Erp2\Core\Database;

$title = 'Dashboard';

require __DIR__ . '/../partials/app_shell_top.php';

// RBAC seguro
$can = static function (string $perm): bool {
    return (class_exists(Auth::class) && method_exists(Auth::class, 'has')) ? Auth::has($perm) : false;
};

$today = date('Y-m-d');

// KPIs (default)
$kpiFacturas = '—';
$kpiCompras  = '—';
$kpiPagos    = '—';
$kpiCartera  = '—';

$metaFacturas = 'Requiere permiso facturas.ver';
$metaCompras  = 'Requiere permiso compras.ver';
$metaPagos    = 'Requiere permiso pagos.ver';
$metaCartera  = 'Requiere permiso cartera.ver';

$fmtMoney = static function ($n): string {
    return number_format((float)$n, 2, '.', '');
};

try {
    if (class_exists(Database::class) && method_exists(Database::class, 'pdo')) {
        $pdo = Database::pdo();

        // ===== Facturas (hoy): total emitido hoy =====
        if ($can('facturas.ver')) {
            $st = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM facturas WHERE fecha = :d AND estado = 'emitida'");
            $st->execute([':d' => $today]);
            $sumEmit = (float)$st->fetchColumn();

            $st2 = $pdo->prepare("SELECT COUNT(*) FROM facturas WHERE fecha = :d AND estado = 'emitida'");
            $st2->execute([':d' => $today]);
            $cntEmit = (int)$st2->fetchColumn();

            $kpiFacturas = $fmtMoney($sumEmit);
            $metaFacturas = "Emitidas hoy: {$cntEmit}";
        }

        // ===== Compras (hoy): total emitido hoy =====
        if ($can('compras.ver')) {
            $st = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM compras WHERE fecha = :d AND estado = 'emitida'");
            $st->execute([':d' => $today]);
            $sumEmit = (float)$st->fetchColumn();

            $st2 = $pdo->prepare("SELECT COUNT(*) FROM compras WHERE fecha = :d AND estado = 'emitida'");
            $st2->execute([':d' => $today]);
            $cntEmit = (int)$st2->fetchColumn();

            $kpiCompras = $fmtMoney($sumEmit);
            $metaCompras = "Emitidas hoy: {$cntEmit}";
        }

        // ===== Pagos (hoy): total pagado hoy =====
        if ($can('pagos.ver')) {
            $st = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM pagos WHERE fecha = :d");
            $st->execute([':d' => $today]);
            $sum = (float)$st->fetchColumn();

            $st2 = $pdo->prepare("SELECT COUNT(*) FROM pagos WHERE fecha = :d");
            $st2->execute([':d' => $today]);
            $cnt = (int)$st2->fetchColumn();

            $kpiPagos = $fmtMoney($sum);
            $metaPagos = "Pagos hoy: {$cnt}";
        }

        // ===== Cartera: saldo neto (CXC - CXP) =====
        if ($can('cartera.ver')) {
            // saldo CXC = sum(total - pagado) de facturas emitidas
            $sqlCxc = "
                SELECT COALESCE(SUM(GREATEST(f.total - COALESCE(p.pagado,0), 0)),0) AS saldo
                FROM facturas f
                LEFT JOIN (
                    SELECT ref_id, SUM(monto) AS pagado
                    FROM pagos
                    WHERE tipo_ref = 'factura'
                    GROUP BY ref_id
                ) p ON p.ref_id = f.id
                WHERE f.estado = 'emitida'
            ";
            $cxc = $pdo->query($sqlCxc);
            $saldoCxc = $cxc ? (float)$cxc->fetchColumn() : 0.0;

            // saldo CXP = sum(total - pagado) de compras emitidas
            $sqlCxp = "
                SELECT COALESCE(SUM(GREATEST(c.total - COALESCE(p.pagado,0), 0)),0) AS saldo
                FROM compras c
                LEFT JOIN (
                    SELECT ref_id, SUM(monto) AS pagado
                    FROM pagos
                    WHERE tipo_ref = 'compra'
                    GROUP BY ref_id
                ) p ON p.ref_id = c.id
                WHERE c.estado = 'emitida'
            ";
            $cxp = $pdo->query($sqlCxp);
            $saldoCxp = $cxp ? (float)$cxp->fetchColumn() : 0.0;

            $neto = $saldoCxc - $saldoCxp;

            $kpiCartera = $fmtMoney($neto);
            $metaCartera = 'CXC: ' . $fmtMoney($saldoCxc) . ' | CXP: ' . $fmtMoney($saldoCxp);
        }
    }
} catch (\Throwable $e) {
    error_log('[home.kpis] ' . $e->getMessage());
}
?>
<div class="dashboard-grid">
  <div class="card stat-card">
    <div class="card-title">Facturas (hoy)</div>
    <div class="card-value"><?= htmlspecialchars((string)$kpiFacturas, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="card-meta"><?= htmlspecialchars((string)$metaFacturas, ENT_QUOTES, 'UTF-8') ?></div>
  </div>

  <div class="card stat-card">
    <div class="card-title">Compras (hoy)</div>
    <div class="card-value"><?= htmlspecialchars((string)$kpiCompras, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="card-meta"><?= htmlspecialchars((string)$metaCompras, ENT_QUOTES, 'UTF-8') ?></div>
  </div>

  <div class="card stat-card">
    <div class="card-title">Pagos (hoy)</div>
    <div class="card-value"><?= htmlspecialchars((string)$kpiPagos, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="card-meta"><?= htmlspecialchars((string)$metaPagos, ENT_QUOTES, 'UTF-8') ?></div>
  </div>

  <div class="card stat-card">
    <div class="card-title">Cartera</div>
    <div class="card-value"><?= htmlspecialchars((string)$kpiCartera, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="card-meta"><?= htmlspecialchars((string)$metaCartera, ENT_QUOTES, 'UTF-8') ?></div>
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