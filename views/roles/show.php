<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Detalle rol', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Detalle rol', ENT_QUOTES, 'UTF-8') ?></h1>

  <p>
    <a href="/">Inicio</a> |
    <a href="/roles">Roles</a>
  </p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <p style="color:#0a7a0a;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php
    $r = is_array($role ?? null) ? $role : [];
    $id = (int)($r['id'] ?? 0);
    $codigo = (string)($r['codigo'] ?? ($r['nombre'] ?? ''));
    $nombre = (string)($r['nombre'] ?? '');
  ?>

  <h2>Rol</h2>
  <ul>
    <li><strong>ID:</strong> <?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Código:</strong> <?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Nombre:</strong> <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></li>
  </ul>

  <h2>Permisos asignados</h2>
  <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%;">
    <thead>
      <tr>
        <th>ID</th>
        <th>Código</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($permisos ?? []) as $p): ?>
        <?php
          $pid = (int)($p['id'] ?? 0);
          $pcod = (string)($p['codigo'] ?? '');
        ?>
        <tr>
          <td><?= htmlspecialchars((string)$pid, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($pcod, ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
      <?php endforeach; ?>

      <?php if (empty($permisos)): ?>
        <tr><td colspan="2">Sin permisos asignados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>