<?php
declare(strict_types=1);

use Erp2\Core\Auth;

$title = $title ?? 'Detalle usuario';

$isAdmin = (bool)($is_admin ?? ((int)(Auth::user()['id'] ?? 0) === 1));
$u = is_array($user ?? null) ? $user : [];
$id = (int)($u['id'] ?? 0);

require __DIR__ . '/../partials/app_shell_top.php';
?>

<div class="card" style="padding:16px;">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Resumen</h3>
    <div class="table-actions">
      <a class="btn btn-secondary" href="/usuarios">Volver</a>
      <?php if ($isAdmin && $id > 0): ?>
        <a class="btn btn-primary" href="/usuarios/<?= $id ?>/editar">✏️ Editar</a>
      <?php endif; ?>
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

  <div class="kv-grid">
    <div class="kv">
      <div class="k">ID</div>
      <div class="v"><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="kv">
      <div class="k">Email</div>
      <div class="v"><?= htmlspecialchars((string)($u['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="kv">
      <div class="k">Nombre</div>
      <div class="v"><?= htmlspecialchars((string)($u['nombre'] ?? ($u['nombres'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="kv">
      <div class="k">Admin-only</div>
      <div class="v">
        <?php if ($isAdmin): ?>
          <span class="badge badge-success">Sí (id=1)</span>
        <?php else: ?>
          <span class="badge badge-muted">No</span>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="card" style="padding:16px; margin-top:14px;">
  <div class="section-header" style="margin-bottom:10px;">
    <h3>Roles asignados</h3>
    <span class="muted">Según asignación actual</span>
  </div>

  <div class="table-actions">
    <?php foreach (($roles ?? []) as $r): ?>
      <?php
        $label = (string)($r['codigo'] ?? '');
        if ($label === '') $label = (string)($r['nombre'] ?? '');
        $extra = (string)($r['nombre'] ?? '');
        $suffix = '';
        if ((string)($r['codigo'] ?? '') !== '' && $extra !== '') $suffix = ' — ' . $extra;
      ?>
      <span class="badge"><?= htmlspecialchars($label . $suffix, ENT_QUOTES, 'UTF-8') ?></span>
    <?php endforeach; ?>

    <?php if (empty($roles)): ?>
      <span class="badge badge-muted">Sin roles</span>
    <?php endif; ?>
  </div>
</div>

<div class="card" style="padding:16px; margin-top:14px;">
  <div class="section-header" style="margin-bottom:10px;">
    <h3>Permisos directos</h3>
    <span class="muted">Rol personal <?= htmlspecialchars('USR_'.$id, ENT_QUOTES, 'UTF-8') ?></span>
  </div>

  <div class="table-actions">
    <?php foreach (($perms_directos ?? []) as $c): ?>
      <span class="badge badge-muted"><?= htmlspecialchars((string)$c, ENT_QUOTES, 'UTF-8') ?></span>
    <?php endforeach; ?>

    <?php if (empty($perms_directos ?? [])): ?>
      <span class="badge badge-muted">Sin permisos directos</span>
    <?php endif; ?>
  </div>
</div>

<div class="card" style="padding:16px; margin-top:14px;">
  <div class="section-header" style="margin-bottom:10px;">
    <h3>Permisos efectivos</h3>
    <span class="muted">Acumulados por roles</span>
  </div>

  <div class="table-actions">
    <?php foreach (($perms_efectivos ?? []) as $c): ?>
      <span class="badge"><?= htmlspecialchars((string)$c, ENT_QUOTES, 'UTF-8') ?></span>
    <?php endforeach; ?>

    <?php if (empty($perms_efectivos ?? [])): ?>
      <span class="badge badge-muted">Sin permisos efectivos</span>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>