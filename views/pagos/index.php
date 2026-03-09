<?php
declare(strict_types=1);

use Erp2\Core\Auth;

$title = $title ?? 'Pagos';
require __DIR__ . '/../partials/app_shell_top.php';

// UX: si ref_id viene vacío, no mostrar "0"
$refIdVal = '';
if (isset($ref_id)) {
    $tmp = trim((string)$ref_id);
    if ($tmp !== '' && (int)$tmp > 0) {
        $refIdVal = (string)((int)$tmp);
    }
}
?>
<div class="card" style="padding:16px;">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Listado</h3>
    <div class="table-actions">
      <?php if (Auth::has('pagos.crear')): ?>
        <a class="btn btn-primary" href="/pagos/crear">➕ Registrar pago</a>
      <?php endif; ?>
    </div>
  </div>

  <form method="get" action="/pagos" style="margin-top:12px;">
    <div class="kv-grid" style="margin-top:0;">
      <div class="kv">
        <div class="k">Buscar</div>
        <div class="v" style="font-weight:600;">
          <input
            class="input"
            name="q"
            value="<?= htmlspecialchars((string)($q ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            maxlength="120"
            placeholder="tercero / referencia / nota…"
            style="width:100%;"
          >
        </div>
      </div>

      <div class="kv">
        <div class="k">Tipo</div>
        <div class="v" style="font-weight:600;">
          <select class="input" name="tipo_ref" style="width:100%;">
            <option value="" <?= (($tipo_ref ?? '') === '') ? 'selected' : '' ?>>Todos</option>
            <option value="factura" <?= (($tipo_ref ?? '') === 'factura') ? 'selected' : '' ?>>Factura</option>
            <option value="compra" <?= (($tipo_ref ?? '') === 'compra') ? 'selected' : '' ?>>Compra</option>
          </select>
        </div>
      </div>

      <div class="kv">
        <div class="k">Ref ID</div>
        <div class="v" style="font-weight:600;">
          <input class="input" name="ref_id" value="<?= htmlspecialchars($refIdVal, ENT_QUOTES, 'UTF-8') ?>" style="width:100%;" placeholder="ej: 123">
        </div>
      </div>

      <div class="kv">
        <div class="k">Acciones</div>
        <div class="v" style="font-weight:600;">
          <div class="table-actions">
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <a class="btn btn-secondary" href="/pagos">Limpiar</a>
          </div>
        </div>
      </div>
    </div>
  </form>

  <div class="table-container">
    <table class="table" style="min-width: 980px;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Tipo</th>
          <th>Referencia</th>
          <th>Tercero</th>
          <th>Fecha</th>
          <th>Monto</th>
          <th>Método</th>
          <th>Ref.</th>
          <th>Nota</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($items ?? []) as $p): ?>
          <?php
            $pid = (int)($p['id'] ?? 0);
            $tref = (string)($p['tipo_ref'] ?? '');
            $rid = (int)($p['ref_id'] ?? 0);
            $link = ($tref === 'factura') ? ('/facturas/' . $rid) : ('/compras/' . $rid);
            $refNum = (string)($p['ref_numero'] ?? '');

            $badgeTipo = 'badge-muted';
            if ($tref === 'factura') $badgeTipo = 'badge-success';
            elseif ($tref === 'compra') $badgeTipo = 'badge';
          ?>
          <tr>
            <td><?= htmlspecialchars((string)$pid, ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="badge <?= $badgeTipo ?>"><?= htmlspecialchars($tref, ENT_QUOTES, 'UTF-8') ?></span></td>
            <td>
              <div class="table-actions">
                <a class="link" href="<?= htmlspecialchars($link, ENT_QUOTES, 'UTF-8') ?>">
                  <?= htmlspecialchars($refNum !== '' ? $refNum : ('#' . $rid), ENT_QUOTES, 'UTF-8') ?>
                </a>
                <?php if ($rid > 0): ?>
                  <span class="badge badge-muted">#<?= (int)$rid ?></span>
                <?php endif; ?>
              </div>
            </td>
            <td><?= htmlspecialchars((string)($p['tercero_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($p['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($p['monto'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($p['metodo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <span class="muted"><?= htmlspecialchars((string)($p['referencia'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            </td>
            <td>
              <span class="muted"><?= htmlspecialchars((string)($p['nota'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            </td>
            <td>
              <div class="table-actions">
                <?php if (Auth::has('pagos.eliminar')): ?>
                  <form method="post" action="/pagos/<?= $pid ?>/eliminar" style="display:inline;">
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

        <?php if (empty($items)): ?>
          <tr><td colspan="10">Sin resultados.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>