<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Compras', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Compras', ENT_QUOTES, 'UTF-8') ?></h1>

  <p><a href="/">Inicio</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <p style="color:#0a7a0a;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="get" action="/compras">
    <label for="q">Filtrar por número</label>
    <input id="q" name="q" value="<?= htmlspecialchars((string)($q ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="32">
    <button type="submit">Buscar</button>
    <?php if (!empty($q)): ?>
      <a href="/compras">Limpiar</a>
    <?php endif; ?>
  </form>

  <p style="margin-top:12px;">
    <?php if (\Erp2\Core\Auth::has('compras.crear')): ?>
      <a href="/compras/crear">Crear compra</a>
    <?php endif; ?>
  </p>

  <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%;">
    <thead>
      <tr>
        <th>ID</th>
        <th>Número</th>
        <th>Fecha</th>
        <th>Proveedor</th>
        <th>Estado</th>
        <th>Total</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($items ?? []) as $c): ?>
        <?php $id = (int)($c['id'] ?? 0); $estado = (string)($c['estado'] ?? ''); ?>
        <tr>
          <td><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></td>
          <td><a href="/compras/<?= $id ?>"><?= htmlspecialchars((string)($c['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a></td>
          <td><?= htmlspecialchars((string)($c['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($c['tercero_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($c['total'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <a href="/compras/<?= $id ?>">Ver</a>

            <?php if (\Erp2\Core\Auth::has('compras.emitir') && $estado === 'borrador'): ?>
              | <form method="post" action="/compras/<?= $id ?>/emitir" style="display:inline;">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  <button type="submit" onclick="return confirm('¿Emitir compra?');">Emitir</button>
                </form>
            <?php endif; ?>

            <?php if (\Erp2\Core\Auth::has('compras.anular') && $estado !== 'anulada'): ?>
              | <form method="post" action="/compras/<?= $id ?>/anular" style="display:inline;">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  <button type="submit" onclick="return confirm('¿Anular compra?');">Anular</button>
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