<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Usuarios', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Usuarios', ENT_QUOTES, 'UTF-8') ?></h1>

  <p><a href="/">Inicio</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <p style="color:#0a7a0a;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="get" action="/usuarios">
    <label>Buscar (email/nombre o ID)</label>
    <input name="q" value="<?= htmlspecialchars((string)($q ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="120">

    <label>Límite</label>
    <input type="number" name="limit" min="1" max="500" step="1" value="<?= htmlspecialchars((string)($limit ?? 200), ENT_QUOTES, 'UTF-8') ?>" style="width:90px;">

    <button type="submit">Filtrar</button>
    <a href="/usuarios">Limpiar</a>
  </form>

  <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%; margin-top: 12px;">
    <thead>
      <tr>
        <th>ID</th>
        <th>Email</th>
        <th>Nombre</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($items ?? []) as $u): ?>
        <?php
          $id = (int)($u['id'] ?? 0);
          $email = (string)($u['email'] ?? '');
          $nombre = (string)($u['nombre'] ?? ($u['nombres'] ?? ''));
        ?>
        <tr>
          <td><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></td>
          <td><a href="/usuarios/<?= $id ?>">Ver</a></td>
        </tr>
      <?php endforeach; ?>

      <?php if (empty($items)): ?>
        <tr><td colspan="4">Sin resultados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>