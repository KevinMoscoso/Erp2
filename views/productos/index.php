<?php
declare(strict_types=1);

use Erp2\Core\Auth;

$title = 'Productos / Servicios';
require __DIR__ . '/../partials/app_shell_top.php';

$items = $items ?? [];
$qVal = (string)($q ?? '');
?>
<div class="card" style="padding:16px;">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Listado</h3>
    <div class="table-actions">
      <?php if (Auth::has('productos.crear')): ?>
        <a class="btn btn-primary" href="/productos/crear">➕ Nuevo</a>
      <?php endif; ?>
    </div>
  </div>

  <form method="get" action="/productos" style="margin-top:12px;">
    <div class="kv-grid" style="margin-top:0;">
      <div class="kv">
        <div class="k">Buscar</div>
        <div class="v" style="font-weight:600;">
          <input
            id="q"
            class="input"
            name="q"
            value="<?= htmlspecialchars($qVal, ENT_QUOTES, 'UTF-8') ?>"
            maxlength="160"
            placeholder="referencia / nombre…"
            style="width:100%;"
          >
        </div>
      </div>

      <div class="kv">
        <div class="k">Acciones</div>
        <div class="v" style="font-weight:600;">
          <div class="table-actions">
            <button class="btn btn-primary" type="submit">Buscar</button>
            <a class="btn btn-secondary" href="/productos">Limpiar</a>
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
          <th>Precio venta</th>
          <th>Costo</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $p): ?>
          <?php
            $id = (int)($p['id'] ?? 0);
            $tipo = (string)($p['tipo'] ?? '');
            $ref = (string)($p['referencia'] ?? '');
            $nombre = (string)($p['nombre'] ?? '');
            $precio = (string)($p['precio_venta'] ?? '');
            $costo = (string)($p['costo'] ?? '');

            $tipoClass = 'badge-muted';
            $tipoLabel = $tipo !== '' ? $tipo : '—';
            if ($tipo === 'producto') { $tipoClass = 'badge-success'; $tipoLabel = 'Producto'; }
            elseif ($tipo === 'servicio') { $tipoClass = 'badge-muted'; $tipoLabel = 'Servicio'; }

            $stockTxt = null;
            if (array_key_exists('stock_actual', $p) && $p['stock_actual'] !== null && $p['stock_actual'] !== '') {
              $stockTxt = (string)$p['stock_actual'];
            }
          ?>
          <tr>
            <td><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <div class="table-actions">
                <span class="badge <?= $tipoClass ?>"><?= htmlspecialchars($tipoLabel, ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($tipo === 'producto' && $stockTxt !== null): ?>
                  <span class="badge badge-muted">Stock: <?= htmlspecialchars($stockTxt, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <?php if ($tipo === 'servicio'): ?>
                  <span class="muted">No mueve stock</span>
                <?php endif; ?>
              </div>
            </td>
            <td>
              <a class="link" href="/productos/<?= $id ?>">
                <?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?>
              </a>
            </td>
            <td><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($precio, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($costo, ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <div class="table-actions">
                <a class="btn btn-secondary" href="/productos/<?= $id ?>">Ver</a>

                <?php if (Auth::has('productos.editar')): ?>
                  <a class="btn btn-secondary" href="/productos/<?= $id ?>/editar">Editar</a>
                <?php endif; ?>

                <?php if (Auth::has('productos.eliminar')): ?>
                  <form method="post" action="/productos/<?= $id ?>/eliminar" style="display:inline;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <button class="btn btn-danger" type="submit">Eliminar</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($items)): ?>
          <tr><td colspan="7">Sin resultados.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>