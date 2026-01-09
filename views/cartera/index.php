<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Cartera', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Cartera', ENT_QUOTES, 'UTF-8') ?></h1>

  <p><a href="/">Inicio</a></p>

  <?php if (!empty($errors) && is_array($errors)): ?>
    <div style="color:#b00020;">
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="get" action="/cartera">
    <label>Tipo</label>
    <select name="tipo">
      <option value="" <?= (($tipo ?? '') === '') ? 'selected' : '' ?>>Ambas</option>
      <option value="factura" <?= (($tipo ?? '') === 'factura') ? 'selected' : '' ?>>CXC (Facturas)</option>
      <option value="compra"  <?= (($tipo ?? '') === 'compra') ? 'selected' : '' ?>>CXP (Compras)</option>
    </select>

    <label>Buscar (número o tercero)</label>
    <input name="q" value="<?= htmlspecialchars((string)($q ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="120">

    <label>Tercero ID</label>
    <input name="tercero_id" value="<?= htmlspecialchars((string)($tercero_id ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:90px;">

    <label>Desde</label>
    <input type="date" name="desde" value="<?= htmlspecialchars((string)($desde ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Hasta</label>
    <input type="date" name="hasta" value="<?= htmlspecialchars((string)($hasta ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Estado de pago</label>
    <select name="estado_pago">
      <option value="" <?= (($estado_pago ?? '') === '') ? 'selected' : '' ?>>Todos</option>
      <option value="pendiente" <?= (($estado_pago ?? '') === 'pendiente') ? 'selected' : '' ?>>pendiente</option>
      <option value="parcial"   <?= (($estado_pago ?? '') === 'parcial') ? 'selected' : '' ?>>parcial</option>
      <option value="pagado"    <?= (($estado_pago ?? '') === 'pagado') ? 'selected' : '' ?>>pagado</option>
    </select>

    <button type="submit">Filtrar</button>
    <a href="/cartera">Limpiar</a>
  </form>

  <?php
    $showCxc = (($tipo ?? '') === '' || ($tipo ?? '') === 'factura');
    $showCxp = (($tipo ?? '') === '' || ($tipo ?? '') === 'compra');

    $fmt = static function($v): string {
        return number_format((float)$v, 2, '.', '');
    };
  ?>

  <?php if ($showCxc): ?>
    <hr>
    <h2>CXC (Facturas emitidas)</h2>

    <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%;">
      <thead>
        <tr>
          <th>ID</th>
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
          ?>
          <tr>
            <td><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($numero, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($tercero, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($total, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($pagado, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($saldo, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($estadoPagoRow, ENT_QUOTES, 'UTF-8') ?></td>
            <td><a href="/facturas/<?= $id ?>">Ver</a></td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($cxc)): ?>
          <tr><td colspan="9">Sin resultados en CXC.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <?php if ($showCxp): ?>
    <hr>
    <h2>CXP (Compras emitidas)</h2>

    <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%;">
      <thead>
        <tr>
          <th>ID</th>
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
          ?>
          <tr>
            <td><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($numero, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($tercero, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($total, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($pagado, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($saldo, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($estadoPagoRow, ENT_QUOTES, 'UTF-8') ?></td>
            <td><a href="/compras/<?= $id ?>">Ver</a></td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($cxp)): ?>
          <tr><td colspan="9">Sin resultados en CXP.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  <?php endif; ?>
</body>
</html>