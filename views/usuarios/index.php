<?php
declare(strict_types=1);

use Erp2\Core\Auth;

$title = $title ?? 'Usuarios';
$isAdmin = (bool)($is_admin ?? ((int)(Auth::user()['id'] ?? 0) === 1));

require __DIR__ . '/../partials/app_shell_top.php';
?>

<div class="card" style="padding:16px;">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Listado</h3>
    <div class="table-actions">
      <?php if ($isAdmin): ?>
        <a class="btn btn-secondary" href="/roles">Roles</a>
        <a class="btn btn-secondary" href="/permisos">Permisos</a>
        <a class="btn btn-primary" href="/usuarios/crear">➕ Crear usuario</a>
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

  <form method="get" action="/usuarios" style="margin-top:12px;">
    <div class="kv-grid" style="margin-top:0;">
      <div class="kv">
        <div class="k">Buscar</div>
        <div class="v" style="font-weight:600;">
          <input class="input" name="q" value="<?= htmlspecialchars((string)($q ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="120" placeholder="email / nombre" style="width:100%;">
        </div>
      </div>

      <div class="kv">
        <div class="k">Límite</div>
        <div class="v" style="font-weight:600;">
          <input class="input" name="limit" type="number" min="1" max="500" value="<?= htmlspecialchars((string)($limit ?? 200), ENT_QUOTES, 'UTF-8') ?>" style="width:100%;" placeholder="200">
        </div>
      </div>

      <div class="kv">
        <div class="k">Acciones</div>
        <div class="v" style="font-weight:600;">
          <div class="table-actions">
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <a class="btn btn-secondary" href="/usuarios">Limpiar</a>
          </div>
        </div>
      </div>
    </div>
  </form>

  <div class="table-container">
    <table class="table" style="min-width: 640px;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Email</th>
          <th>Nombre</th>
          <th>Link</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($items ?? []) as $u): ?>
          <?php
            $id = (int)($u['id'] ?? 0);
            $email = (string)($u['email'] ?? '');
            $nombre = (string)($u['nombre'] ?? ($u['nombres'] ?? ''));
          ?>
          <tr>
            <td><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></td>
            <td class="table-actions">
              <a class="btn btn-secondary" href="/usuarios/<?= $id ?>">Ver</a>
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