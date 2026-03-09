<?php
declare(strict_types=1);

use Erp2\Core\Auth;

$title = 'Compras';
require __DIR__ . '/../partials/app_shell_top.php';

$qVal = (string)($q ?? '');

$badgeEstado = static function(string $estado): string {
    return match ($estado) {
        'emitida'  => 'badge-success',
        'anulada'  => 'badge-danger',
        'borrador' => 'badge-muted',
        default    => 'badge-muted',
    };
};
?>

<div class="card" style="padding:16px;">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Listado</h3>
    <div class="table-actions">
      <?php if (Auth::has('compras.crear')): ?>
        <a class="btn btn-primary" href="/compras/crear">➕ Nueva compra</a>
      <?php endif; ?>
    </div>
  </div>

  <form method="get" action="/compras" style="margin-top:12px;">
    <div class="kv-grid" style="margin-top:0;">
      <div class="kv">
        <div class="k">Filtrar por número</div>
        <div class="v" style="font-weight:600;">
          <input
            id="q"
            class="input"
            name="q"
            value="<?= htmlspecialchars($qVal, ENT_QUOTES, 'UTF-8') ?>"
            maxlength="32"
            placeholder="Ej: C-000123"
            style="width:100%;"
          >
        </div>
      </div>

      <div class="kv">
        <div class="k">Acciones</div>
        <div class="v" style="font-weight:600;">
          <div class="table-actions">
            <button class="btn btn-primary" type="submit">Buscar</button>
            <a class="btn btn-secondary" href="/compras">Limpiar</a>
          </div>
        </div>
      </div>
    </div>
  </form>

  <div class="table-container">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Número</th>
          <th>Fecha</th>
          <th>Proveedor</th>
          <th>Estado</th>
          <th>Total</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($items ?? []) as $c): ?>
          <?php
            $id = (int)($c['id'] ?? 0);
            $estado = (string)($c['estado'] ?? '');
            $numero = (string)($c['numero'] ?? '');
          ?>
          <tr>
            <td><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <a class="link" href="/compras/<?= $id ?>">
                <?= htmlspecialchars($numero, ENT_QUOTES, 'UTF-8') ?>
              </a>
            </td>
            <td><?= htmlspecialchars((string)($c['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($c['tercero_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <span class="badge <?= $badgeEstado($estado) ?>">
                <?= htmlspecialchars($estado !== '' ? $estado : '—', ENT_QUOTES, 'UTF-8') ?>
              </span>
            </td>
            <td><?= htmlspecialchars((string)($c['total'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <div class="table-actions">
                <a class="btn btn-secondary" href="/compras/<?= $id ?>">Ver</a>

                <?php if (Auth::has('compras.emitir') && $estado === 'borrador'): ?>
                  <form method="post" action="/compras/<?= $id ?>/emitir" style="display:inline;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <button class="btn btn-primary" type="submit" onclick="return confirm('¿Emitir compra?');">Emitir</button>
                  </form>
                <?php endif; ?>

                <?php if (Auth::has('compras.anular') && $estado !== 'anulada'): ?>
                  <form method="post" action="/compras/<?= $id ?>/anular" style="display:inline;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <button class="btn btn-danger" type="submit" onclick="return confirm('¿Anular compra?');">Anular</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($items)): ?>
          <tr><td colspan="7">Sin resultados.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>