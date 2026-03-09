<?php
declare(strict_types=1);

$title = 'Auditoría';
require __DIR__ . '/../partials/app_shell_top.php';
?>

<div class="card" style="padding:16px;">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Registros</h3>
    <span class="muted">Ordenado por ID desc.</span>
  </div>

  <?php if (!empty($errors) && is_array($errors)): ?>
    <div class="alert alert-error" role="alert">
      <ul style="margin-left:18px;">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="get" action="/auditoria" style="margin-top:12px;">
    <div class="kv-grid" style="margin-top:0;">
      <div class="kv">
        <div class="k">Buscar</div>
        <div class="v" style="font-weight:600;">
          <input class="input" name="q" value="<?= htmlspecialchars((string)($q ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="120" placeholder="acción / entidad / email / id / ip" style="width:100%;">
        </div>
      </div>

      <div class="kv">
        <div class="k">Usuario ID</div>
        <div class="v" style="font-weight:600;">
          <input class="input" name="usuario_id" value="<?= htmlspecialchars((string)($usuario_id ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%;" placeholder="ej: 1">
        </div>
      </div>

      <div class="kv">
        <div class="k">Acción</div>
        <div class="v" style="font-weight:600;">
          <input class="input" name="accion" value="<?= htmlspecialchars((string)($accion ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="60" placeholder="ej: pagos.crear" style="width:100%;">
        </div>
      </div>

      <div class="kv">
        <div class="k">Entidad</div>
        <div class="v" style="font-weight:600;">
          <input class="input" name="entidad" value="<?= htmlspecialchars((string)($entidad ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="60" placeholder="ej: factura" style="width:100%;">
        </div>
      </div>

      <div class="kv">
        <div class="k">Desde</div>
        <div class="v" style="font-weight:600;">
          <input class="input" type="date" name="desde" value="<?= htmlspecialchars((string)($desde ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%;">
        </div>
      </div>

      <div class="kv">
        <div class="k">Hasta</div>
        <div class="v" style="font-weight:600;">
          <input class="input" type="date" name="hasta" value="<?= htmlspecialchars((string)($hasta ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%;">
        </div>
      </div>

      <div class="kv">
        <div class="k">Límite</div>
        <div class="v" style="font-weight:600;">
          <input class="input" name="limit" value="<?= htmlspecialchars((string)($limit ?? 200), ENT_QUOTES, 'UTF-8') ?>" style="width:100%;" placeholder="200">
        </div>
      </div>

      <div class="kv">
        <div class="k">Acciones</div>
        <div class="v" style="font-weight:600;">
          <div class="table-actions">
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <a class="btn btn-secondary" href="/auditoria">Limpiar</a>
          </div>
        </div>
      </div>
    </div>
  </form>

  <p class="muted" style="margin-top:10px;">
    Requiere permiso <code>auditoria.ver</code>.
  </p>

  <div class="table-container">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Fecha</th>
          <th>Usuario</th>
          <th>Acción</th>
          <th>Entidad</th>
          <th>Entidad ID</th>
          <th>IP</th>
          <th>Detalle</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($rows ?? []) as $r): ?>
          <?php
            $id = (int)($r['id'] ?? 0);
            $fecha = (string)($r['created_at'] ?? '');
            $uid = (int)($r['usuario_id'] ?? 0);
            $uEmail = (string)($r['usuario_email'] ?? '');
            $accionRow = (string)($r['accion'] ?? '');
            $entidadRow = (string)($r['entidad'] ?? '');
            $entId = (int)($r['entidad_id'] ?? 0);
            $ip = (string)($r['ip'] ?? '');
            $detalle = (string)($r['detalle_json'] ?? '');

            if (function_exists('mb_strlen') && function_exists('mb_substr')) {
              if (mb_strlen($detalle) > 600) $detalle = mb_substr($detalle, 0, 600) . '…';
            } else {
              if (strlen($detalle) > 600) $detalle = substr($detalle, 0, 600) . '…';
            }
          ?>
          <tr>
            <td>
              <a class="link" href="/auditoria/<?= $id ?>">
                <?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?>
              </a>
            </td>
            <td><?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <div style="font-weight:800;"><?= htmlspecialchars((string)$uid, ENT_QUOTES, 'UTF-8') ?></div>
              <?php if ($uEmail !== ''): ?>
                <div class="muted"><?= htmlspecialchars($uEmail, ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </td>
            <td><span class="badge"><?= htmlspecialchars($accionRow, ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><span class="badge badge-muted"><?= htmlspecialchars($entidadRow, ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><?= htmlspecialchars((string)$entId, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <pre class="table-pre"><?= htmlspecialchars($detalle, ENT_QUOTES, 'UTF-8') ?></pre>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="8">Sin resultados.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>