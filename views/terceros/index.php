<?php
declare(strict_types=1);

use Erp2\Core\Auth;

$title = 'Terceros';
require __DIR__ . '/../partials/app_shell_top.php';

$items = $items ?? [];
$qVal = (string)($q ?? '');
?>
<div class="card" style="padding:16px;">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Listado</h3>
    <div class="table-actions">
      <?php if (Auth::has('terceros.crear')): ?>
        <a class="btn btn-primary" href="/terceros/crear">➕ Nuevo</a>
      <?php endif; ?>
    </div>
  </div>

  <form method="get" action="/terceros" style="margin-top:12px;">
    <div class="kv-grid" style="margin-top:0;">
      <div class="kv">
        <div class="k">Buscar</div>
        <div class="v" style="font-weight:600;">
          <input
            id="q"
            class="input"
            name="q"
            value="<?= htmlspecialchars($qVal, ENT_QUOTES, 'UTF-8') ?>"
            maxlength="160"
            placeholder="nombre, identificación, email…"
            style="width:100%;"
          >
        </div>
      </div>

      <div class="kv">
        <div class="k">Acciones</div>
        <div class="v" style="font-weight:600;">
          <div class="table-actions">
            <button class="btn btn-primary" type="submit">Buscar</button>
            <a class="btn btn-secondary" href="/terceros"><?= $qVal !== '' ? 'Limpiar' : 'Refrescar' ?></a>
          </div>
        </div>
      </div>
    </div>
  </form>

  <div class="table-container">
    <table class="table" style="min-width: 860px;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Tipo</th>
          <th>Nombre comercial</th>
          <th>Identificación</th>
          <th>Email</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $t): ?>
          <?php
            $id = (int)($t['id'] ?? 0);
            $tipo = (string)($t['tipo'] ?? '');
            $estado = $t['estado'] ?? null;

            $tipoBadgeClass = 'badge-muted';
            if ($tipo === 'cliente') $tipoBadgeClass = 'badge-success';
            elseif ($tipo === 'proveedor') $tipoBadgeClass = 'badge';

            $estadoBadge = null;
            if ($estado !== null && $estado !== '') {
              $isActive = ((string)$estado === '1' || (string)$estado === 'activo' || (string)$estado === 'A');
              $estadoBadge = $isActive
                ? '<span class="badge badge-success">Activo</span>'
                : '<span class="badge badge-danger">Inactivo</span>';
            }
          ?>
          <tr>
            <td><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <div class="table-actions">
                <span class="badge <?= $tipoBadgeClass ?>"><?= htmlspecialchars($tipo !== '' ? $tipo : '—', ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($estadoBadge !== null): ?>
                  <?= $estadoBadge ?>
                <?php endif; ?>
              </div>
            </td>
            <td>
              <a class="link" href="/terceros/<?= $id ?>">
                <?= htmlspecialchars((string)($t['nombre_comercial'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
              </a>
            </td>
            <td><?= htmlspecialchars((string)($t['identificacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($t['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <div class="table-actions">
                <a class="btn btn-secondary" href="/terceros/<?= $id ?>">Ver</a>

                <?php if (Auth::has('terceros.editar')): ?>
                  <a class="btn btn-secondary" href="/terceros/<?= $id ?>/editar">Editar</a>
                <?php endif; ?>

                <?php if (Auth::has('terceros.eliminar')): ?>
                  <form method="post" action="/terceros/<?= $id ?>/eliminar" style="display:inline;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <button class="btn btn-danger" type="submit">Eliminar</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($items)): ?>
          <tr><td colspan="6">Sin resultados.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>