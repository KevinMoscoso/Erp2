<?php
declare(strict_types=1);

use Erp2\Core\Auth;

$title = 'Detalle de Producto/Servicio';
require __DIR__ . '/../partials/app_shell_top.php';

$p = is_array($producto ?? null) ? (array)$producto : [];
$id = (int)($p['id'] ?? 0);

$tipo = (string)($p['tipo'] ?? '');
$tipoClass = 'badge-muted';
$tipoLabel = $tipo !== '' ? $tipo : '—';
$movTxt = '—';

if ($tipo === 'producto') { $tipoClass = 'badge-success'; $tipoLabel = 'Producto'; $movTxt = 'Mueve stock'; }
elseif ($tipo === 'servicio') { $tipoClass = 'badge-muted'; $tipoLabel = 'Servicio'; $movTxt = 'No mueve stock'; }

$stockTxt = null;
if (array_key_exists('stock_actual', $p) && $p['stock_actual'] !== null && $p['stock_actual'] !== '') {
  $stockTxt = (string)$p['stock_actual'];
}
?>

<div class="card" style="padding:16px;">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Resumen</h3>
    <div class="table-actions">
      <a class="btn btn-secondary" href="/productos">Volver</a>

      <?php if (Auth::has('productos.editar')): ?>
        <a class="btn btn-primary" href="/productos/<?= $id ?>/editar">✏️ Editar</a>
      <?php endif; ?>

      <?php if (Auth::has('productos.eliminar')): ?>
        <form method="post" action="/productos/<?= $id ?>/eliminar" style="display:inline;">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          <button class="btn btn-danger" type="submit">Eliminar</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="kv-grid">
    <div class="kv">
      <div class="k">ID</div>
      <div class="v"><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="kv">
      <div class="k">Tipo</div>
      <div class="v">
        <div class="table-actions">
          <span class="badge <?= $tipoClass ?>"><?= htmlspecialchars($tipoLabel, ENT_QUOTES, 'UTF-8') ?></span>
          <span class="badge badge-muted"><?= htmlspecialchars($movTxt, ENT_QUOTES, 'UTF-8') ?></span>
          <?php if ($tipo === 'producto' && $stockTxt !== null): ?>
            <span class="badge badge-muted">Stock: <?= htmlspecialchars($stockTxt, ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="kv">
      <div class="k">Referencia</div>
      <div class="v"><?= htmlspecialchars((string)($p['referencia'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="kv">
      <div class="k">Nombre</div>
      <div class="v"><?= htmlspecialchars((string)($p['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="kv" style="grid-column: 1 / -1;">
      <div class="k">Descripción</div>
      <div class="v" style="font-weight:700;"><?= htmlspecialchars((string)($p['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="kv">
      <div class="k">Precio venta</div>
      <div class="v"><?= htmlspecialchars((string)($p['precio_venta'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="kv">
      <div class="k">Costo</div>
      <div class="v"><?= htmlspecialchars((string)($p['costo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <?php if (array_key_exists('estado', $p)): ?>
      <?php
        $estado = (string)($p['estado'] ?? '');
        $isActive = ($estado === '1' || $estado === 'activo' || $estado === 'A');
      ?>
      <div class="kv">
        <div class="k">Estado</div>
        <div class="v">
          <?php if ($estado !== ''): ?>
            <span class="badge <?= $isActive ? 'badge-success' : 'badge-danger' ?>">
              <?= htmlspecialchars($isActive ? 'Activo' : 'Inactivo', ENT_QUOTES, 'UTF-8') ?>
            </span>
          <?php else: ?>
            <span class="badge badge-muted">—</span>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>