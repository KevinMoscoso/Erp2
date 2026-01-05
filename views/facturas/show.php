<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Detalle factura', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <p><a href="/facturas">← Volver</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <p style="color:#0b6b0b;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php $f = $factura ?? []; $id = (int)($f['id'] ?? 0); ?>

  <h1>Factura <?= htmlspecialchars((string)($f['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>

  <ul>
    <li><strong>ID:</strong> <?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Fecha:</strong> <?= htmlspecialchars((string)($f['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Tercero:</strong> <?= htmlspecialchars((string)($f['tercero_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Estado:</strong> <?= htmlspecialchars((string)($f['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Subtotal:</strong> <?= htmlspecialchars((string)($f['subtotal'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Total:</strong> <?= htmlspecialchars((string)($f['total'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
  </ul>
  <?php if (\Erp2\Core\Auth::has('facturas.emitir') && (string)($f['estado'] ?? '') === 'borrador'): ?>
    <form method="post" action="/facturas/<?= $id ?>/emitir" style="display:inline; margin-right:8px;">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      <button type="submit" onclick="return confirm('¿Emitir factura? Esto descontará stock.');">Emitir</button>
    </form>
  <?php endif; ?>
  <?php if (\Erp2\Core\Auth::has('facturas.anular') && (string)($f['estado'] ?? '') !== 'anulada'): ?>
    <form method="post" action="/facturas/<?= $id ?>/anular" style="display:inline;">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      <button type="submit" onclick="return confirm('¿Anular factura?');">Anular</button>
    </form>
  <?php endif; ?>

  <hr>

  <h2>Detalle</h2>

  <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%;">
    <thead>
      <tr>
        <th>#</th>
        <th>Producto</th>
        <th>Descripción</th>
        <th>Cantidad</th>
        <th>Precio unitario</th>
        <th>Subtotal línea</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($detalles ?? []) as $i => $d): ?>
        <tr>
          <td><?= htmlspecialchars((string)($i + 1), ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <?php
              $ref = (string)($d['producto_referencia'] ?? '');
              $nom = (string)($d['producto_nombre'] ?? '');
              $prod = trim($ref . ' ' . $nom);
            ?>
            <?= htmlspecialchars($prod !== '' ? $prod : '—', ENT_QUOTES, 'UTF-8') ?>
          </td>
          <td><?= htmlspecialchars((string)($d['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($d['cantidad'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($d['precio_unitario'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
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