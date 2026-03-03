<?php
declare(strict_types=1);

use Erp2\Core\Auth;

$title = 'Detalle de Rol';

$isAdmin = (bool)($is_admin ?? ((int)(Auth::user()['id'] ?? 0) === 1));
$r = is_array($role ?? null) ? $role : [];
$id = (int)($r['id'] ?? 0);

$nombre = (string)($r['nombre'] ?? '');
$descripcion = (string)($r['descripcion'] ?? '');
$createdAt = array_key_exists('created_at', $r) ? (string)($r['created_at'] ?? '') : '';

require __DIR__ . '/../partials/app_shell_top.php';
?>

<div class="card" style="padding:16px;">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Resumen</h3>
    <div class="table-actions">
      <a class="btn btn-secondary" href="/roles">Volver</a>
      <?php if ($isAdmin && $id > 0): ?>
        <a class="btn btn-primary" href="/roles/<?= $id ?>/editar">✏️ Editar</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="kv-grid">
    <div class="kv">
      <div class="k">ID</div>
      <div class="v"><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="kv">
      <div class="k">Nombre</div>
      <div class="v">
        <?php if (str_starts_with($nombre, 'USR_')): ?>
          <span class="badge badge-muted"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></span>
        <?php else: ?>
          <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="kv">
      <div class="k">Descripción</div>
      <div class="v"><?= htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <?php if ($createdAt !== ''): ?>
      <div class="kv">
        <div class="k">Creado</div>
        <div class="v"><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?></div>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="card" style="padding:16px; margin-top:14px;">
  <div class="section-header" style="margin-bottom:10px;">
    <h3>Permisos asignados</h3>
    <span class="muted">Códigos de permisos del rol</span>
  </div>

  <div class="table-actions">
    <?php foreach (($permisos ?? []) as $p): ?>
      <span class="badge"><?= htmlspecialchars((string)($p['codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
    <?php endforeach; ?>

    <?php if (empty($permisos)): ?>
      <span class="badge badge-muted">Sin permisos</span>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>