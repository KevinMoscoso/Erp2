<?php
declare(strict_types=1);

$title = $title ?? 'Editar usuario';

$u = is_array($user ?? null) ? $user : [];
$id = (int)($u['id'] ?? 0);

require __DIR__ . '/../partials/app_shell_top.php';
?>

<div class="card form-card">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Editar usuario</h3>
    <div class="table-actions">
      <a class="btn btn-secondary" href="/usuarios/<?= $id ?>">Volver</a>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-error" role="alert">
      <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <div class="alert alert-success" role="alert">
      <?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <form method="post" action="/usuarios/<?= $id ?>/editar">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <div class="form-grid">
      <div>
        <label for="email">Email</label>
        <input
          id="email"
          class="input <?= hasErr('email') ? 'error' : '' ?>"
          name="email"
          value="<?= htmlspecialchars((string)old('email', (string)($u['email'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
          style="width:100%;"
        >
        <?php if (hasErr('email')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('email'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>

      <div>
        <label for="nombre">Nombre</label>
        <input
          id="nombre"
          class="input <?= hasErr('nombre') ? 'error' : '' ?>"
          name="nombre"
          value="<?= htmlspecialchars((string)old('nombre', (string)($u['nombre'] ?? ($u['nombres'] ?? ''))), ENT_QUOTES, 'UTF-8') ?>"
          style="width:100%;"
        >
        <?php if (hasErr('nombre')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('nombre'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>

      <div style="grid-column: 1 / -1;">
        <label for="password">Password (opcional; si vacío NO se cambia)</label>
        <input
          id="password"
          type="password"
          class="input <?= hasErr('password') ? 'error' : '' ?>"
          name="password"
          value=""
          style="width:100%; max-width: 520px;"
        >
        <?php if (hasErr('password')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('password'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <div class="help">Deja el campo vacío si no deseas actualizar la contraseña.</div>
      </div>
    </div>

    <div class="section" style="margin-top:16px;">
      <div class="section-header" style="margin-bottom:8px;">
        <h3>Roles</h3>
        <span class="muted">Asignación por roles</span>
      </div>

      <?php
        $defaultRoleIds = is_array($user_role_ids ?? null) ? $user_role_ids : [];
        $sel = old('roles', $defaultRoleIds);
        if (!is_array($sel)) $sel = [];
        $selMap = [];
        foreach ($sel as $rid) $selMap[(int)$rid] = true;
      ?>

      <div class="check-grid">
        <?php foreach (($roles ?? []) as $r): ?>
          <?php
            $rid = (int)($r['id'] ?? 0);
            $label = (string)($r['codigo'] ?? '');
            if ($label === '') $label = (string)($r['nombre'] ?? '');
            $extra = (string)($r['nombre'] ?? '');
            $showExtra = ((string)($r['codigo'] ?? '') !== '' && $extra !== '');
          ?>
          <label class="check">
            <input type="checkbox" name="roles[]" value="<?= $rid ?>" <?= isset($selMap[$rid]) ? 'checked' : '' ?>>
            <span>
              <div class="check-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
              <?php if ($showExtra): ?>
                <div class="check-sub"><?= htmlspecialchars($extra, ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </span>
          </label>
        <?php endforeach; ?>

        <?php if (empty($roles ?? [])): ?>
          <div class="muted">Sin roles disponibles.</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="section">
      <div class="section-header" style="margin-bottom:8px;">
        <h3>Permisos directos (opcionales)</h3>
        <span class="muted">Rol personal <?= htmlspecialchars('USR_'.$id, ENT_QUOTES, 'UTF-8') ?></span>
      </div>

      <p class="help" style="margin-top:0;">
        Se aplican mediante el rol personal: <code><?= htmlspecialchars('USR_'.$id, ENT_QUOTES, 'UTF-8') ?></code>
      </p>

      <?php
        $defaultPermIds = is_array($direct_perm_ids ?? null) ? $direct_perm_ids : [];
        $selP = old('perm_ids', $defaultPermIds);
        if (!is_array($selP)) $selP = [];
        $selPMap = [];
        foreach ($selP as $pid) $selPMap[(int)$pid] = true;
      ?>

      <?php if (!empty($permisos ?? [])): ?>
        <div class="check-grid">
          <?php foreach (($permisos ?? []) as $p): ?>
            <?php $pid = (int)($p['id'] ?? 0); ?>
            <label class="check">
              <input type="checkbox" name="perm_ids[]" value="<?= $pid ?>" <?= isset($selPMap[$pid]) ? 'checked' : '' ?>>
              <span>
                <div class="check-label"><?= htmlspecialchars((string)($p['codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
              </span>
            </label>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="muted">No hay permisos disponibles.</div>
      <?php endif; ?>

      <?php if (hasErr('perm_ids')): ?>
        <div class="field-error"><?= htmlspecialchars((string)err('perm_ids'), ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
    </div>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Guardar cambios</button>
      <a class="btn btn-secondary" href="/usuarios/<?= $id ?>">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>