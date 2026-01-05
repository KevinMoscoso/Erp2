<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Facturas', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Facturas', ENT_QUOTES, 'UTF-8') ?></h1>

  <p><a href="/">Inicio</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <p style="color:#0b6b0b;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="get" action="/facturas">
    <label for="q">Filtrar por número</label>
    <input id="q" name="q" value="<?= htmlspecialchars((string)($q ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="32">
    <button type="submit">Buscar</button>
    <?php if (!empty($q)): ?>
      <a href="/facturas">Limpiar</a>
    <?php endif; ?>
  </form>

  <p style="margin-top:12px;">
    <?php if (\Erp2\Core\Auth::has('facturas.crear')): ?>
      <a href="/facturas/crear">Crear factura</a>
    <?php endif; ?>
  </p>

  <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%;">
    <thead>
      <tr>
        <th>ID</th>
        <th>Número</th>
        <th>Fecha</th>
        <th>Tercero</th>
        <th>Estado</th>
        <th>Total</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($items ?? []) as $f): ?>
        <?php $id = (int)($f['id'] ?? 0); ?>
        <tr>
          <td><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <a href="/facturas/<?= $id ?>">
              <?= htmlspecialchars((string)($f['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </a>
          </td>
          <td><?= htmlspecialchars((string)($f['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($f['tercero_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($f['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($f['total'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <a href="/facturas/<?= $id ?>">Ver</a>

            <?php if (\Erp2\Core\Auth::has('facturas.anular')): ?>
              <?php if ((string)($f['estado'] ?? '') !== 'anulada'): ?>
                | <form method="post" action="/facturas/<?= $id ?>/anular" style="display:inline;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" onclick="return confirm('¿Anular factura?');">Anular</button>
                  </form>
              <?php else: ?>
                | (anulada)
              <?php endif; ?>
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