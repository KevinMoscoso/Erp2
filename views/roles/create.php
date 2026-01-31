<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Crear rol', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    .err { color:#b00020; font-size: .9em; }
    .input-err { border:1px solid #b00020; }
  </style>
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Crear rol', ENT_QUOTES, 'UTF-8') ?></h1>
  <p><a href="/roles">← Volver</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <p style="color:#0a7a0a;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="post" action="/roles/crear">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <div>
      <label>Nombre</label><br>
      <input
        name="nombre"
        value="<?= htmlspecialchars((string)old('nombre',''), ENT_QUOTES, 'UTF-8') ?>"
        class="<?= hasErr('nombre') ? 'input-err' : '' ?>"
        style="width: 360px;"
      >
      <?php if (hasErr('nombre')): ?><div class="err"><?= htmlspecialchars((string)err('nombre'), ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    </div>

    <div>
      <label>Descripción</label><br>
      <input
        name="descripcion"
        value="<?= htmlspecialchars((string)old('descripcion',''), ENT_QUOTES, 'UTF-8') ?>"
        class="<?= hasErr('descripcion') ? 'input-err' : '' ?>"
        style="width: 360px;"
      >
      <?php if (hasErr('descripcion')): ?><div class="err"><?= htmlspecialchars((string)err('descripcion'), ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    </div>

    <h3>Permisos del rol</h3>
    <?php
      $sel = old('permisos', []);
      if (!is_array($sel)) $sel = [];
      $selMap = [];
      foreach ($sel as $pid) $selMap[(int)$pid] = true;
    ?>
    <?php foreach (($permisos_all ?? []) as $p): ?>
      <?php $pid = (int)($p['id'] ?? 0); ?>
      <label style="display:block;">
        <input type="checkbox" name="permisos[]" value="<?= $pid ?>" <?= isset($selMap[$pid]) ? 'checked' : '' ?>>
        <?= htmlspecialchars((string)($p['codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
      </label>
    <?php endforeach; ?>

    <p><button type="submit">Guardar</button></p>
  </form>
</body>
</html>