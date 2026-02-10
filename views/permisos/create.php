<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Crear permiso', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    .err { color:#b00020; font-size: 0.95em; }
    .haserr { border:1px solid #b00020; }
  </style>
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Crear permiso', ENT_QUOTES, 'UTF-8') ?></h1>
  <p><a href="/permisos">Volver</a></p>

  <?php if (!empty($flash_error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$flash_error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if (!empty($flash_success)): ?>
    <p style="color:#0a7a0a;"><?= htmlspecialchars((string)$flash_success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="post" action="/permisos/crear">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(\Erp2\Core\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

    <label>Código (ej: facturas.ver)</label><br>
    <input
      name="codigo"
      maxlength="120"
      required
      pattern="[a-z0-9_.-]+"
      class="<?= hasErr('codigo') ? 'haserr' : '' ?>"
      value="<?= htmlspecialchars((string)old('codigo',''), ENT_QUOTES, 'UTF-8') ?>"
    >
    <?php if ($m = err('codigo')): ?>
      <div class="err"><?= htmlspecialchars((string)$m, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <br><br>
    <button type="submit">Guardar</button>
  </form>
</body>
</html>