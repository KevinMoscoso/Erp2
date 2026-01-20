<?php
declare(strict_types=1);

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
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Producto/Servicio', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    .err{color:#b00020;font-size:0.92em;margin-top:4px;}
    .input-err{border:1px solid #b00020;}
  </style>
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Producto/Servicio', ENT_QUOTES, 'UTF-8') ?></h1>

  <p><a href="/productos">Volver al listado</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <p style="color:#0b6b0b;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="post" action="<?= htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <div>
      <label for="tipo">Tipo</label><br>
      <select id="tipo" name="tipo" required class="<?= hasErr('tipo') ? 'input-err' : '' ?>">
        <option value="producto" <?= $tipoVal === 'producto' ? 'selected' : '' ?>>producto</option>
        <option value="servicio" <?= $tipoVal === 'servicio' ? 'selected' : '' ?>>servicio</option>
      </select>
      <?php if (err('tipo')): ?>
        <div class="err"><?= htmlspecialchars((string)err('tipo'), ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
    </div>

    <div style="margin-top:8px;">
      <label for="referencia">Referencia</label><br>
      <input id="referencia" name="referencia" required maxlength="64"
             value="<?= htmlspecialchars($refVal, ENT_QUOTES, 'UTF-8') ?>"
             class="<?= hasErr('referencia') ? 'input-err' : '' ?>">
      <?php if (err('referencia')): ?>
        <div class="err"><?= htmlspecialchars((string)err('referencia'), ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
    </div>

    <div style="margin-top:8px;">
      <label for="nombre">Nombre</label><br>
      <input id="nombre" name="nombre" required maxlength="160"
             value="<?= htmlspecialchars($nombreVal, ENT_QUOTES, 'UTF-8') ?>"
             class="<?= hasErr('nombre') ? 'input-err' : '' ?>">
      <?php if (err('nombre')): ?>
        <div class="err"><?= htmlspecialchars((string)err('nombre'), ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
    </div>

    <div style="margin-top:8px;">
      <label for="descripcion">Descripción</label><br>
      <textarea id="descripcion" name="descripcion" rows="4" cols="60"
                class="<?= hasErr('descripcion') ? 'input-err' : '' ?>"><?= htmlspecialchars($descVal, ENT_QUOTES, 'UTF-8') ?></textarea>
      <?php if (err('descripcion')): ?>
        <div class="err"><?= htmlspecialchars((string)err('descripcion'), ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
    </div>

    <div style="margin-top:8px;">
      <label for="precio_venta">Precio de venta</label><br>
      <input id="precio_venta" name="precio_venta" required
             type="number" step="0.01" min="0.01" inputmode="decimal"
             value="<?= htmlspecialchars($precioVal, ENT_QUOTES, 'UTF-8') ?>"
             class="<?= hasErr('precio_venta') ? 'input-err' : '' ?>"
             placeholder="0.00">
      <?php if (err('precio_venta')): ?>
        <div class="err"><?= htmlspecialchars((string)err('precio_venta'), ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
    </div>

    <div style="margin-top:8px;">
      <label for="costo">Costo (opcional)</label><br>
      <input id="costo" name="costo"
             type="number" step="0.01" min="0" inputmode="decimal"
             value="<?= htmlspecialchars($costoVal, ENT_QUOTES, 'UTF-8') ?>"
             class="<?= hasErr('costo') ? 'input-err' : '' ?>"
             placeholder="0.00">
      <?php if (err('costo')): ?>
        <div class="err"><?= htmlspecialchars((string)err('costo'), ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
    </div>

    <div style="margin-top:12px;">
      <button type="submit">Guardar</button>
      <?php if ($isEdit && !empty($p['id'])): ?>
        <a href="/productos/<?= (int)$p['id'] ?>">Cancelar</a>
      <?php endif; ?>
    </div>
  </form>
</body>
</html>