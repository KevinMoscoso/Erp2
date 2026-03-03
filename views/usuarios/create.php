<?php
declare(strict_types=1);

use Erp2\Core\Auth;

$title = $title ?? 'Crear usuario';
require __DIR__ . '/../partials/app_shell_top.php';
?>

<div class="card form-card">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Crear usuario</h3>
    <div class="table-actions">
      <a class="btn btn-secondary" href="/usuarios">Volver</a>
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

  <form method="post" action="/usuarios/crear">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <div class="form-grid">
      <div>
        <label for="email">Email</label>
        <input
          id="email"
          class="input <?= hasErr('email') ? 'error' : '' ?>"
          name="email"
          value="<?= htmlspecialchars((string)old('email',''), ENT_QUOTES, 'UTF-8') ?>"
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
          value="<?= htmlspecialchars((string)old('nombre',''), ENT_QUOTES, 'UTF-8') ?>"
          style="width:100%;"
        >
        <?php if (hasErr('nombre')): ?>
          <div class="field-error"><?= htmlspecialchars((string)err('nombre'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>

      <div style="grid-column: 1 / -1;">
        <label for="password">Password</label>
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
      </div>
    </div>

    <div class="section" style="margin-top:16px;">
      <div class="section-header" style="margin-bottom:8px;">
        <h3>Roles</h3>
        <span class="muted">Asignación por roles</span>
      </div>

      <?php
        $sel = old('roles', []);
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

      <?php if (hasErr('roles')): ?>
        <div class="field-error"><?= htmlspecialchars((string)err('roles'), ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
    </div>

    <div class="section">
      <div class="section-header" style="margin-bottom:8px;">
        <h3>Permisos directos (opcionales)</h3>
        <span class="muted">Se aplican mediante rol personal USR_&lt;id&gt;</span>
      </div>

      <p class="help" style="margin-top:0;">
        Se aplican mediante un rol personal <code>USR_&lt;id&gt;</code> para este usuario.
      </p>

      <?php
        $selP = old('perm_ids', []);
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
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn btn-secondary" href="/usuarios">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>