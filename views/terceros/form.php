<?php
declare(strict_types=1);

$t = is_array($tercero ?? null) ? (array)$tercero : [];

$tipoDefault = (string)($t['tipo'] ?? 'cliente');
$nombreDefault = (string)($t['nombre_comercial'] ?? '');
$identDefault = (string)($t['identificacion'] ?? '');
$emailDefault = (string)($t['email'] ?? '');

$tipoVal = (string)old('tipo', $tipoDefault);
$nombreVal = (string)old('nombre_comercial', $nombreDefault);
$identVal = (string)old('identificacion', $identDefault);
$emailVal = (string)old('email', $emailDefault);

$actionUrl = (string)($action ?? '/terceros/crear');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Tercero', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    .err{color:#b00020;font-size:0.92em;margin-top:4px;}
    .input-err{border:1px solid #b00020;}
  </style>
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Tercero', ENT_QUOTES, 'UTF-8') ?></h1>

  <p><a href="/terceros">Volver al listado</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <p style="color:#0a7a0a;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="post" action="<?= htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <div>
      <label for="tipo">Tipo</label><br>
      <select id="tipo" name="tipo" class="<?= hasErr('tipo') ? 'input-err' : '' ?>" required>
        <option value="cliente" <?= ($tipoVal === 'cliente') ? 'selected' : '' ?>>Cliente</option>
        <option value="proveedor" <?= ($tipoVal === 'proveedor') ? 'selected' : '' ?>>Proveedor</option>
        <option value="ambos" <?= ($tipoVal === 'ambos') ? 'selected' : '' ?>>Ambos</option>
      </select>
      <?php if (err('tipo')): ?>
        <div class="err"><?= htmlspecialchars((string)err('tipo'), ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
    </div>

    <div style="margin-top:10px;">
      <label for="nombre_comercial">Nombre comercial</label><br>
      <input
        id="nombre_comercial"
        name="nombre_comercial"
        type="text"
        maxlength="160"
        value="<?= htmlspecialchars($nombreVal, ENT_QUOTES, 'UTF-8') ?>"
        class="<?= hasErr('nombre_comercial') ? 'input-err' : '' ?>"
        required
      >
      <?php if (err('nombre_comercial')): ?>
        <div class="err"><?= htmlspecialchars((string)err('nombre_comercial'), ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
    </div>

    <div style="margin-top:10px;">
      <label for="identificacion">Identificación (opcional)</label><br>
      <input
        id="identificacion"
        name="identificacion"
        type="text"
        maxlength="30"
        value="<?= htmlspecialchars($identVal, ENT_QUOTES, 'UTF-8') ?>"
        class="<?= hasErr('identificacion') ? 'input-err' : '' ?>"
      >
      <?php if (err('identificacion')): ?>
        <div class="err"><?= htmlspecialchars((string)err('identificacion'), ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
    </div>

    <div style="margin-top:10px;">
      <label for="email">Email (opcional)</label><br>
      <input
        id="email"
        name="email"
        type="email"
        maxlength="190"
        value="<?= htmlspecialchars($emailVal, ENT_QUOTES, 'UTF-8') ?>"
        class="<?= hasErr('email') ? 'input-err' : '' ?>"
      >
      <?php if (err('email')): ?>
        <div class="err"><?= htmlspecialchars((string)err('email'), ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
    </div>

    <div style="margin-top:14px;">
      <button type="submit"><?= ($mode ?? 'create') === 'edit' ? 'Guardar cambios' : 'Crear tercero' ?></button>
    </div>
  </form>
</body>
</html>