<?php
declare(strict_types=1);

$csrfVal = '';
if (isset($csrf) && is_string($csrf)) {
    $csrfVal = $csrf;
} elseif (class_exists('\Erp2\Core\Csrf')) {
    $csrfVal = (string)\Erp2\Core\Csrf::token();
}

$facturasList = is_array($facturas ?? null) ? $facturas : [];
$comprasList  = is_array($compras ?? null) ? $compras  : [];

$errorMsg = isset($error) ? (string)$error : '';
$successMsg = isset($success) ? (string)$success : '';

$tipoDefault = isset($tipo_ref) ? (string)$tipo_ref : (string)($_GET['tipo_ref'] ?? 'factura');
if (!in_array($tipoDefault, ['factura', 'compra'], true)) $tipoDefault = 'factura';

$refDefault = 0;
if (isset($ref_id)) {
    $refDefault = (int)$ref_id;
} elseif (isset($_GET['ref_id'])) {
    $refDefault = (int)$_GET['ref_id'];
}

$tipoVal = (string) old('tipo_ref', $tipoDefault);

$refFacturaVal = (string) old('ref_id_factura', $tipoDefault === 'factura' ? (string)$refDefault : '');
$refCompraVal  = (string) old('ref_id_compra',  $tipoDefault === 'compra'  ? (string)$refDefault : '');

$fechaVal = (string) old('fecha', date('Y-m-d'));
$montoVal = (string) old('monto', '');
$metodoVal = (string) old('metodo', '');
$referenciaVal = (string) old('referencia', '');
$notaVal = (string) old('nota', '');

$title = $title ?? 'Registrar pago';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    .err{color:#b00020;font-size:.95em;margin-top:4px}
    .field{margin:10px 0}
    .invalid{outline:2px solid #b00020}
  </style>
</head>
<body>

<h1><?= htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8') ?></h1>
<p><a href="/pagos">← Volver</a></p>

<?php if ($errorMsg !== ''): ?>
  <p style="color:#b00020;"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if ($successMsg !== ''): ?>
  <p style="color:#0a7a0a;"><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<?php if (!empty($selectedInfo) && is_array($selectedInfo)): ?>
  <fieldset style="margin:12px 0;">
    <legend>Referencia seleccionada</legend>
    <ul>
      <li><strong>Número:</strong> <?= htmlspecialchars((string)($selectedInfo['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
      <li><strong>Estado:</strong> <?= htmlspecialchars((string)($selectedInfo['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
      <li><strong>Tercero:</strong> <?= htmlspecialchars((string)($selectedInfo['tercero_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
      <li><strong>Total:</strong> <?= htmlspecialchars((string)($selectedInfo['total'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
      <li><strong>Pagado:</strong> <?= htmlspecialchars((string)($selectedInfo['pagado'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
      <li><strong>Saldo:</strong> <?= htmlspecialchars((string)($selectedInfo['saldo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
    </ul>
  </fieldset>
<?php endif; ?>

<form method="post" action="/pagos/crear">
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfVal, ENT_QUOTES, 'UTF-8') ?>">

  <input type="hidden" name="ref_id" id="ref_id" value="">

  <div class="field">
    <label for="tipo_ref">Tipo referencia</label><br>
    <select id="tipo_ref" name="tipo_ref" class="<?= hasErr('tipo_ref') ? 'invalid' : '' ?>">
      <option value="factura" <?= ($tipoVal === 'factura') ? 'selected' : '' ?>>Factura</option>
      <option value="compra"  <?= ($tipoVal === 'compra')  ? 'selected' : '' ?>>Compra</option>
    </select>
    <?php if (err('tipo_ref')): ?>
      <div class="err"><?= htmlspecialchars((string)err('tipo_ref'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
  </div>

  <div class="field">
    <label for="ref_id_factura">Factura (si tipo=Factura)</label><br>
    <select id="ref_id_factura" name="ref_id_factura">
      <option value="">-- seleccionar factura --</option>
      <?php foreach ($facturasList as $f): ?>
        <?php
          $fid = (string)($f['id'] ?? '');
          $sel = ($tipoVal === 'factura' && $refFacturaVal !== '' && $refFacturaVal === $fid) ? 'selected' : '';
          $num = (string)($f['numero'] ?? '');
          $est = (string)($f['estado'] ?? '');
          $tot = (string)($f['total'] ?? '');
        ?>
        <option value="<?= htmlspecialchars($fid, ENT_QUOTES, 'UTF-8') ?>" <?= $sel ?>>
          <?= htmlspecialchars($num, ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($est, ENT_QUOTES, 'UTF-8') ?> — Total: <?= htmlspecialchars($tot, ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="field">
    <label for="ref_id_compra">Compra (si tipo=Compra)</label><br>
    <select id="ref_id_compra" name="ref_id_compra">
      <option value="">-- seleccionar compra --</option>
      <?php foreach ($comprasList as $c): ?>
        <?php
          $cid = (string)($c['id'] ?? '');
          $sel = ($tipoVal === 'compra' && $refCompraVal !== '' && $refCompraVal === $cid) ? 'selected' : '';
          $num = (string)($c['numero'] ?? '');
          $est = (string)($c['estado'] ?? '');
          $tot = (string)($c['total'] ?? '');
        ?>
        <option value="<?= htmlspecialchars($cid, ENT_QUOTES, 'UTF-8') ?>" <?= $sel ?>>
          <?= htmlspecialchars($num, ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($est, ENT_QUOTES, 'UTF-8') ?> — Total: <?= htmlspecialchars($tot, ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>

    <?php if (err('ref_id')): ?>
      <div class="err"><?= htmlspecialchars((string)err('ref_id'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
  </div>

  <hr>

  <div class="field">
    <label for="fecha">Fecha</label><br>
    <input
      id="fecha"
      type="date"
      name="fecha"
      value="<?= htmlspecialchars($fechaVal, ENT_QUOTES, 'UTF-8') ?>"
      class="<?= hasErr('fecha') ? 'invalid' : '' ?>"
      required
    >
    <?php if (err('fecha')): ?>
      <div class="err"><?= htmlspecialchars((string)err('fecha'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
  </div>

  <div class="field">
    <label for="monto">Monto</label><br>
    <input
      id="monto"
      type="number"
      name="monto"
      min="0.01"
      step="0.01"
      value="<?= htmlspecialchars($montoVal, ENT_QUOTES, 'UTF-8') ?>"
      class="<?= hasErr('monto') ? 'invalid' : '' ?>"
      placeholder="0.00"
      required
    >
    <?php if (err('monto')): ?>
      <div class="err"><?= htmlspecialchars((string)err('monto'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
  </div>

  <div class="field">
    <label for="metodo">Método</label><br>
    <input
      id="metodo"
      type="text"
      name="metodo"
      maxlength="30"
      value="<?= htmlspecialchars($metodoVal, ENT_QUOTES, 'UTF-8') ?>"
      class="<?= hasErr('metodo') ? 'invalid' : '' ?>"
      placeholder="efectivo / transferencia / tarjeta"
    >
    <?php if (err('metodo')): ?>
      <div class="err"><?= htmlspecialchars((string)err('metodo'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
  </div>

  <div class="field">
    <label for="referencia">Referencia</label><br>
    <input
      id="referencia"
      type="text"
      name="referencia"
      maxlength="100"
      value="<?= htmlspecialchars($referenciaVal, ENT_QUOTES, 'UTF-8') ?>"
      class="<?= hasErr('referencia') ? 'invalid' : '' ?>"
      placeholder="Nro comprobante"
    >
    <?php if (err('referencia')): ?>
      <div class="err"><?= htmlspecialchars((string)err('referencia'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
  </div>

  <div class="field">
    <label for="nota">Nota</label><br>
    <input
      id="nota"
      type="text"
      name="nota"
      maxlength="255"
      value="<?= htmlspecialchars($notaVal, ENT_QUOTES, 'UTF-8') ?>"
      class="<?= hasErr('nota') ? 'invalid' : '' ?>"
      style="width:100%;"
    >
    <?php if (err('nota')): ?>
      <div class="err"><?= htmlspecialchars((string)err('nota'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
  </div>

  <div class="field" style="margin-top:14px;">
    <button type="submit">Guardar pago</button>
  </div>

  <p style="margin-top:10px;">
    <small>Nota: el tercero se toma automáticamente de la factura/compra seleccionada.</small>
  </p>
</form>

<script>
(function(){
  function syncRefId(){
    var tipo = document.getElementById('tipo_ref');
    var selF = document.getElementById('ref_id_factura');
    var selC = document.getElementById('ref_id_compra');
    var hid  = document.getElementById('ref_id');
    if (!tipo || !selF || !selC || !hid) return;

    if (tipo.value === 'factura') {
      hid.value = selF.value || '';
      selF.disabled = false;
      selC.disabled = true;
    } else {
      hid.value = selC.value || '';
      selF.disabled = true;
      selC.disabled = false;
    }
  }

  document.addEventListener('change', function(e){
    if (!e.target) return;
    if (e.target.id === 'tipo_ref' || e.target.id === 'ref_id_factura' || e.target.id === 'ref_id_compra') {
      syncRefId();
    }
  });

  syncRefId();
})();
</script>

</body>
</html>