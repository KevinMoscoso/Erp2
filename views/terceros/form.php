<?php
declare(strict_types=1);

$title = (string)($title ?? 'Tercero');
require __DIR__ . '/../partials/app_shell_top.php';

$t = is_array($tercero ?? null) ? (array)$tercero : [];

$tipoDefault   = (string)($t['tipo'] ?? 'cliente');
$nombreDefault = (string)($t['nombre_comercial'] ?? '');
$identDefault  = (string)($t['identificacion'] ?? '');
$emailDefault  = (string)($t['email'] ?? '');

$tipoVal   = (string)old('tipo', $tipoDefault);
$nombreVal = (string)old('nombre_comercial', $nombreDefault);
$identVal  = (string)old('identificacion', $identDefault);
$emailVal  = (string)old('email', $emailDefault);

$actionUrl   = (string)($action ?? '/terceros/crear');
$mode        = (string)($mode ?? 'create');
$submitText  = ($mode === 'edit') ? 'Guardar cambios' : 'Crear tercero';
?>

<div class="card form-card">
  <div class="section-header" style="margin-bottom:12px;">
    <h3><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
    <div class="table-actions">
      <a class="btn btn-secondary" href="/terceros">Volver al listado</a>
    </div>
  </div>

  <form method="post" action="<?= htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <div class="form-grid" style="margin-top:0;">
      <div>
        <label for="tipo">Tipo</label>
        <select id="tipo" name="tipo" class="input <?= hasErr('tipo') ? 'error' : '' ?>" required style="width:100%;">
          <option value="cliente" <?= ($tipoVal === 'cliente') ? 'selected' : '' ?>>Cliente</option>
          <option value="proveedor" <?= ($tipoVal === 'proveedor') ? 'selected' : '' ?>>Proveedor</option>
          <option value="ambos" <?= ($tipoVal === 'ambos') ? 'selected' : '' ?>>Ambos</option>
        </select>
        <?php if (err('tipo')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('tipo'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>

      <div>
        <label for="nombre_comercial">Nombre comercial</label>
        <input
          id="nombre_comercial"
          name="nombre_comercial"
          type="text"
          maxlength="160"
          value="<?= htmlspecialchars($nombreVal, ENT_QUOTES, 'UTF-8') ?>"
          class="input <?= hasErr('nombre_comercial') ? 'error' : '' ?>"
          required
          style="width:100%;"
        >
        <?php if (err('nombre_comercial')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('nombre_comercial'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>

      <div>
        <label for="identificacion">Identificación (opcional)</label>
        <input
          id="identificacion"
          name="identificacion"
          type="text"
          maxlength="30"
          value="<?= htmlspecialchars($identVal, ENT_QUOTES, 'UTF-8') ?>"
          class="input <?= hasErr('identificacion') ? 'error' : '' ?>"
          style="width:100%;"
        >
        <?php if (err('identificacion')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('identificacion'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>

      <div>
        <label for="email">Email (opcional)</label>
        <input
          id="email"
          name="email"
          type="email"
          maxlength="190"
          value="<?= htmlspecialchars($emailVal, ENT_QUOTES, 'UTF-8') ?>"
          class="input <?= hasErr('email') ? 'error' : '' ?>"
          style="width:100%;"
        >
        <?php if (err('email')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('email'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= htmlspecialchars($submitText, ENT_QUOTES, 'UTF-8') ?></button>
      <a class="btn btn-secondary" href="/terceros">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>