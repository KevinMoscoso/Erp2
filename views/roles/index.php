<?php
declare(strict_types=1);

use Erp2\Core\Auth;

$title = 'Roles';
$isAdmin = (bool)($is_admin ?? ((int)(Auth::user()['id'] ?? 0) === 1));

require __DIR__ . '/../partials/app_shell_top.php';
?>

<div class="card" style="padding:16px;">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Listado</h3>
    <div class="table-actions">
      <?php if ($isAdmin): ?>
        <a class="btn btn-primary" href="/roles/crear">➕ Crear rol</a>
      <?php endif; ?>
    </div>
  </div>

  <form method="get" action="/roles" style="margin-top:12px;">
    <div class="kv-grid" style="margin-top:0;">
      <div class="kv">
        <div class="k">Buscar (Nombre)</div>
        <div class="v" style="font-weight:600;">
          <input
            class="input"
            name="q"
            value="<?= htmlspecialchars((string)($q ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            maxlength="120"
            placeholder="nombre del rol (ej: Admin, Ventas, Compras)"
            style="width:100%;"
          >
        </div>
      </div>

      <div class="kv">
        <div class="k">Límite</div>
        <div class="v" style="font-weight:600;">
          <input
            class="input"
            name="limit"
            type="number"
            min="1"
            max="500"
            value="<?= htmlspecialchars((string)($limit ?? 200), ENT_QUOTES, 'UTF-8') ?>"
            style="width:100%;"
            placeholder="200"
          >
        </div>
      </div>

      <div class="kv">
        <div class="k">Acciones</div>
        <div class="v" style="font-weight:600;">
          <div class="table-actions">
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <a class="btn btn-secondary" href="/roles">Limpiar</a>
          </div>
        </div>
      </div>
    </div>
  </form>

  <div class="table-container">
    <table class="table" style="min-width: 680px;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Descripción</th>
          <th>Link</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($items ?? []) as $r): ?>
          <?php
            $id = (int)($r['id'] ?? 0);
            $nombre = (string)($r['nombre'] ?? '');
            $descripcion = (string)($r['descripcion'] ?? '');
            $isUsr = str_starts_with($nombre, 'USR_');
          ?>
          <tr>
            <td><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?php if ($isUsr): ?>
                <span class="badge badge-muted"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></span>
              <?php else: ?>
                <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8') ?></td>
            <td class="table-actions">
              <a class="btn btn-secondary" href="/roles/<?= $id ?>">Ver</a>
              <?php if ($isAdmin && $id > 0): ?>
                <a class="btn btn-secondary" href="/roles/<?= $id ?>/editar">Editar</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($items)): ?>
          <tr><td colspan="4">Sin resultados.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>