<?php
use Erp2\Core\Auth;

$isAdmin = (bool)($is_admin ?? ((int)(Auth::user()['id'] ?? 0) === 1));
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Roles', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Roles', ENT_QUOTES, 'UTF-8') ?></h1>

  <p><a href="/">Inicio</a> | <a href="/usuarios">Usuarios</a> | <a href="/permisos">Permisos</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <p style="color:#0a7a0a;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="get" action="/roles" style="margin-bottom:12px;">
    <label>Buscar</label>
    <input name="q" value="<?= htmlspecialchars((string)($q ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="120">
    <label>Límite</label>
    <input name="limit" type="number" min="1" max="500" value="<?= htmlspecialchars((string)($limit ?? 200), ENT_QUOTES, 'UTF-8') ?>" style="width:90px;">
    <button type="submit">Filtrar</button>
    <a href="/roles">Limpiar</a>
  </form>

  <?php if ($isAdmin): ?>
    <p><a href="/roles/crear">➕ Crear rol</a></p>
  <?php endif; ?>

  <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%;">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Descripción</th>
        <th>Link</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($items ?? []) as $r): ?>
        <?php
          $id = (int)($r['id'] ?? 0);
          $nombre = (string)($r['nombre'] ?? '');
          $descripcion = (string)($r['descripcion'] ?? '');
        ?>
        <tr>
          <td><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8') ?></td>
          <td><a href="/roles/<?= $id ?>">Ver</a></td>
        </tr>
      <?php endforeach; ?>

      <?php if (empty($items)): ?>
        <tr><td colspan="4">Sin resultados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>