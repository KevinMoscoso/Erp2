<?php
declare(strict_types=1);

$title = 'Cartera';
require __DIR__ . '/../partials/app_shell_top.php';

$showCxc = (($tipo ?? '') === '' || ($tipo ?? '') === 'factura');
$showCxp = (($tipo ?? '') === '' || ($tipo ?? '') === 'compra');

$fmt = static function($v): string {
  $n = is_numeric($v) ? (float)$v : 0.0;
  return number_format($n, 2, '.', '');
};

$tipoVal = (string)($tipo ?? '');
$qVal = (string)($q ?? '');
$terceroIdVal = (string)($tercero_id ?? '');
$desdeVal = (string)($desde ?? '');
$hastaVal = (string)($hasta ?? '');
$estadoPagoVal = (string)($estado_pago ?? '');
?>

<div class="card" style="padding:16px;">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Cartera</h3>
    <span class="muted">CXC / CXP</span>
  </div>

  <form method="get" action="/cartera" style="margin-top:12px;">
    <div class="kv-grid" style="margin-top:0;">
      <div class="kv">
        <div class="k">Tipo</div>
        <div class="v" style="font-weight:600;">
          <select class="input" name="tipo" style="width:100%;">
            <option value="" <?= ($tipoVal === '') ? 'selected' : '' ?>>Ambas</option>
            <option value="factura" <?= ($tipoVal === 'factura') ? 'selected' : '' ?>>CXC (Facturas)</option>
            <option value="compra"  <?= ($tipoVal === 'compra') ? 'selected' : '' ?>>CXP (Compras)</option>
          </select>
        </div>
      </div>

      <div class="kv">
        <div class="k">Buscar</div>
        <div class="v" style="font-weight:600;">
          <input class="input" name="q" value="<?= htmlspecialchars($qVal, ENT_QUOTES, 'UTF-8') ?>" maxlength="120" placeholder="número o tercero" style="width:100%;">
        </div>
      </div>

      <div class="kv">
        <div class="k">Tercero ID</div>
        <div class="v" style="font-weight:600;">
          <input class="input" name="tercero_id" value="<?= htmlspecialchars($terceroIdVal, ENT_QUOTES, 'UTF-8') ?>" style="width:100%;" placeholder="ej: 10">
        </div>
      </div>

      <div class="kv">
        <div class="k">Desde</div>
        <div class="v" style="font-weight:600;">
          <input class="input" type="date" name="desde" value="<?= htmlspecialchars($desdeVal, ENT_QUOTES, 'UTF-8') ?>" style="width:100%;">
        </div>
      </div>

      <div class="kv">
        <div class="k">Hasta</div>
        <div class="v" style="font-weight:600;">
          <input class="input" type="date" name="hasta" value="<?= htmlspecialchars($hastaVal, ENT_QUOTES, 'UTF-8') ?>" style="width:100%;">
        </div>
      </div>

      <div class="kv">
        <div class="k">Estado de pago</div>
        <div class="v" style="font-weight:600;">
          <select class="input" name="estado_pago" style="width:100%;">
            <option value="" <?= ($estadoPagoVal === '') ? 'selected' : '' ?>>Todos</option>
            <option value="pendiente" <?= ($estadoPagoVal === 'pendiente') ? 'selected' : '' ?>>pendiente</option>
            <option value="parcial"   <?= ($estadoPagoVal === 'parcial') ? 'selected' : '' ?>>parcial</option>
            <option value="pagado"    <?= ($estadoPagoVal === 'pagado') ? 'selected' : '' ?>>pagado</option>
          </select>
        </div>
      </div>

      <div class="kv">
        <div class="k">Acciones</div>
        <div class="v" style="font-weight:600;">
          <div class="table-actions">
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <a class="btn btn-secondary" href="/cartera">Limpiar</a>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<?php if ($showCxc): ?>
  <div class="card" style="padding:16px; margin-top:14px;">
    <div class="section-header" style="margin-bottom:10px;">
      <h3>CXC (Facturas emitidas)</h3>
      <span class="muted">Cuentas por cobrar</span>
    </div>

    <div class="table-container" style="margin-top:0;">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Tipo</th>
            <th>Número</th>
            <th>Fecha</th>
            <th>Tercero</th>
            <th>Total</th>
            <th>Pagado</th>
            <th>Saldo</th>
            <th>Estado pago</th>
            <th>Link</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($cxc ?? []) as $r): ?>
            <?php
              $id = (int)($r['id'] ?? 0);
              $numero = (string)($r['numero'] ?? '');
              $fecha = (string)($r['fecha'] ?? '');
              $tercero = (string)($r['tercero_nombre'] ?? '');
              $total = $fmt($r['total'] ?? 0);
              $pagado = $fmt($r['pagado'] ?? 0);
              $saldo = $fmt($r['saldo'] ?? 0);
              $estadoPagoRow = (string)($r['estado_pago'] ?? '');

              $estadoClass = 'badge-muted';
              if ($estadoPagoRow === 'pagado') $estadoClass = 'badge-success';
              elseif ($estadoPagoRow === 'parcial') $estadoClass = 'badge';
              elseif ($estadoPagoRow === 'pendiente') $estadoClass = 'badge-danger';
            ?>
            <tr>
              <td><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></td>
              <td><span class="badge badge-success">factura</span></td>
              <td><?= htmlspecialchars($numero, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($tercero, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($total, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($pagado, ENT_QUOTES, 'UTF-8') ?></td>
              <td style="font-weight:900;"><?= htmlspecialchars($saldo, ENT_QUOTES, 'UTF-8') ?></td>
              <td><span class="badge <?= $estadoClass ?>"><?= htmlspecialchars($estadoPagoRow, ENT_QUOTES, 'UTF-8') ?></span></td>
              <td><a class="btn btn-secondary" href="/facturas/<?= $id ?>">Ver</a></td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($cxc)): ?>
            <tr><td colspan="10">Sin resultados en CXC.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php if ($showCxp): ?>
  <div class="card" style="padding:16px; margin-top:14px;">
    <div class="section-header" style="margin-bottom:10px;">
      <h3>CXP (Compras emitidas)</h3>
      <span class="muted">Cuentas por pagar</span>
    </div>

    <div class="table-container" style="margin-top:0;">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Tipo</th>
            <th>Número</th>
            <th>Fecha</th>
            <th>Tercero</th>
            <th>Total</th>
            <th>Pagado</th>
            <th>Saldo</th>
            <th>Estado pago</th>
            <th>Link</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($cxp ?? []) as $r): ?>
            <?php
              $id = (int)($r['id'] ?? 0);
              $numero = (string)($r['numero'] ?? '');
              $fecha = (string)($r['fecha'] ?? '');
              $tercero = (string)($r['tercero_nombre'] ?? '');
              $total = $fmt($r['total'] ?? 0);
              $pagado = $fmt($r['pagado'] ?? 0);
              $saldo = $fmt($r['saldo'] ?? 0);
              $estadoPagoRow = (string)($r['estado_pago'] ?? '');

              $estadoClass = 'badge-muted';
              if ($estadoPagoRow === 'pagado') $estadoClass = 'badge-success';
              elseif ($estadoPagoRow === 'parcial') $estadoClass = 'badge';
              elseif ($estadoPagoRow === 'pendiente') $estadoClass = 'badge-danger';
            ?>
            <tr>
              <td><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></td>
              <td><span class="badge">compra</span></td>
              <td><?= htmlspecialchars($numero, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($tercero, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($total, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($pagado, ENT_QUOTES, 'UTF-8') ?></td>
              <td style="font-weight:900;"><?= htmlspecialchars($saldo, ENT_QUOTES, 'UTF-8') ?></td>
              <td><span class="badge <?= $estadoClass ?>"><?= htmlspecialchars($estadoPagoRow, ENT_QUOTES, 'UTF-8') ?></span></td>
              <td><a class="btn btn-secondary" href="/compras/<?= $id ?>">Ver</a></td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($cxp)): ?>
            <tr><td colspan="10">Sin resultados en CXP.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>