<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Permisos', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Permisos', ENT_QUOTES, 'UTF-8') ?></h1>

  <p>
    <a href="/">Inicio</a>
    | <a href="/roles">Roles</a>
    | <a href="/usuarios">Usuarios</a>
  </p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <p style="color:#0a7a0a;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <p>
    <a href="/permisos/crear">➕ Crear permiso</a>
  </p>

  <form method="get" action="/permisos">
    <label>Buscar (código o ID)</label>
    <input name="q" value="<?= htmlspecialchars((string)($q ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="120">

    <label>Límite</label>
    <input type="number" name="limit" min="1" max="500" step="1"
           value="<?= htmlspecialchars((string)($limit ?? 200), ENT_QUOTES, 'UTF-8') ?>"
           style="width:90px;">

    <button type="submit">Filtrar</button>
    <a href="/permisos">Limpiar</a>
  </form>

  <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%; margin-top: 12px;">
    <thead>
      <tr>
        <th>ID</th>
        <th>Código</th>
        <th style="width:220px;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($items ?? []) as $p): ?>
        <?php
          $id = (int)($p['id'] ?? 0);
          $codigo = (string)($p['codigo'] ?? '');
        ?>
        <tr>
          <td><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <a href="/permisos/<?= $id ?>/editar">Editar</a>

            <form method="post"
                  action="/permisos/<?= $id ?>/eliminar"
                  style="display:inline;"
                  onsubmit="return confirm('¿Eliminar este permiso?');">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(\Erp2\Core\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
              <button type="submit">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php if (empty($items)): ?>
        <tr><td colspan="3">Sin resultados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>