<?php
declare(strict_types=1);

$title = 'Editar permiso';
$permisoArr = is_array($permiso ?? null) ? $permiso : [];
$id = (int)($permisoArr['id'] ?? 0);

require __DIR__ . '/../partials/app_shell_top.php';

$csrfToken = '';
if (isset($csrf) && is_string($csrf) && $csrf !== '') {
  $csrfToken = $csrf;
} elseif (class_exists(\Erp2\Core\Csrf::class) && method_exists(\Erp2\Core\Csrf::class, 'token')) {
  $csrfToken = (string)\Erp2\Core\Csrf::token();
}
?>

<div class="card form-card">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Editar permiso</h3>
    <div class="table-actions">
      <a class="btn btn-secondary" href="/permisos">Volver</a>
    </div>
  </div>

  <form method="post" action="/permisos/<?= $id ?>/editar">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <div class="form-grid">
      <div style="grid-column: 1 / -1;">
        <label for="codigo">Código</label>
        <input
          id="codigo"
          name="codigo"
          maxlength="120"
          required
          pattern="[a-z0-9_.-]+"
          class="input <?= hasErr('codigo') ? 'error' : '' ?>"
          value="<?= htmlspecialchars((string)old('codigo', (string)($permisoArr['codigo'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
          style="width:100%; max-width: 520px;"
        >
        <?php if (hasErr('codigo')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('codigo'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Guardar cambios</button>
      <a class="btn btn-secondary" href="/permisos">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>