<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Productos/Servicios', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Productos/Servicios', ENT_QUOTES, 'UTF-8') ?></h1>

  <p><a href="/">Inicio</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <p style="color:#0b6b0b;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="get" action="/productos">
    <label for="q">Buscar</label>
    <input id="q" name="q" value="<?= htmlspecialchars((string)($q ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="160">
    <button type="submit">Buscar</button>
    <?php if (!empty($q)): ?>
      <a href="/productos">Limpiar</a>
    <?php endif; ?>
  </form>

  <p style="margin-top:12px;">
    <?php if (\Erp2\Core\Auth::has('productos.crear')): ?>
      <a href="/productos/crear">Crear producto/servicio</a>
    <?php endif; ?>
  </p>

  <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%;">
    <thead>
      <tr>
        <th>ID</th>
        <th>Tipo</th>
        <th>Referencia</th>
        <th>Nombre</th>
        <th>Precio venta</th>
        <th>Costo</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($items ?? []) as $p): ?>
        <?php $id = (int)($p['id'] ?? 0); ?>
        <tr>
          <td><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($p['tipo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <a href="/productos/<?= $id ?>">
              <?= htmlspecialchars((string)($p['referencia'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </a>
          </td>
          <td><?= htmlspecialchars((string)($p['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($p['precio_venta'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($p['costo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <a href="/productos/<?= $id ?>">Ver</a>

            <?php if (\Erp2\Core\Auth::has('productos.editar')): ?>
              | <a href="/productos/<?= $id ?>/editar">Editar</a>
            <?php endif; ?>

            <?php if (\Erp2\Core\Auth::has('productos.eliminar')): ?>
              | <form method="post" action="/productos/<?= $id ?>/eliminar" style="display:inline;">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  <button type="submit" onclick="return confirm('¿Eliminar (soft delete)?');">Eliminar</button>
                </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php if (empty($items)): ?>
        <tr><td colspan="7">Sin resultados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>