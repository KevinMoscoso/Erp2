<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Auditoría', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    .flash-error { color:#b00020; }
    .flash-success { color:#0a7a0a; }
    .muted { color:#666; font-size: 12px; }
    .filters label { display:inline-block; margin-right: 6px; }
    .filters input, .filters select { margin-right: 10px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
    th { background: #f7f7f7; text-align: left; }
    pre { white-space: pre-wrap; margin: 0; }
  </style>
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Auditoría', ENT_QUOTES, 'UTF-8') ?></h1>

  <p>
    <a href="/">Inicio</a>
  </p>

  <?php if (!empty($flash_error)): ?>
    <p class="flash-error"><?= htmlspecialchars((string)$flash_error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if (!empty($flash_success)): ?>
    <p class="flash-success"><?= htmlspecialchars((string)$flash_success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if (!empty($errors) && is_array($errors)): ?>
    <div class="flash-error">
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form class="filters" method="get" action="/auditoria">
    <label>Buscar</label>
    <input name="q" value="<?= htmlspecialchars((string)($q ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="120" placeholder="acción / entidad / email / id / ip">

    <label>Usuario ID</label>
    <input name="usuario_id" value="<?= htmlspecialchars((string)($usuario_id ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:90px;">

    <label>Acción</label>
    <input name="accion" value="<?= htmlspecialchars((string)($accion ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="60" placeholder="ej: pagos.crear">

    <label>Entidad</label>
    <input name="entidad" value="<?= htmlspecialchars((string)($entidad ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="60" placeholder="ej: factura">

    <label>Desde</label>
    <input type="date" name="desde" value="<?= htmlspecialchars((string)($desde ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Hasta</label>
    <input type="date" name="hasta" value="<?= htmlspecialchars((string)($hasta ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Límite</label>
    <input name="limit" value="<?= htmlspecialchars((string)($limit ?? 200), ENT_QUOTES, 'UTF-8') ?>" style="width:80px;">

    <button type="submit">Filtrar</button>
    <a href="/auditoria">Limpiar</a>
  </form>

  <p class="muted">Requiere permiso <code>auditoria.ver</code>. Ordenado por ID desc.</p>

  <hr>

  <table>
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
          if (mb_strlen($detalle) > 600) {
            $detalle = mb_substr($detalle, 0, 600) . '…';
          }
        ?>
        <tr>
          <td><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <?= htmlspecialchars((string)$uid, ENT_QUOTES, 'UTF-8') ?>
            <?php if ($uEmail !== ''): ?>
              <div class="muted"><?= htmlspecialchars($uEmail, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($accionRow, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($entidadRow, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)$entId, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') ?></td>
          <td><pre><?= htmlspecialchars($detalle, ENT_QUOTES, 'UTF-8') ?></pre></td>
        </tr>
      <?php endforeach; ?>

      <?php if (empty($rows)): ?>
        <tr>
          <td colspan="8">Sin resultados.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>