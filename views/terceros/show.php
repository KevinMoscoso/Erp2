<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Tercero', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <p><a href="/terceros">← Volver</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <p style="color:#0b6b0b;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php $t = $tercero ?? []; $id = (int)($t['id'] ?? 0); ?>

  <h1>Tercero #<?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></h1>

  <ul>
    <li><strong>Tipo:</strong> <?= htmlspecialchars((string)($t['tipo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Nombre comercial:</strong> <?= htmlspecialchars((string)($t['nombre_comercial'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Identificación:</strong> <?= htmlspecialchars((string)($t['identificacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Email:</strong> <?= htmlspecialchars((string)($t['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
  </ul>

  <p>
    <?php if (\Erp2\Core\Auth::has('terceros.editar')): ?>
      <a href="/terceros/<?= $id ?>/editar">Editar</a>
    <?php endif; ?>

    <?php if (\Erp2\Core\Auth::has('terceros.eliminar')): ?>
      <?php if (\Erp2\Core\Auth::has('terceros.editar')): ?> | <?php endif; ?>
      <form method="post" action="/terceros/<?= $id ?>/eliminar" style="display:inline;">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" onclick="return confirm('¿Eliminar tercero (soft delete)?');">Eliminar</button>
      </form>
    <?php endif; ?>
  </p>

  <hr>

  <h2>Contactos</h2>

  <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%;">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Email</th>
        <th>Teléfono</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($contactos ?? []) as $c): ?>
        <?php $cid = (int)($c['id'] ?? 0); ?>
        <tr>
          <td><?= htmlspecialchars((string)$cid, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($c['nombres'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($c['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($c['telefono'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <?php if (\Erp2\Core\Auth::has('terceros.editar')): ?>
              <form method="post" action="/terceros/<?= $id ?>/contactos/<?= $cid ?>/eliminar" style="display:inline;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" onclick="return confirm('¿Eliminar contacto?');">Eliminar</button>
              </form>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php if (empty($contactos)): ?>
        <tr><td colspan="5">Sin contactos.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <?php if (\Erp2\Core\Auth::has('terceros.editar')): ?>
    <h3 style="margin-top:16px;">Agregar contacto</h3>
    <form method="post" action="/terceros/<?= $id ?>/contactos/crear">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">

      <div>
        <label for="nombre">Nombre</label><br>
        <input id="nombre" name="nombre" required maxlength="160">
      </div>

      <div style="margin-top:8px;">
        <label for="email">Email</label><br>
        <input id="email" name="email" type="email" maxlength="190">
      </div>

      <div style="margin-top:8px;">
        <label for="telefono">Teléfono</label><br>
        <input id="telefono" name="telefono" maxlength="30">
      </div>

      <div style="margin-top:12px;">
        <button type="submit">Crear contacto</button>
      </div>
    </form>
  <?php endif; ?>
</body>
</html>