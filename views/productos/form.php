<?php
declare(strict_types=1);

$title = (string)($title ?? 'Producto/Servicio');
require __DIR__ . '/../partials/app_shell_top.php';

$p = is_array($producto ?? null) ? (array)$producto : [];

$modeVal = (string)($mode ?? '');
$isEdit = ($modeVal === 'edit') || (!empty($p['id']));

$tipoDefault = (string)($p['tipo'] ?? 'producto');
$refDefault = (string)($p['referencia'] ?? '');
$nombreDefault = (string)($p['nombre'] ?? '');
$descDefault = (string)($p['descripcion'] ?? '');

$precioDefault = $isEdit ? (string)($p['precio_venta'] ?? '') : '';
$costoDefault  = $isEdit ? (string)($p['costo'] ?? '') : '';

$tipoVal   = (string)old('tipo', $tipoDefault);
$refVal    = (string)old('referencia', $refDefault);
$nombreVal = (string)old('nombre', $nombreDefault);
$descVal   = (string)old('descripcion', $descDefault);

$precioVal = (string)old('precio_venta', $precioDefault);
$costoVal  = (string)old('costo', $costoDefault);

$actionUrl = (string)($action ?? ($isEdit && !empty($p['id'])
    ? '/productos/' . (int)$p['id'] . '/editar'
    : '/productos/crear'
));

$id = (int)($p['id'] ?? 0);
?>
<div class="card form-card">
  <div class="section-header" style="margin-bottom:12px;">
    <h3><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
    <div class="table-actions">
      <a class="btn btn-secondary" href="/productos">Volver al listado</a>
      <?php if ($isEdit && $id > 0): ?>
        <a class="btn btn-secondary" href="/productos/<?= $id ?>">Ver detalle</a>
      <?php endif; ?>
    </div>
  </div>

  <form method="post" action="<?= htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <div class="form-grid" style="margin-top:0;">
      <div>
        <label for="tipo">Tipo</label>
        <select id="tipo" name="tipo" required class="input <?= hasErr('tipo') ? 'error' : '' ?>" style="width:100%;">
          <option value="producto" <?= $tipoVal === 'producto' ? 'selected' : '' ?>>producto</option>
          <option value="servicio" <?= $tipoVal === 'servicio' ? 'selected' : '' ?>>servicio</option>
        </select>
        <?php if (err('tipo')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('tipo'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <div class="help">
          <?php if ($tipoVal === 'servicio'): ?>
            Servicio: no mueve stock.
          <?php else: ?>
            Producto: mueve stock (compras/facturas/ajustes).
          <?php endif; ?>
        </div>
      </div>

      <div>
        <label for="referencia">Referencia</label>
        <input
          id="referencia"
          name="referencia"
          required
          maxlength="64"
          value="<?= htmlspecialchars($refVal, ENT_QUOTES, 'UTF-8') ?>"
          class="input <?= hasErr('referencia') ? 'error' : '' ?>"
          style="width:100%;"
        >
        <?php if (err('referencia')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('referencia'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>

      <div>
        <label for="nombre">Nombre</label>
        <input
          id="nombre"
          name="nombre"
          required
          maxlength="160"
          value="<?= htmlspecialchars($nombreVal, ENT_QUOTES, 'UTF-8') ?>"
          class="input <?= hasErr('nombre') ? 'error' : '' ?>"
          style="width:100%;"
        >
        <?php if (err('nombre')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('nombre'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>

      <div style="grid-column: 1 / -1;">
        <label for="descripcion">Descripción</label>
        <textarea
          id="descripcion"
          name="descripcion"
          rows="4"
          class="input <?= hasErr('descripcion') ? 'error' : '' ?>"
          style="width:100%;"
        ><?= htmlspecialchars($descVal, ENT_QUOTES, 'UTF-8') ?></textarea>
        <?php if (err('descripcion')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('descripcion'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>

      <div>
        <label for="precio_venta">Precio de venta</label>
        <input
          id="precio_venta"
          name="precio_venta"
          required
          type="number"
          step="0.01"
          min="0.01"
          inputmode="decimal"
          value="<?= htmlspecialchars($precioVal, ENT_QUOTES, 'UTF-8') ?>"
          class="input <?= hasErr('precio_venta') ? 'error' : '' ?>"
          placeholder="0.00"
          style="width:100%;"
        >
        <?php if (err('precio_venta')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('precio_venta'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>

      <div>
        <label for="costo">Costo (opcional)</label>
        <input
          id="costo"
          name="costo"
          type="number"
          step="0.01"
          min="0"
          inputmode="decimal"
          value="<?= htmlspecialchars($costoVal, ENT_QUOTES, 'UTF-8') ?>"
          class="input <?= hasErr('costo') ? 'error' : '' ?>"
          placeholder="0.00"
          style="width:100%;"
        >
        <?php if (err('costo')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('costo'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <div class="help">En servicios puede dejarse vacío; en productos es informativo.</div>
      </div>
    </div>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <?php if ($isEdit && $id > 0): ?>
        <a class="btn btn-secondary" href="/productos/<?= $id ?>">Cancelar</a>
      <?php else: ?>
        <a class="btn btn-secondary" href="/productos">Cancelar</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>