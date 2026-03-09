<?php
declare(strict_types=1);

use Erp2\Core\Auth;

$title = 'Inventario';
require __DIR__ . '/../partials/app_shell_top.php';

$productos = $productos ?? [];
$qVal = (string)($q ?? '');
?>
<div class="card" style="padding:16px;">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Productos con stock</h3>
    <div class="table-actions">
      <?php if (Auth::has('inventario.ajustar')): ?>
        <span class="badge badge-muted">Ajustes desde Kardex</span>
      <?php endif; ?>
    </div>
  </div>

  <form method="get" action="/inventario" style="margin-top:12px;">
    <div class="kv-grid" style="margin-top:0;">
      <div class="kv">
        <div class="k">Buscar</div>
        <div class="v" style="font-weight:600;">
          <input
            class="input"
            type="text"
            name="q"
            value="<?= htmlspecialchars($qVal, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="referencia o nombre…"
            style="width:100%;"
          >
        </div>
      </div>

      <div class="kv">
        <div class="k">Acciones</div>
        <div class="v" style="font-weight:600;">
          <div class="table-actions">
            <button class="btn btn-primary" type="submit">Buscar</button>
            <a class="btn btn-secondary" href="/inventario">Limpiar</a>
          </div>
        </div>
      </div>
    </div>
  </form>

  <div class="table-container">
    <table class="table" style="min-width: 860px;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Tipo</th>
          <th>Referencia</th>
          <th>Nombre</th>
          <th>Stock actual</th>
          <th>Estado</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($productos as $p): ?>
          <?php
            $id = (int)($p['id'] ?? 0);
            $tipo = (string)($p['tipo'] ?? '');
            $esProducto = ($tipo === 'producto');

            $stockLabel = $esProducto ? (string)($p['stock_actual'] ?? '0.00') : '—';

            $estadoRaw = (int)($p['estado'] ?? 1);
            $estadoTxt = ($estadoRaw === 1) ? 'Activo' : 'Inactivo';
            $estadoClass = ($estadoRaw === 1) ? 'badge-success' : 'badge-danger';

            $tipoBadge = $esProducto ? 'badge-success' : 'badge-muted';
            $tipoTxt = $esProducto ? 'Producto' : (($tipo !== '') ? $tipo : '—');
          ?>
          <tr>
            <td><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <div class="table-actions">
                <span class="badge <?= $tipoBadge ?>"><?= htmlspecialchars($tipoTxt, ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!$esProducto): ?>
                  <span class="muted">No mueve stock</span>
                <?php endif; ?>
              </div>
            </td>
            <td><?= htmlspecialchars((string)($p['referencia'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($p['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($stockLabel, ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="badge <?= $estadoClass ?>"><?= htmlspecialchars($estadoTxt, ENT_QUOTES, 'UTF-8') ?></span></td>
            <td>
              <?php if (Auth::has('inventario.ver')): ?>
                <?php if ($esProducto): ?>
                  <a class="btn btn-secondary" href="/inventario/<?= $id ?>">Ver Kardex</a>
                <?php else: ?>
                  <span class="badge badge-muted">(sin kardex)</span>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($productos)): ?>
          <tr><td colspan="7">Sin resultados.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>