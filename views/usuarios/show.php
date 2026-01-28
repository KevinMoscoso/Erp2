<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Detalle usuario', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Detalle usuario', ENT_QUOTES, 'UTF-8') ?></h1>

  <p>
    <a href="/">Inicio</a> |
    <a href="/usuarios">Usuarios</a>
  </p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <p style="color:#0a7a0a;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php
    $u = is_array($user ?? null) ? $user : [];
    $id = (int)($u['id'] ?? 0);
    $email = (string)($u['email'] ?? '');
    $nombre = (string)($u['nombre'] ?? ($u['nombres'] ?? ''));
  ?>

  <h2>Usuario</h2>
  <ul>
    <li><strong>ID:</strong> <?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Email:</strong> <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Nombre:</strong> <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></li>
  </ul>

  <h2>Roles asignados</h2>
  <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%;">
    <thead>
      <tr>
        <th>ID</th>
        <th>Código</th>
        <th>Nombre</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($roles ?? []) as $r): ?>
        <?php
          $rid = (int)($r['id'] ?? 0);
          $codigo = (string)($r['codigo'] ?? ($r['nombre'] ?? ''));
          $rnombre = (string)($r['nombre'] ?? '');
        ?>
        <tr>
          <td><?= htmlspecialchars((string)$rid, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($rnombre, ENT_QUOTES, 'UTF-8') ?></td>
          <td><a href="/roles/<?= $rid ?>">Ver rol</a></td>
        </tr>
      <?php endforeach; ?>

      <?php if (empty($roles)): ?>
        <tr><td colspan="4">Sin roles asignados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>