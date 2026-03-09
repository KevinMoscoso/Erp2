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

$tipoDefault = isset($tipo_ref) ? (string)$tipo_ref : (string)($_GET['tipo_ref'] ?? 'factura');
if (!in_array($tipoDefault, ['factura', 'compra'], true)) $tipoDefault = 'factura';

$refDefault = 0;
if (isset($ref_id)) {
    $refDefault = (int)$ref_id;
} elseif (isset($_GET['ref_id'])) {
    $refDefault = (int)$_GET['ref_id'];
}

$tipoVal = (string) old('tipo_ref', $tipoDefault);

// Mantener compatibilidad con la lógica original
$refFacturaVal = (string) old('ref_id_factura', $tipoDefault === 'factura' ? (string)$refDefault : '');
$refCompraVal  = (string) old('ref_id_compra',  $tipoDefault === 'compra'  ? (string)$refDefault : '');

$fechaVal = (string) old('fecha', date('Y-m-d'));
$montoVal = (string) old('monto', '');
$metodoVal = (string) old('metodo', '');
$referenciaVal = (string) old('referencia', '');
$notaVal = (string) old('nota', '');

$title = $title ?? 'Registrar pago';

require __DIR__ . '/../partials/app_shell_top.php';
?>

<div class="card form-card">
  <div class="section-header" style="margin-bottom:12px;">
    <h3><?= htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8') ?></h3>
    <div class="table-actions">
      <a class="btn btn-secondary" href="/pagos">Volver</a>
    </div>
  </div>

  <?php if (!empty($selectedInfo) && is_array($selectedInfo)): ?>
    <div class="card" style="padding:12px; margin-bottom:12px; box-shadow:none;">
      <div class="section-header" style="margin-bottom:8px;">
        <h3 style="font-size:14px;">Referencia seleccionada</h3>
        <span class="muted">Resumen</span>
      </div>
      <div class="kv-grid" style="margin-top:0;">
        <div class="kv"><div class="k">Número</div><div class="v"><?= htmlspecialchars((string)($selectedInfo['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div></div>
        <div class="kv"><div class="k">Estado</div><div class="v"><?= htmlspecialchars((string)($selectedInfo['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div></div>
        <div class="kv"><div class="k">Tercero</div><div class="v"><?= htmlspecialchars((string)($selectedInfo['tercero_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div></div>
        <div class="kv"><div class="k">Total</div><div class="v"><?= htmlspecialchars((string)($selectedInfo['total'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div></div>
        <div class="kv"><div class="k">Pagado</div><div class="v"><?= htmlspecialchars((string)($selectedInfo['pagado'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div></div>
        <div class="kv"><div class="k">Saldo</div><div class="v"><?= htmlspecialchars((string)($selectedInfo['saldo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div></div>
      </div>
    </div>
  <?php endif; ?>

  <form method="post" action="/pagos/crear">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfVal, ENT_QUOTES, 'UTF-8') ?>">

    <!-- CLAVE: el controlador espera ref_id -->
    <input type="hidden" name="ref_id" id="ref_id" value="">

    <div class="form-grid" style="margin-top:0;">
      <div>
        <label for="tipo_ref">Tipo referencia</label>
        <select id="tipo_ref" name="tipo_ref" class="input <?= hasErr('tipo_ref') ? 'error' : '' ?>" style="width:100%;">
          <option value="factura" <?= ($tipoVal === 'factura') ? 'selected' : '' ?>>Factura</option>
          <option value="compra"  <?= ($tipoVal === 'compra')  ? 'selected' : '' ?>>Compra</option>
        </select>
        <?php if (err('tipo_ref')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('tipo_ref'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <div class="help">Selecciona el tipo y luego la referencia correspondiente.</div>
      </div>

      <div>
        <label for="ref_id_factura">Factura (si tipo=Factura)</label>
        <select
          id="ref_id_factura"
          name="ref_id_factura"
          class="input <?= hasErr('ref_id') ? 'error' : '' ?>"
          style="width:100%;"
        >
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

      <div>
        <label for="ref_id_compra">Compra (si tipo=Compra)</label>
        <select
          id="ref_id_compra"
          name="ref_id_compra"
          class="input <?= hasErr('ref_id') ? 'error' : '' ?>"
          style="width:100%;"
        >
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
          <div class="field-error"><?= htmlspecialchars((string)err('ref_id'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <div class="help">Nota: el tercero se toma automáticamente de la factura/compra seleccionada.</div>
      </div>

      <div>
        <label for="fecha">Fecha</label>
        <input
          id="fecha"
          type="date"
          name="fecha"
          value="<?= htmlspecialchars($fechaVal, ENT_QUOTES, 'UTF-8') ?>"
          class="input <?= hasErr('fecha') ? 'error' : '' ?>"
          required
          style="width:100%;"
        >
        <?php if (err('fecha')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('fecha'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>

      <div>
        <label for="monto">Monto</label>
        <input
          id="monto"
          type="number"
          name="monto"
          min="0.01"
          step="0.01"
          value="<?= htmlspecialchars($montoVal, ENT_QUOTES, 'UTF-8') ?>"
          class="input <?= hasErr('monto') ? 'error' : '' ?>"
          placeholder="0.00"
          required
          style="width:100%;"
        >
        <?php if (err('monto')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('monto'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>

      <div>
        <label for="metodo">Método</label>
        <input
          id="metodo"
          type="text"
          name="metodo"
          maxlength="30"
          value="<?= htmlspecialchars($metodoVal, ENT_QUOTES, 'UTF-8') ?>"
          class="input <?= hasErr('metodo') ? 'error' : '' ?>"
          placeholder="efectivo / transferencia / tarjeta"
          style="width:100%;"
        >
        <?php if (err('metodo')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('metodo'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>

      <div>
        <label for="referencia">Referencia</label>
        <input
          id="referencia"
          type="text"
          name="referencia"
          maxlength="100"
          value="<?= htmlspecialchars($referenciaVal, ENT_QUOTES, 'UTF-8') ?>"
          class="input <?= hasErr('referencia') ? 'error' : '' ?>"
          placeholder="Nro comprobante"
          style="width:100%;"
        >
        <?php if (err('referencia')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('referencia'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>

      <div style="grid-column: 1 / -1;">
        <label for="nota">Nota</label>
        <input
          id="nota"
          type="text"
          name="nota"
          maxlength="255"
          value="<?= htmlspecialchars($notaVal, ENT_QUOTES, 'UTF-8') ?>"
          class="input <?= hasErr('nota') ? 'error' : '' ?>"
          style="width:100%;"
        >
        <?php if (err('nota')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('nota'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Guardar pago</button>
      <a class="btn btn-secondary" href="/pagos">Cancelar</a>
    </div>
  </form>

  <p class="muted" style="margin-top:10px;">
    Nota: el tercero se toma automáticamente de la factura/compra seleccionada.
  </p>
</div>

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

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>