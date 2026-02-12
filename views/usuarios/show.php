<?php
use Erp2\Core\Auth;

$isAdmin = (bool)($is_admin ?? ((int)(Auth::user()['id'] ?? 0) === 1));
$u = is_array($user ?? null) ? $user : [];
$id = (int)($u['id'] ?? 0);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Detalle usuario', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Detalle usuario', ENT_QUOTES, 'UTF-8') ?></h1>

  <p><a href="/usuarios">← Volver</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <p style="color:#0a7a0a;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if ($isAdmin && $id > 0): ?>
    <p><a href="/usuarios/<?= $id ?>/editar">✏️ Editar usuario</a></p>
  <?php endif; ?>

  <h3>Datos</h3>
  <ul>
    <li><b>ID:</b> <?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></li>
    <li><b>Email:</b> <?= htmlspecialchars((string)($u['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><b>Nombre:</b> <?= htmlspecialchars((string)($u['nombre'] ?? ($u['nombres'] ?? '')), ENT_QUOTES, 'UTF-8') ?></li>
  </ul>

  <h3>Roles asignados</h3>
  <ul>
    <?php foreach (($roles ?? []) as $r): ?>
      <?php
        $label = (string)($r['codigo'] ?? '');
        if ($label === '') $label = (string)($r['nombre'] ?? '');
      ?>
      <li>
        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
        <?php
          $extra = (string)($r['nombre'] ?? '');
          if ((string)($r['codigo'] ?? '') !== '' && $extra !== '') {
              echo ' — ' . htmlspecialchars($extra, ENT_QUOTES, 'UTF-8');
          }
        ?>
        (ID <?= htmlspecialchars((string)($r['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)
      </li>
    <?php endforeach; ?>
    <?php if (empty($roles)): ?>
      <li>Sin roles.</li>
    <?php endif; ?>
  </ul>

  <h3>Permisos directos (rol personal <?= htmlspecialchars('USR_'.$id, ENT_QUOTES, 'UTF-8') ?>)</h3>
  <ul>
    <?php foreach (($perms_directos ?? []) as $c): ?>
      <li><?= htmlspecialchars((string)$c, ENT_QUOTES, 'UTF-8') ?></li>
    <?php endforeach; ?>
    <?php if (empty($perms_directos ?? [])): ?>
      <li>Sin permisos directos.</li>
    <?php endif; ?>
  </ul>

  <h3>Permisos efectivos (todos los roles)</h3>
  <ul>
    <?php foreach (($perms_efectivos ?? []) as $c): ?>
      <li><?= htmlspecialchars((string)$c, ENT_QUOTES, 'UTF-8') ?></li>
    <?php endforeach; ?>
    <?php if (empty($perms_efectivos ?? [])): ?>
      <li>Sin permisos efectivos.</li>
    <?php endif; ?>
  </ul>
</body>
</html>