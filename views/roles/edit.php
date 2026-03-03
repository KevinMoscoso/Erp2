<?php
declare(strict_types=1);

$title = 'Editar rol';

$r = is_array($role ?? null) ? $role : [];
$id = (int)($r['id'] ?? 0);

require __DIR__ . '/../partials/app_shell_top.php';
?>

<div class="card form-card">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Editar rol</h3>
    <div class="table-actions">
      <a class="btn btn-secondary" href="/roles/<?= $id ?>">Volver</a>
    </div>
  </div>

  <form method="post" action="/roles/<?= $id ?>/editar">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <div class="form-grid">
      <div>
        <label for="nombre">Nombre</label>
        <input
          id="nombre"
          class="input <?= hasErr('nombre') ? 'error' : '' ?>"
          name="nombre"
          value="<?= htmlspecialchars((string)old('nombre', (string)($r['nombre'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
          style="width:100%;"
        >
        <?php if (hasErr('nombre')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('nombre'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>

      <div>
        <label for="descripcion">Descripción</label>
        <input
          id="descripcion"
          class="input <?= hasErr('descripcion') ? 'error' : '' ?>"
          name="descripcion"
          value="<?= htmlspecialchars((string)old('descripcion', (string)($r['descripcion'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
          style="width:100%;"
        >
        <?php if (hasErr('descripcion')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('descripcion'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="section" style="margin-top:16px;">
      <div class="section-header" style="margin-bottom:8px;">
        <h3>Permisos del rol</h3>
        <span class="muted">Selecciona los permisos asignados</span>
      </div>

      <?php
        $defaultIds = is_array($permiso_ids ?? null) ? $permiso_ids : [];
        $sel = old('permisos', $defaultIds);
        if (!is_array($sel)) $sel = [];
        $selMap = [];
        foreach ($sel as $pid) $selMap[(int)$pid] = true;
      ?>

      <?php if (!empty($permisos_all ?? [])): ?>
        <div class="check-grid">
          <?php foreach (($permisos_all ?? []) as $p): ?>
            <?php $pid = (int)($p['id'] ?? 0); ?>
            <label class="check">
              <input type="checkbox" name="permisos[]" value="<?= $pid ?>" <?= isset($selMap[$pid]) ? 'checked' : '' ?>>
              <span>
                <div class="check-label"><?= htmlspecialchars((string)($p['codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
              </span>
            </label>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="muted">No hay permisos disponibles.</div>
      <?php endif; ?>

      <?php if (hasErr('permisos')): ?>
        <div class="field-error"><?= htmlspecialchars((string)err('permisos'), ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
    </div>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Guardar cambios</button>
      <a class="btn btn-secondary" href="/roles/<?= $id ?>">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>