<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Tercero', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Tercero', ENT_QUOTES, 'UTF-8') ?></h1>

  <p><a href="/terceros">Volver al listado</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php
    $t = $tercero ?? [];
    $tipo = (string)($t['tipo'] ?? 'cliente');
    $nombre = (string)($t['nombre_comercial'] ?? '');
    $ident = (string)($t['identificacion'] ?? '');
    $email = (string)($t['email'] ?? '');
  ?>

  <form method="post" action="<?= htmlspecialchars((string)($action ?? '/terceros'), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <div>
      <label for="tipo">Tipo</label><br>
      <select id="tipo" name="tipo" required>
        <option value="cliente" <?= $tipo === 'cliente' ? 'selected' : '' ?>>cliente</option>
        <option value="proveedor" <?= $tipo === 'proveedor' ? 'selected' : '' ?>>proveedor</option>
        <option value="ambos" <?= $tipo === 'ambos' ? 'selected' : '' ?>>ambos</option>
      </select>
    </div>

    <div style="margin-top:8px;">
      <label for="nombre_comercial">Nombre comercial</label><br>
      <input id="nombre_comercial" name="nombre_comercial" required maxlength="160"
             value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div style="margin-top:8px;">
      <label for="identificacion">Identificación</label><br>
      <input id="identificacion" name="identificacion" maxlength="30"
             value="<?= htmlspecialchars($ident, ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div style="margin-top:8px;">
      <label for="email">Email</label><br>
      <input id="email" name="email" type="email" maxlength="190"
             value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div style="margin-top:12px;">
      <button type="submit">Guardar</button>
      <?php if (($mode ?? '') === 'edit' && !empty($t['id'])): ?>
        <a href="/terceros/<?= (int)$t['id'] ?>">Cancelar</a>
      <?php endif; ?>
    </div>
  </form>
</body>
</html>