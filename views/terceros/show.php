<?php
declare(strict_types=1);

use Erp2\Core\Auth;

$title = 'Detalle de Tercero';
require __DIR__ . '/../partials/app_shell_top.php';

$t = is_array($tercero ?? null) ? (array)$tercero : [];
$id = (int)($t['id'] ?? 0);

$oldNombres  = (string)old('nombres', (string)old('nombre', ''));
$oldEmail    = (string)old('email', '');
$oldTelefono = (string)old('telefono', '');
$oldCargo    = (string)old('cargo', '');
$oldNotas    = (string)old('notas', '');

$errNombres = err('nombres') ?: err('nombre');

$tipo = (string)($t['tipo'] ?? '');
$tipoBadgeClass = 'badge-muted';
if ($tipo === 'cliente') $tipoBadgeClass = 'badge-success';
elseif ($tipo === 'proveedor') $tipoBadgeClass = 'badge';

$estado = $t['estado'] ?? null;
$estadoBadge = null;
if ($estado !== null && $estado !== '') {
  $isActive = ((string)$estado === '1' || (string)$estado === 'activo' || (string)$estado === 'A');
  $estadoBadge = $isActive
    ? '<span class="badge badge-success">Activo</span>'
    : '<span class="badge badge-danger">Inactivo</span>';
}
?>

<div class="card" style="padding:16px;">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Resumen</h3>
    <div class="table-actions">
      <a class="btn btn-secondary" href="/terceros">Volver</a>

      <?php if (Auth::has('terceros.editar')): ?>
        <a class="btn btn-primary" href="/terceros/<?= $id ?>/editar">✏️ Editar</a>
      <?php endif; ?>

      <?php if (Auth::has('terceros.eliminar')): ?>
        <form method="post" action="/terceros/<?= $id ?>/eliminar" style="display:inline;">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          <button class="btn btn-danger" type="submit">Eliminar</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="kv-grid">
    <div class="kv">
      <div class="k">ID</div>
      <div class="v"><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="kv">
      <div class="k">Tipo</div>
      <div class="v">
        <div class="table-actions">
          <span class="badge <?= $tipoBadgeClass ?>"><?= htmlspecialchars($tipo !== '' ? $tipo : '—', ENT_QUOTES, 'UTF-8') ?></span>
          <?php if ($estadoBadge !== null): ?>
            <?= $estadoBadge ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="kv">
      <div class="k">Nombre comercial</div>
      <div class="v"><?= htmlspecialchars((string)($t['nombre_comercial'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="kv">
      <div class="k">Identificación</div>
      <div class="v"><?= htmlspecialchars((string)($t['identificacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="kv">
      <div class="k">Email</div>
      <div class="v"><?= htmlspecialchars((string)($t['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <?php if (array_key_exists('telefono', $t)): ?>
      <div class="kv">
        <div class="k">Teléfono</div>
        <div class="v"><?= htmlspecialchars((string)($t['telefono'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
      </div>
    <?php endif; ?>

    <?php if (array_key_exists('direccion', $t)): ?>
      <div class="kv" style="grid-column: 1 / -1;">
        <div class="k">Dirección</div>
        <div class="v" style="font-weight:700;"><?= htmlspecialchars((string)($t['direccion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="card" style="padding:16px; margin-top:14px;">
  <div class="section-header" style="margin-bottom:10px;">
    <h3>Contactos</h3>
    <span class="muted">Asociados a este tercero</span>
  </div>

  <div class="table-container" style="margin-top:0;">
    <table class="table" style="min-width: 860px;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Email</th>
          <th>Teléfono</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($contactos ?? []) as $c): ?>
          <?php $cid = (int)($c['id'] ?? 0); ?>
          <tr>
            <td><?= htmlspecialchars((string)$cid, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($c['nombres'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($c['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($c['telefono'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <div class="table-actions">
                <?php if (Auth::has('terceros.editar')): ?>
                  <form method="post" action="/terceros/<?= $id ?>/contactos/<?= $cid ?>/eliminar" style="display:inline;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <button class="btn btn-danger" type="submit">Eliminar</button>
                  </form>
                <?php else: ?>
                  <span class="badge badge-muted">—</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($contactos)): ?>
          <tr><td colspan="5">Sin contactos.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if (Auth::has('terceros.editar')): ?>
    <div class="section" style="margin-top:16px;">
      <div class="section-header" style="margin-bottom:10px;">
        <h3>Agregar contacto</h3>
        <span class="muted">Completa los campos requeridos</span>
      </div>

      <?php if (err('contacto')): ?>
        <div class="alert alert-error" role="alert">
          <?= htmlspecialchars((string)err('contacto'), ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <form method="post" action="/terceros/<?= $id ?>/contactos/crear">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-grid" style="margin-top:0;">
          <div>
            <label for="nombres">Nombre</label>
            <input
              id="nombres"
              name="nombres"
              required
              maxlength="160"
              value="<?= htmlspecialchars($oldNombres, ENT_QUOTES, 'UTF-8') ?>"
              class="input <?= ($errNombres !== null && $errNombres !== '') ? 'error' : '' ?>"
              style="width:100%;"
            >
            <input type="hidden" name="nombre" value="<?= htmlspecialchars($oldNombres, ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($errNombres): ?>
              <div class="field-error"><?= htmlspecialchars((string)$errNombres, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

          <div>
            <label for="email">Email</label>
            <input
              id="email"
              name="email"
              type="email"
              maxlength="190"
              value="<?= htmlspecialchars($oldEmail, ENT_QUOTES, 'UTF-8') ?>"
              class="input <?= hasErr('email') ? 'error' : '' ?>"
              style="width:100%;"
            >
            <?php if (err('email')): ?>
              <div class="field-error"><?= htmlspecialchars((string)err('email'), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

          <div>
            <label for="telefono">Teléfono</label>
            <input
              id="telefono"
              name="telefono"
              maxlength="30"
              value="<?= htmlspecialchars($oldTelefono, ENT_QUOTES, 'UTF-8') ?>"
              class="input <?= hasErr('telefono') ? 'error' : '' ?>"
              style="width:100%;"
            >
            <?php if (err('telefono')): ?>
              <div class="field-error"><?= htmlspecialchars((string)err('telefono'), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

          <div>
            <label for="cargo">Cargo (opcional)</label>
            <input
              id="cargo"
              name="cargo"
              maxlength="80"
              value="<?= htmlspecialchars($oldCargo, ENT_QUOTES, 'UTF-8') ?>"
              class="input <?= hasErr('cargo') ? 'error' : '' ?>"
              style="width:100%;"
            >
            <?php if (err('cargo')): ?>
              <div class="field-error"><?= htmlspecialchars((string)err('cargo'), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

          <div style="grid-column: 1 / -1;">
            <label for="notas">Notas (opcional)</label>
            <input
              id="notas"
              name="notas"
              maxlength="255"
              value="<?= htmlspecialchars($oldNotas, ENT_QUOTES, 'UTF-8') ?>"
              class="input <?= hasErr('notas') ? 'error' : '' ?>"
              style="width:100%;"
            >
            <?php if (err('notas')): ?>
              <div class="field-error"><?= htmlspecialchars((string)err('notas'), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="form-actions">
          <button class="btn btn-primary" type="submit">Crear contacto</button>
        </div>
      </form>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>