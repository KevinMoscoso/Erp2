<?php
declare(strict_types=1);

// Helpers: old(), err(), hasErr()

$csrfVal = '';
if (isset($csrf) && is_string($csrf) && $csrf !== '') {
    $csrfVal = $csrf;
} elseif (class_exists('\Erp2\Core\Csrf')) {
    $csrfVal = (string)\Erp2\Core\Csrf::token();
}

$proveedoresList = [];
if (isset($proveedores) && is_array($proveedores)) {
    $proveedoresList = $proveedores;
} elseif (isset($terceros) && is_array($terceros)) {
    $proveedoresList = $terceros;
}

$productosList = is_array($productos ?? null) ? $productos : [];

$fechaVal = (string) old('fecha', $today ?? date('Y-m-d'));
$terceroOld = (string) old('tercero_id', '');

$title = $title ?? 'Crear compra';
require __DIR__ . '/../partials/app_shell_top.php';
?>

<div class="card form-card">
  <div class="section-header" style="margin-bottom:12px;">
    <h3><?= htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8') ?></h3>
    <div class="table-actions">
      <a class="btn btn-secondary" href="/compras">Volver</a>
    </div>
  </div>

  <form method="post" action="/compras/crear">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfVal, ENT_QUOTES, 'UTF-8') ?>">

    <div class="form-grid" style="margin-top:0;">
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
        <label for="tercero_id">Proveedor</label>
        <select
          id="tercero_id"
          name="tercero_id"
          class="input <?= hasErr('tercero_id') ? 'error' : '' ?>"
          required
          style="width:100%;"
        >
          <option value="">-- seleccionar --</option>
          <?php foreach ($proveedoresList as $t): ?>
            <?php
              $tid = (string)($t['id'] ?? '');
              $sel = ($terceroOld !== '' && $terceroOld === $tid) ? 'selected' : '';
              $label = (string)($t['nombre'] ?? ($t['razon_social'] ?? ($t['nombre_comercial'] ?? ('Tercero #' . $tid))));
            ?>
            <option value="<?= htmlspecialchars($tid, ENT_QUOTES, 'UTF-8') ?>" <?= $sel ?>>
              <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (err('tercero_id')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('tercero_id'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="section" style="margin-top:16px;">
      <div class="section-header" style="margin-bottom:10px;">
        <h3>Líneas</h3>
        <span class="muted">Producto / Servicio</span>
      </div>

      <?php if (err('lines')): ?>
        <div class="alert alert-error" role="alert">
          <?= htmlspecialchars((string)err('lines'), ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <div class="table-container" style="margin-top:0;">
        <table class="table">
          <thead>
            <tr>
              <th style="width:24%;">Producto/Servicio</th>
              <th>Descripción</th>
              <th style="width:14%;">Cantidad</th>
              <th style="width:14%;">Costo unit.</th>
            </tr>
          </thead>
          <tbody>
            <?php for ($i = 0; $i < 5; $i++): ?>
              <?php
                $prodOld = (string) old("line_producto_id.$i", '');
                $descOld = (string) old("line_descripcion.$i", '');
                $qtyOld  = (string) old("line_cantidad.$i", '');
                $cosOld  = (string) old("line_costo_unitario.$i", '');

                $qtyKey = "line_cantidad.$i";
                $cosKey = "line_costo_unitario.$i";
              ?>
              <tr>
                <td>
                  <select name="line_producto_id[]" class="input" style="width:100%;">
                    <option value="">-- (opcional) --</option>
                    <?php foreach ($productosList as $p): ?>
                      <?php
                        $pid = (string)($p['id'] ?? '');
                        $sel = ($prodOld !== '' && $prodOld === $pid) ? 'selected' : '';
                        $pNombre = (string)($p['nombre'] ?? ($p['descripcion'] ?? ('Item #' . $pid)));
                        $pTipo = (string)($p['tipo'] ?? 'producto');
                      ?>
                      <option value="<?= htmlspecialchars($pid, ENT_QUOTES, 'UTF-8') ?>" <?= $sel ?>>
                        <?= htmlspecialchars($pNombre, ENT_QUOTES, 'UTF-8') ?><?= $pTipo ? ' (' . htmlspecialchars($pTipo, ENT_QUOTES, 'UTF-8') . ')' : '' ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </td>

                <td>
                  <input
                    type="text"
                    name="line_descripcion[]"
                    value="<?= htmlspecialchars($descOld, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Descripción"
                    class="input"
                    style="width:100%;"
                  >
                </td>

                <td>
                  <input
                    type="number"
                    name="line_cantidad[]"
                    value="<?= htmlspecialchars($qtyOld, ENT_QUOTES, 'UTF-8') ?>"
                    min="0.01"
                    step="0.01"
                    placeholder="1.00"
                    class="input <?= hasErr($qtyKey) ? 'error' : '' ?>"
                    style="width:100%;"
                  >
                  <?php if (err($qtyKey)): ?>
                    <div class="field-error"><?= htmlspecialchars((string)err($qtyKey), ENT_QUOTES, 'UTF-8') ?></div>
                  <?php endif; ?>
                </td>

                <td>
                  <input
                    type="number"
                    name="line_costo_unitario[]"
                    value="<?= htmlspecialchars($cosOld, ENT_QUOTES, 'UTF-8') ?>"
                    min="0"
                    step="0.01"
                    placeholder="0.00"
                    class="input <?= hasErr($cosKey) ? 'error' : '' ?>"
                    style="width:100%;"
                  >
                  <?php if (err($cosKey)): ?>
                    <div class="field-error"><?= htmlspecialchars((string)err($cosKey), ENT_QUOTES, 'UTF-8') ?></div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>

      <p class="muted" style="margin-top:10px;">
        Puedes dejar filas vacías. El sistema tomará solo las líneas válidas.
      </p>
    </div>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Guardar (borrador)</button>
      <a class="btn btn-secondary" href="/compras">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>