<?php
declare(strict_types=1);

$title = 'Kardex / Movimientos';
require __DIR__ . '/../partials/app_shell_top.php';

$producto = is_array($producto ?? null) ? $producto : [];
$movimientos = $movimientos ?? [];

$pid = (int)($producto['id'] ?? 0);
$tipo = (string)($producto['tipo'] ?? '');

$accionVal = (string)old('accion', 'sumar');
$cantidadVal = (string)old('cantidad', '');
$notaVal = (string)old('nota', '');

$esProducto = ($tipo === 'producto');

$tipoBadge = $esProducto ? 'badge-success' : 'badge-muted';
$tipoTxt = $esProducto ? 'Producto' : (($tipo !== '') ? $tipo : '—');

$estadoRaw = (int)($producto['estado'] ?? 1);
$estadoTxt = ($estadoRaw === 1) ? 'Activo' : 'Inactivo';
$estadoClass = ($estadoRaw === 1) ? 'badge-success' : 'badge-danger';

$stockTxt = (string)($producto['stock_actual'] ?? '0.00');
?>
<div class="card" style="padding:16px;">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Kardex</h3>
    <div class="table-actions">
      <a class="btn btn-secondary" href="/inventario">Volver</a>
    </div>
  </div>

  <div class="kv-grid">
    <div class="kv">
      <div class="k">Producto</div>
      <div class="v">
        <div class="table-actions">
          <span class="badge <?= $tipoBadge ?>"><?= htmlspecialchars($tipoTxt, ENT_QUOTES, 'UTF-8') ?></span>
          <span class="badge <?= $estadoClass ?>"><?= htmlspecialchars($estadoTxt, ENT_QUOTES, 'UTF-8') ?></span>
          <?php if ($esProducto): ?>
            <span class="badge badge-muted">Mueve stock</span>
          <?php else: ?>
            <span class="badge badge-muted">No mueve stock</span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="kv">
      <div class="k">ID</div>
      <div class="v"><?= htmlspecialchars((string)$pid, ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="kv">
      <div class="k">Referencia</div>
      <div class="v"><?= htmlspecialchars((string)($producto['referencia'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="kv">
      <div class="k">Nombre</div>
      <div class="v"><?= htmlspecialchars((string)($producto['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="kv">
      <div class="k">Stock actual</div>
      <div class="v"><?= htmlspecialchars($stockTxt, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
  </div>
</div>

<?php if (\Erp2\Core\Auth::has('inventario.ajustar') && $tipo === 'producto'): ?>
  <div class="card" style="padding:16px; margin-top:14px;">
    <div class="section-header" style="margin-bottom:10px;">
      <h3>Ajuste manual</h3>
      <span class="muted">Solo productos</span>
    </div>

    <?php if (err('form')): ?>
      <div class="alert alert-error" role="alert">
        <?= htmlspecialchars((string)err('form'), ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <form method="post" action="/inventario/<?= $pid ?>/ajustar">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">

      <div class="form-grid" style="margin-top:0;">
        <div>
          <label for="accion">Acción</label>
          <select id="accion" name="accion" class="input <?= hasErr('accion') ? 'error' : '' ?>" style="width:100%;">
            <option value="sumar" <?= $accionVal === 'sumar' ? 'selected' : '' ?>>Sumar</option>
            <option value="restar" <?= $accionVal === 'restar' ? 'selected' : '' ?>>Restar</option>
          </select>
          <?php if (err('accion')): ?>
            <div class="field-error"><?= htmlspecialchars((string)err('accion'), ENT_QUOTES, 'UTF-8') ?></div>
          <?php endif; ?>
        </div>

        <div>
          <label for="cantidad">Cantidad</label>
          <input
            id="cantidad"
            type="number"
            step="0.01"
            min="0.01"
            name="cantidad"
            required
            value="<?= htmlspecialchars($cantidadVal, ENT_QUOTES, 'UTF-8') ?>"
            class="input <?= hasErr('cantidad') ? 'error' : '' ?>"
            style="width:100%;"
          >
          <?php if (err('cantidad')): ?>
            <div class="field-error"><?= htmlspecialchars((string)err('cantidad'), ENT_QUOTES, 'UTF-8') ?></div>
          <?php endif; ?>
        </div>

        <div style="grid-column: 1 / -1;">
          <label for="nota">Nota (opcional)</label>
          <input
            id="nota"
            type="text"
            name="nota"
            maxlength="255"
            value="<?= htmlspecialchars($notaVal, ENT_QUOTES, 'UTF-8') ?>"
            class="input <?= hasErr('nota') ? 'error' : '' ?>"
            style="width:100%;"
          >
          <?php if (err('nota')): ?>
            <div class="field-error"><?= htmlspecialchars((string)err('nota'), ENT_QUOTES, 'UTF-8') ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-actions">
        <button class="btn btn-primary" type="submit">Aplicar ajuste</button>
      </div>
    </form>
  </div>
<?php endif; ?>

<div class="card" style="padding:16px; margin-top:14px;">
  <div class="section-header" style="margin-bottom:10px;">
    <h3>Movimientos</h3>
    <span class="muted">Histórico del producto</span>
  </div>

  <div class="table-container" style="margin-top:0;">
    <table class="table">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Tipo</th>
          <th>Cantidad</th>
          <th>Saldo anterior</th>
          <th>Saldo nuevo</th>
          <th>Ref</th>
          <th>Nota</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($movimientos as $m): ?>
          <?php
            $tipoMov = (string)($m['tipo'] ?? '');
            $badgeMov = 'badge-muted';
            if ($tipoMov === 'entrada') $badgeMov = 'badge-success';
            elseif ($tipoMov === 'salida') $badgeMov = 'badge-danger';
            elseif ($tipoMov === 'ajuste') $badgeMov = 'badge';

            $refTipo = (string)($m['referencia_tipo'] ?? '');
            $refId = (int)($m['referencia_id'] ?? 0);
          ?>
          <tr>
            <td><?= htmlspecialchars((string)($m['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="badge <?= $badgeMov ?>"><?= htmlspecialchars($tipoMov, ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><?= htmlspecialchars((string)($m['cantidad'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($m['saldo_anterior'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td style="font-weight:900;"><?= htmlspecialchars((string)($m['saldo_nuevo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?php if ($refTipo !== ''): ?>
                <span class="badge badge-muted"><?= htmlspecialchars($refTipo, ENT_QUOTES, 'UTF-8') ?></span>
              <?php endif; ?>
              <?php if ($refId > 0): ?>
                <span class="muted">#<?= (int)$refId ?></span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars((string)($m['nota'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($movimientos)): ?>
          <tr><td colspan="7">Sin movimientos.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>