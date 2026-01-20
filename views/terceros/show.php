<?php
declare(strict_types=1);

$t = is_array($tercero ?? null) ? (array)$tercero : [];
$id = (int)($t['id'] ?? 0);

$oldNombres  = (string)old('nombres', (string)old('nombre', ''));
$oldEmail    = (string)old('email', '');
$oldTelefono = (string)old('telefono', '');
$oldCargo    = (string)old('cargo', '');
$oldNotas    = (string)old('notas', '');

$errNombres = err('nombres') ?: err('nombre');
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
  <p><a href="/terceros">← Volver</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <p style="color:#0b6b0b;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <h1>Tercero #<?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></h1>

  <ul>
    <li><strong>Tipo:</strong> <?= htmlspecialchars((string)($t['tipo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Nombre comercial:</strong> <?= htmlspecialchars((string)($t['nombre_comercial'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Identificación:</strong> <?= htmlspecialchars((string)($t['identificacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Email:</strong> <?= htmlspecialchars((string)($t['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
  </ul>

  <p>
    <?php if (\Erp2\Core\Auth::has('terceros.editar')): ?>
      <a href="/terceros/<?= $id ?>/editar">Editar</a>
    <?php endif; ?>

    <?php if (\Erp2\Core\Auth::has('terceros.eliminar')): ?>
      <?php if (\Erp2\Core\Auth::has('terceros.editar')): ?> | <?php endif; ?>
      <form method="post" action="/terceros/<?= $id ?>/eliminar" style="display:inline;">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" onclick="return confirm('¿Eliminar tercero (soft delete)?');">Eliminar</button>
      </form>
    <?php endif; ?>
  </p>

  <hr>

  <h2>Contactos</h2>

  <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%;">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Email</th>
        <th>Teléfono</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($contactos ?? []) as $c): ?>
        <?php $cid = (int)($c['id'] ?? 0); ?>
        <tr>
          <td><?= htmlspecialchars((string)$cid, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($c['nombres'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($c['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($c['telefono'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <?php if (\Erp2\Core\Auth::has('terceros.editar')): ?>
              <form method="post" action="/terceros/<?= $id ?>/contactos/<?= $cid ?>/eliminar" style="display:inline;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" onclick="return confirm('¿Eliminar contacto?');">Eliminar</button>
              </form>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php if (empty($contactos)): ?>
        <tr><td colspan="5">Sin contactos.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <?php if (\Erp2\Core\Auth::has('terceros.editar')): ?>
    <h3 style="margin-top:16px;">Agregar contacto</h3>

    <?php if (err('contacto')): ?>
      <p class="err"><?= htmlspecialchars((string)err('contacto'), ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" action="/terceros/<?= $id ?>/contactos/crear">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">

      <div>
        <label for="nombres">Nombre</label><br>
        <input id="nombres" name="nombres" required maxlength="160"
               value="<?= htmlspecialchars($oldNombres, ENT_QUOTES, 'UTF-8') ?>"
               class="<?= ($errNombres !== null) ? 'input-err' : '' ?>">

        <!-- Alias retro-compatible por si algún controlador/old usa 'nombre' -->
        <input type="hidden" name="nombre" value="<?= htmlspecialchars($oldNombres, ENT_QUOTES, 'UTF-8') ?>">

        <?php if ($errNombres): ?>
          <div class="err"><?= htmlspecialchars((string)$errNombres, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>

      <div style="margin-top:8px;">
        <label for="email">Email</label><br>
        <input id="email" name="email" type="email" maxlength="190"
               value="<?= htmlspecialchars($oldEmail, ENT_QUOTES, 'UTF-8') ?>"
               class="<?= hasErr('email') ? 'input-err' : '' ?>">
        <?php if (err('email')): ?>
          <div class="err"><?= htmlspecialchars((string)err('email'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>

      <div style="margin-top:8px;">
        <label for="telefono">Teléfono</label><br>
        <input id="telefono" name="telefono" maxlength="30"
               value="<?= htmlspecialchars($oldTelefono, ENT_QUOTES, 'UTF-8') ?>"
               class="<?= hasErr('telefono') ? 'input-err' : '' ?>">
        <?php if (err('telefono')): ?>
          <div class="err"><?= htmlspecialchars((string)err('telefono'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>

      <div style="margin-top:8px;">
        <label for="cargo">Cargo (opcional)</label><br>
        <input id="cargo" name="cargo" maxlength="80"
               value="<?= htmlspecialchars($oldCargo, ENT_QUOTES, 'UTF-8') ?>"
               class="<?= hasErr('cargo') ? 'input-err' : '' ?>">
        <?php if (err('cargo')): ?>
          <div class="err"><?= htmlspecialchars((string)err('cargo'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>

      <div style="margin-top:8px;">
        <label for="notas">Notas (opcional)</label><br>
        <input id="notas" name="notas" maxlength="255"
               value="<?= htmlspecialchars($oldNotas, ENT_QUOTES, 'UTF-8') ?>"
               class="<?= hasErr('notas') ? 'input-err' : '' ?>" style="width:100%;">
        <?php if (err('notas')): ?>
          <div class="err"><?= htmlspecialchars((string)err('notas'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>

      <div style="margin-top:12px;">
        <button type="submit">Crear contacto</button>
      </div>
    </form>
  <?php endif; ?>
</body>
</html>