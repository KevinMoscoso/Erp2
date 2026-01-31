<?php
use Erp2\Core\Auth;

$isAdmin = (bool)($is_admin ?? ((int)(Auth::user()['id'] ?? 0) === 1));
$r = is_array($role ?? null) ? $role : [];
$id = (int)($r['id'] ?? 0);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Detalle rol', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Detalle rol', ENT_QUOTES, 'UTF-8') ?></h1>

  <p><a href="/roles">← Volver</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <p style="color:#0a7a0a;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if ($isAdmin && $id > 0): ?>
    <p><a href="/roles/<?= $id ?>/editar">✏️ Editar rol</a></p>
  <?php endif; ?>

  <h3>Datos</h3>
  <ul>
    <li><b>ID:</b> <?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></li>
    <li><b>Nombre:</b> <?= htmlspecialchars((string)($r['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><b>Descripción:</b> <?= htmlspecialchars((string)($r['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
  </ul>

  <h3>Permisos asignados</h3>
  <ul>
    <?php foreach (($permisos ?? []) as $p): ?>
      <li><?= htmlspecialchars((string)($p['codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <?php endforeach; ?>
    <?php if (empty($permisos)): ?>
      <li>Sin permisos.</li>
    <?php endif; ?>
  </ul>
</body>
</html>