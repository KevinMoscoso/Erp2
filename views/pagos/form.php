<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Registrar pago', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Registrar pago', ENT_QUOTES, 'UTF-8') ?></h1>

  <p><a href="/pagos">← Volver</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if (!empty($selectedInfo) && is_array($selectedInfo)): ?>
    <fieldset style="margin: 10px 0;">
      <legend>Referencia seleccionada</legend>
      <ul>
        <li><strong>Número:</strong> <?= htmlspecialchars((string)$selectedInfo['numero'], ENT_QUOTES, 'UTF-8') ?></li>
        <li><strong>Estado:</strong> <?= htmlspecialchars((string)$selectedInfo['estado'], ENT_QUOTES, 'UTF-8') ?></li>
        <li><strong>Tercero:</strong> <?= htmlspecialchars((string)$selectedInfo['tercero_nombre'], ENT_QUOTES, 'UTF-8') ?></li>
        <li><strong>Total:</strong> <?= htmlspecialchars((string)$selectedInfo['total'], ENT_QUOTES, 'UTF-8') ?></li>
        <li><strong>Pagado:</strong> <?= htmlspecialchars((string)$selectedInfo['pagado'], ENT_QUOTES, 'UTF-8') ?></li>
        <li><strong>Saldo:</strong> <?= htmlspecialchars((string)$selectedInfo['saldo'], ENT_QUOTES, 'UTF-8') ?></li>
      </ul>
    </fieldset>
  <?php endif; ?>

  <form method="post" action="/pagos/crear">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <div>
      <label>Tipo referencia</label><br>
      <select name="tipo_ref">
        <option value="factura" <?= (($tipo_ref ?? '') === 'factura') ? 'selected' : '' ?>>Factura</option>
        <option value="compra"  <?= (($tipo_ref ?? '') === 'compra')  ? 'selected' : '' ?>>Compra</option>
      </select>
      <small>(Selecciona una factura o una compra en los selects de abajo)</small>
    </div>

    <hr>

    <div>
      <label>Factura (si tipo=Factura)</label><br>
      <select name="ref_id_factura">
        <option value="">-- seleccionar factura --</option>
        <?php foreach (($facturas ?? []) as $f): ?>
          <?php
            $fid = (int)($f['id'] ?? 0);
            $sel = (($tipo_ref ?? '') === 'factura' && (int)($ref_id ?? 0) === $fid) ? 'selected' : '';
          ?>
          <option value="<?= $fid ?>" <?= $sel ?>>
            <?= htmlspecialchars((string)($f['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            — <?= htmlspecialchars((string)($f['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            — Total: <?= htmlspecialchars((string)($f['total'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="margin-top:8px;">
      <label>Compra (si tipo=Compra)</label><br>
      <select name="ref_id_compra">
        <option value="">-- seleccionar compra --</option>
        <?php foreach (($compras ?? []) as $c): ?>
          <?php
            $cid = (int)($c['id'] ?? 0);
            $sel = (($tipo_ref ?? '') === 'compra' && (int)($ref_id ?? 0) === $cid) ? 'selected' : '';
          ?>
          <option value="<?= $cid ?>" <?= $sel ?>>
            <?= htmlspecialchars((string)($c['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            — <?= htmlspecialchars((string)($c['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            — Total: <?= htmlspecialchars((string)($c['total'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <hr>

    <div>
      <label>Fecha</label><br>
      <input type="date" name="fecha" value="<?= htmlspecialchars((string)($today ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8') ?>" required>
    </div>

    <div style="margin-top:8px;">
      <label>Monto</label><br>
      <input name="monto" inputmode="decimal" placeholder="0.00" required>
    </div>

    <div style="margin-top:8px;">
      <label>Método (ej: efectivo, transferencia, tarjeta)</label><br>
      <input name="metodo" maxlength="30" placeholder="efectivo">
    </div>

    <div style="margin-top:8px;">
      <label>Referencia (nro comprobante)</label><br>
      <input name="referencia" maxlength="100">
    </div>

    <div style="margin-top:8px;">
      <label>Nota</label><br>
      <input name="nota" maxlength="255" style="width: 100%;">
    </div>

    <div style="margin-top:12px;">
      <button type="submit">Guardar pago</button>
    </div>
  </form>

  <p style="margin-top:12px;">
    Nota: el tercero se toma automáticamente de la factura/compra seleccionada (no del formulario).
  </p>
</body>
</html>