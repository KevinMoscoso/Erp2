<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Terceros', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Terceros', ENT_QUOTES, 'UTF-8') ?></h1>

  <p><a href="/">Inicio</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <p style="color:#0b6b0b;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="get" action="/terceros">
    <label for="q">Buscar</label>
    <input id="q" name="q" value="<?= htmlspecialchars((string)($q ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="160">
    <button type="submit">Buscar</button>
    <?php if (!empty($q)): ?>
      <a href="/terceros">Limpiar</a>
    <?php endif; ?>
  </form>

  <p style="margin-top:12px;">
    <?php if (\Erp2\Core\Auth::has('terceros.crear')): ?>
      <a href="/terceros/crear">Crear tercero</a>
    <?php endif; ?>
  </p>

  <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%;">
    <thead>
      <tr>
        <th>ID</th>
        <th>Tipo</th>
        <th>Nombre comercial</th>
        <th>Identificación</th>
        <th>Email</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($items ?? []) as $t): ?>
        <tr>
          <td><?= htmlspecialchars((string)($t['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($t['tipo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <a href="/terceros/<?= (int)($t['id'] ?? 0) ?>">
              <?= htmlspecialchars((string)($t['nombre_comercial'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </a>
          </td>
          <td><?= htmlspecialchars((string)($t['identificacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($t['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <a href="/terceros/<?= (int)($t['id'] ?? 0) ?>">Ver</a>

            <?php if (\Erp2\Core\Auth::has('terceros.editar')): ?>
              | <a href="/terceros/<?= (int)($t['id'] ?? 0) ?>/editar">Editar</a>
            <?php endif; ?>

            <?php if (\Erp2\Core\Auth::has('terceros.eliminar')): ?>
              | <form method="post" action="/terceros/<?= (int)($t['id'] ?? 0) ?>/eliminar" style="display:inline;">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  <button type="submit" onclick="return confirm('¿Eliminar tercero (soft delete)?');">Eliminar</button>
                </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php if (empty($items)): ?>
        <tr><td colspan="6">Sin resultados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>