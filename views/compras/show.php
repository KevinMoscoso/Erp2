<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Detalle compra', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <p><a href="/compras">← Volver</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <p style="color:#0a7a0a;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php $c = $compra ?? []; $id = (int)($c['id'] ?? 0); $estado = (string)($c['estado'] ?? ''); ?>

  <h1>Compra <?= htmlspecialchars((string)($c['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>

  <ul>
    <li><strong>ID:</strong> <?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Fecha:</strong> <?= htmlspecialchars((string)($c['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Proveedor:</strong> <?= htmlspecialchars((string)($c['tercero_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Estado:</strong> <?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Subtotal:</strong> <?= htmlspecialchars((string)($c['subtotal'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Total:</strong> <?= htmlspecialchars((string)($c['total'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
  </ul>

  <p>
    <?php if (\Erp2\Core\Auth::has('compras.emitir') && $estado === 'borrador'): ?>
      <form method="post" action="/compras/<?= $id ?>/emitir" style="display:inline;">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" onclick="return confirm('¿Emitir compra?');">Emitir</button>
      </form>
    <?php endif; ?>

    <?php if (\Erp2\Core\Auth::has('compras.anular') && $estado !== 'anulada'): ?>
      <?php if (\Erp2\Core\Auth::has('compras.emitir') && $estado === 'borrador'): ?> | <?php endif; ?>
      <form method="post" action="/compras/<?= $id ?>/anular" style="display:inline;">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" onclick="return confirm('¿Anular compra?');">Anular</button>
      </form>
    <?php endif; ?>
  </p>

  <hr>

  <h2>Detalle</h2>

  <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%;">
    <thead>
      <tr>
        <th>#</th>
        <th>Producto</th>
        <th>Descripción</th>
        <th>Cantidad</th>
        <th>Costo unitario</th>
        <th>Subtotal línea</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($detalles ?? []) as $i => $d): ?>
        <?php
          $ref = (string)($d['producto_referencia'] ?? '');
          $nom = (string)($d['producto_nombre'] ?? '');
          $prod = trim($ref . ' ' . $nom);
        ?>
        <tr>
          <td><?= htmlspecialchars((string)($i + 1), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($prod !== '' ? $prod : '—', ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($d['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($d['cantidad'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($d['costo_unitario'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($d['subtotal_linea'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
      <?php endforeach; ?>

      <?php if (empty($detalles)): ?>
        <tr><td colspan="6">Sin líneas.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>