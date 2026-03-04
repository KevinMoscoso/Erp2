<?php
declare(strict_types=1);

$title = 'Permisos';
require __DIR__ . '/../partials/app_shell_top.php';
?>

<div class="card" style="padding:16px;">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Listado</h3>
    <div class="table-actions">
      <a class="btn btn-primary" href="/permisos/crear">➕ Crear permiso</a>
    </div>
  </div>

  <form method="get" action="/permisos" style="margin-top:12px;">
    <div class="kv-grid" style="margin-top:0;">
      <div class="kv">
        <div class="k">Buscar</div>
        <div class="v" style="font-weight:600;">
          <input
            class="input"
            name="q"
            value="<?= htmlspecialchars((string)($q ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            maxlength="120"
            placeholder="código o ID"
            style="width:100%;"
          >
        </div>
      </div>

      <div class="kv">
        <div class="k">Límite</div>
        <div class="v" style="font-weight:600;">
          <input
            class="input"
            type="number"
            name="limit"
            min="1"
            max="500"
            step="1"
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
            <a class="btn btn-secondary" href="/permisos">Limpiar</a>
          </div>
        </div>
      </div>
    </div>
  </form>

  <div class="table-container">
    <table class="table" style="min-width: 720px;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Código</th>
          <th style="width:220px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($items ?? []) as $p): ?>
          <?php
            $id = (int)($p['id'] ?? 0);
            $codigo = (string)($p['codigo'] ?? '');

            $pref = '';
            if ($codigo !== '') {
              $pos = strpos($codigo, '.');
              if ($pos !== false) $pref = substr($codigo, 0, $pos);
            }

            $csrfToken = '';
            if (isset($csrf) && is_string($csrf) && $csrf !== '') {
              $csrfToken = $csrf;
            } elseif (class_exists(\Erp2\Core\Csrf::class) && method_exists(\Erp2\Core\Csrf::class, 'token')) {
              $csrfToken = (string)\Erp2\Core\Csrf::token();
            }
          ?>
          <tr>
            <td><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <div class="table-actions">
                <?php if ($pref !== ''): ?>
                  <span class="badge badge-muted"><?= htmlspecialchars($pref, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <span class="badge"><?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?></span>
              </div>
            </td>
            <td>
              <div class="table-actions">
                <a class="btn btn-secondary" href="/permisos/<?= $id ?>/editar">Editar</a>

                <?php if ($csrfToken !== ''): ?>
                  <form method="post"
                        action="/permisos/<?= $id ?>/eliminar"
                        style="display:inline;">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button class="btn btn-danger" type="submit">Eliminar</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($items)): ?>
          <tr><td colspan="3">Sin resultados.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>