<?php
declare(strict_types=1);

use Erp2\Core\Auth;

$title = 'Detalle de Factura';
require __DIR__ . '/../partials/app_shell_top.php';

$fact = is_array($factura ?? null) ? $factura : [];
$id = (int)($fact['id'] ?? 0);
$estado = (string)($fact['estado'] ?? '');
$total = (float)($fact['total'] ?? 0);

// CSRF fallback seguro
$csrfVal = '';
if (isset($csrf) && is_string($csrf) && $csrf !== '') {
    $csrfVal = $csrf;
} elseif (class_exists('\Erp2\Core\Csrf')) {
    $csrfVal = (string)\Erp2\Core\Csrf::token();
}

// Datos de pagos: preferir variables ya calculadas por el controller (no mezclar capas)
$canSeePagos = Auth::has('pagos.ver');

$pagos = (is_array($pagos ?? null)) ? $pagos : [];
$pagado = is_numeric($pagado ?? null) ? (float)$pagado : null;
$saldo  = is_numeric($saldo ?? null)  ? (float)$saldo  : null;
$estadoPago = is_string($estado_pago ?? null) ? (string)$estado_pago : (is_string($estadoPago ?? null) ? (string)$estadoPago : null);

// Fallback opcional (solo si el controller NO lo calculó)
if ($canSeePagos && $id > 0 && ($pagado === null || $saldo === null || $estadoPago === null)) {
    if (class_exists('\Erp2\Model\Pago') && method_exists('\Erp2\Model\Pago', 'sumByRef')) {
        try {
            $pagos = (class_exists('\Erp2\Model\Pago') && method_exists('\Erp2\Model\Pago', 'listByRef'))
                ? (array)\Erp2\Model\Pago::listByRef('factura', $id)
                : [];

            $pagado = (float)\Erp2\Model\Pago::sumByRef('factura', $id);
            $saldo = round($total - $pagado, 2);

            if ($pagado <= 0.0) $estadoPago = 'pendiente';
            elseif (abs($pagado - $total) < 0.00001) $estadoPago = 'pagado';
            else $estadoPago = 'parcial';
        } catch (Throwable) {
            // no-op (no romper vista)
            $pagado = 0.0;
            $saldo = $total;
            $estadoPago = 'pendiente';
            $pagos = [];
        }
    } else {
        $pagado = 0.0;
        $saldo = $total;
        $estadoPago = 'pendiente';
        $pagos = [];
    }
}

// Seguridad/acciones
$canRegistrarPago = (Auth::has('pagos.crear') && $estado === 'emitida');
$canEliminarPago  = (Auth::has('pagos.eliminar') && $estado !== 'anulada');
$canAnular        = (Auth::has('facturas.anular') && $estado !== 'anulada');

// UX: ocultar anular si existen pagos (solo si podemos saberlo)
if ($canSeePagos && (float)($pagado ?? 0) > 0.00001) {
    $canAnular = false;
}

$badgeEstado = match ($estado) {
    'emitida'  => 'badge-success',
    'anulada'  => 'badge-danger',
    'borrador' => 'badge-muted',
    default    => 'badge-muted',
};

$badgeEstadoPago = match ((string)($estadoPago ?? 'pendiente')) {
    'pagado'    => 'badge-success',
    'parcial'   => 'badge',
    'pendiente' => 'badge-danger',
    default     => 'badge-muted',
};
?>

<div class="card" style="padding:16px;">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Factura <?= htmlspecialchars((string)($fact['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
    <div class="table-actions">
      <a class="btn btn-secondary" href="/facturas">Volver</a>

      <?php if ($estado === 'borrador' && Auth::has('facturas.emitir')): ?>
        <form method="post" action="/facturas/<?= $id ?>/emitir" style="display:inline;">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfVal, ENT_QUOTES, 'UTF-8') ?>">
          <button class="btn btn-primary" type="submit" onclick="return confirm('¿Emitir factura?');">Emitir</button>
        </form>
      <?php endif; ?>

      <?php if ($canAnular): ?>
        <form method="post" action="/facturas/<?= $id ?>/anular" style="display:inline;">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfVal, ENT_QUOTES, 'UTF-8') ?>">
          <button class="btn btn-danger" type="submit" onclick="return confirm('¿Anular factura?');">Anular</button>
        </form>
      <?php endif; ?>

      <?php if ($canRegistrarPago): ?>
        <a class="btn btn-secondary" href="/pagos/crear?tipo_ref=factura&ref_id=<?= $id ?>">Registrar pago</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="kv-grid">
    <div class="kv">
      <div class="k">Fecha</div>
      <div class="v"><?= htmlspecialchars((string)($fact['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="kv">
      <div class="k">Estado</div>
      <div class="v"><span class="badge <?= $badgeEstado ?>"><?= htmlspecialchars($estado ?: '—', ENT_QUOTES, 'UTF-8') ?></span></div>
    </div>

    <div class="kv">
      <div class="k">Total</div>
      <div class="v"><?= htmlspecialchars((string)($fact['total'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <?php if ($canSeePagos): ?>
      <div class="kv">
        <div class="k">Estado de pago</div>
        <div class="v"><span class="badge <?= $badgeEstadoPago ?>"><?= htmlspecialchars((string)($estadoPago ?? 'pendiente'), ENT_QUOTES, 'UTF-8') ?></span></div>
      </div>

      <div class="kv">
        <div class="k">Pagado</div>
        <div class="v"><?= htmlspecialchars(number_format((float)($pagado ?? 0), 2, '.', ''), ENT_QUOTES, 'UTF-8') ?></div>
      </div>

      <div class="kv">
        <div class="k">Saldo</div>
        <div class="v"><?= htmlspecialchars(number_format(max(0.0, (float)($saldo ?? $total)), 2, '.', ''), ENT_QUOTES, 'UTF-8') ?></div>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="card" style="padding:16px; margin-top:14px;">
  <div class="section-header" style="margin-bottom:10px;">
    <h3>Detalle</h3>
    <span class="muted">Líneas de la factura</span>
  </div>

  <div class="table-container" style="margin-top:0;">
    <table class="table">
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
  </div>
</div>

<?php if ($canSeePagos): ?>
  <div class="card" style="padding:16px; margin-top:14px;">
    <div class="section-header" style="margin-bottom:10px;">
      <h3>Pagos</h3>
      <span class="muted">Asociados a la factura</span>
    </div>

    <div class="table-container" style="margin-top:0;">
      <table class="table">
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
              <td><span class="muted"><?= htmlspecialchars((string)($p['referencia'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
              <td><span class="muted"><?= htmlspecialchars((string)($p['nota'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
              <td>
                <?php if ($canEliminarPago): ?>
                  <form method="post" action="/pagos/<?= $pid ?>/eliminar" style="display:inline;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfVal, ENT_QUOTES, 'UTF-8') ?>">
                    <button class="btn btn-danger" type="submit" onclick="return confirm('¿Eliminar pago?');">Eliminar</button>
                  </form>
                <?php else: ?>
                  <span class="badge badge-muted">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($pagos)): ?>
            <tr><td colspan="6">Sin pagos registrados.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>