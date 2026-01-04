<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Producto/Servicio', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <p><a href="/productos">← Volver</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <p style="color:#0b6b0b;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php $p = $producto ?? []; $id = (int)($p['id'] ?? 0); ?>

  <h1>Producto/Servicio #<?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></h1>

  <ul>
    <li><strong>Tipo:</strong> <?= htmlspecialchars((string)($p['tipo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Referencia:</strong> <?= htmlspecialchars((string)($p['referencia'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Nombre:</strong> <?= htmlspecialchars((string)($p['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Descripción:</strong> <?= htmlspecialchars((string)($p['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Precio venta:</strong> <?= htmlspecialchars((string)($p['precio_venta'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Costo:</strong> <?= htmlspecialchars((string)($p['costo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
  </ul>

  <p>
    <?php if (\Erp2\Core\Auth::has('productos.editar')): ?>
      <a href="/productos/<?= $id ?>/editar">Editar</a>
    <?php endif; ?>

    <?php if (\Erp2\Core\Auth::has('productos.eliminar')): ?>
      <?php if (\Erp2\Core\Auth::has('productos.editar')): ?> | <?php endif; ?>
      <form method="post" action="/productos/<?= $id ?>/eliminar" style="display:inline;">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" onclick="return confirm('¿Eliminar (soft delete)?');">Eliminar</button>
      </form>
    <?php endif; ?>
  </p>
</body>
</html>