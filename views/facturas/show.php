<?php
declare(strict_types=1);

use Erp2\Core\Auth;
use Erp2\Core\Csrf;
use Erp2\Model\Pago;

$fact = $factura ?? [];
$id = (int)($fact['id'] ?? 0);
$estado = (string)($fact['estado'] ?? '');
$total = (float)($fact['total'] ?? 0);

$pagos = [];
$pagado = 0.0;
$saldo = 0.0;
$estadoPago = 'pendiente';

// Solo calculamos pagos si el usuario tiene permiso de ver pagos (no filtrar info a roles sin permiso)
$canSeePagos = Auth::has('pagos.ver');

if ($canSeePagos && $id > 0) {
    $pagos = Pago::listByRef('factura', $id);
    $pagado = Pago::sumByRef('factura', $id);
    $saldo = round($total - $pagado, 2);

    if ($pagado <= 0.0) $estadoPago = 'pendiente';
    elseif (abs($pagado - $total) < 0.00001) $estadoPago = 'pagado';
    else $estadoPago = 'parcial';
}

$canRegistrarPago = (Auth::has('pagos.crear') && $estado === 'emitida');
$canEliminarPago = (Auth::has('pagos.eliminar') && $estado !== 'anulada');
$canAnular = (Auth::has('facturas.anular') && $estado !== 'anulada');

// UX HITO 9B: ocultar "Anular" si se detectan pagos (solo cuando podemos calcularlos)
if ($canSeePagos && $pagado > 0.00001) {
    $canAnular = false;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Factura</title>
</head>
<body>
  <p><a href="/facturas">← Volver</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <p style="color:#0a7a0a;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <h1>Factura <?= htmlspecialchars((string)($fact['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>

  <ul>
    <li><strong>Fecha:</strong> <?= htmlspecialchars((string)($fact['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Estado:</strong> <?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?></li>
    <li><strong>Total:</strong> <?= htmlspecialchars((string)($fact['total'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
  </ul>

  <p>
    <?php if ($estado === 'borrador' && Auth::has('facturas.emitir')): ?>
      <form method="post" action="/facturas/<?= $id ?>/emitir" style="display:inline;">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" onclick="return confirm('¿Emitir factura?');">Emitir</button>
      </form>
    <?php endif; ?>

    <?php if ($canAnular): ?>
      <?php if ($estado === 'borrador' && Auth::has('facturas.emitir')): ?> | <?php endif; ?>
      <form method="post" action="/facturas/<?= $id ?>/anular" style="display:inline;">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" onclick="return confirm('¿Anular factura?');">Anular</button>
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
        <th>Precio unit.</th>
        <th>Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($detalles ?? []) as $i => $d): ?>
        <tr>
          <td><?= htmlspecialchars((string)($i + 1), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($d['producto_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
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

  <?php if ($canSeePagos): ?>
    <hr>
    <h2>Pagos</h2>

    <p>
      <strong>Estado de pago:</strong> <?= htmlspecialchars($estadoPago, ENT_QUOTES, 'UTF-8') ?> |
      <strong>Pagado:</strong> <?= htmlspecialchars(number_format($pagado, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?> |
      <strong>Saldo:</strong> <?= htmlspecialchars(number_format(max(0.0, $saldo), 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>
    </p>

    <?php if ($canRegistrarPago): ?>
      <p><a href="/pagos/crear?tipo_ref=factura&ref_id=<?= $id ?>">Registrar pago para esta factura</a></p>
    <?php endif; ?>

    <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%;">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Monto</th>
          <th>Método</th>
          <th>Referencia</th>
          <th>Nota</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pagos as $p): ?>
          <?php $pid = (int)($p['id'] ?? 0); ?>
          <tr>
            <td><?= htmlspecialchars((string)($p['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($p['monto'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($p['metodo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($p['referencia'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($p['nota'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?php if ($canEliminarPago): ?>
                <form method="post" action="/pagos/<?= $pid ?>/eliminar" style="display:inline;">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                  <button type="submit" onclick="return confirm('¿Eliminar pago?');">Eliminar</button>
                </form>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($pagos)): ?>
          <tr><td colspan="6">Sin pagos registrados.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  <?php endif; ?>
</body>
</html>