<?php
$u = is_array($user ?? null) ? $user : [];
$id = (int)($u['id'] ?? 0);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Editar usuario', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    .err { color:#b00020; font-size: .9em; }
    .input-err { border:1px solid #b00020; }
  </style>
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Editar usuario', ENT_QUOTES, 'UTF-8') ?></h1>
  <p><a href="/usuarios/<?= $id ?>">← Volver</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <p style="color:#0a7a0a;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="post" action="/usuarios/<?= $id ?>/editar">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <div>
      <label>Email</label><br>
      <input
        name="email"
        value="<?= htmlspecialchars((string)old('email', (string)($u['email'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
        class="<?= hasErr('email') ? 'input-err' : '' ?>"
        style="width: 320px;"
      >
      <?php if (hasErr('email')): ?><div class="err"><?= htmlspecialchars((string)err('email'), ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    </div>

    <div>
      <label>Nombre</label><br>
      <input
        name="nombre"
        value="<?= htmlspecialchars((string)old('nombre', (string)($u['nombre'] ?? ($u['nombres'] ?? ''))), ENT_QUOTES, 'UTF-8') ?>"
        class="<?= hasErr('nombre') ? 'input-err' : '' ?>"
        style="width: 320px;"
      >
      <?php if (hasErr('nombre')): ?><div class="err"><?= htmlspecialchars((string)err('nombre'), ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    </div>

    <div>
      <label>Password (opcional; si vacío NO se cambia)</label><br>
      <input
        type="password"
        name="password"
        value=""
        class="<?= hasErr('password') ? 'input-err' : '' ?>"
        style="width: 320px;"
      >
      <?php if (hasErr('password')): ?><div class="err"><?= htmlspecialchars((string)err('password'), ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    </div>

    <h3>Roles</h3>
    <?php
      $defaultRoleIds = is_array($user_role_ids ?? null) ? $user_role_ids : [];
      $sel = old('roles', $defaultRoleIds);
      if (!is_array($sel)) $sel = [];
      $selMap = [];
      foreach ($sel as $rid) $selMap[(int)$rid] = true;
    ?>
    <?php foreach (($roles ?? []) as $r): ?>
      <?php
        $rid = (int)($r['id'] ?? 0);
        $label = (string)($r['codigo'] ?? '');
        if ($label === '') $label = (string)($r['nombre'] ?? '');
      ?>
      <label style="display:block;">
        <input type="checkbox" name="roles[]" value="<?= $rid ?>" <?= isset($selMap[$rid]) ? 'checked' : '' ?>>
        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
        <?php
          $extra = (string)($r['nombre'] ?? '');
          if ((string)($r['codigo'] ?? '') !== '' && $extra !== '') {
              echo ' — ' . htmlspecialchars($extra, ENT_QUOTES, 'UTF-8');
          }
        ?>
      </label>
    <?php endforeach; ?>

    <h3>Permisos directos (opcionales)</h3>
    <p style="margin:0 0 6px 0; font-size:.95em;">
      Se aplican mediante el rol personal: <code><?= htmlspecialchars('USR_'.$id, ENT_QUOTES, 'UTF-8') ?></code>
    </p>

    <?php
      $defaultPermIds = is_array($direct_perm_ids ?? null) ? $direct_perm_ids : [];
      $selP = old('perm_ids', $defaultPermIds);
      if (!is_array($selP)) $selP = [];
      $selPMap = [];
      foreach ($selP as $pid) $selPMap[(int)$pid] = true;
    ?>

    <?php foreach (($permisos ?? []) as $p): ?>
      <?php $pid = (int)($p['id'] ?? 0); ?>
      <label style="display:block;">
        <input type="checkbox" name="perm_ids[]" value="<?= $pid ?>" <?= isset($selPMap[$pid]) ? 'checked' : '' ?>>
        <?= htmlspecialchars((string)($p['codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
      </label>
    <?php endforeach; ?>

    <?php if (empty($permisos ?? [])): ?>
      <div style="color:#666; font-size:.95em;">No hay permisos disponibles.</div>
    <?php endif; ?>

    <?php if (hasErr('perm_ids')): ?><div class="err"><?= htmlspecialchars((string)err('perm_ids'), ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <p><button type="submit">Guardar cambios</button></p>
  </form>
</body>
</html>