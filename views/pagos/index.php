<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Pagos', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Pagos', ENT_QUOTES, 'UTF-8') ?></h1>

  <p><a href="/">Inicio</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <p style="color:#0a7a0a;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php
    // UX: si ref_id viene vacío, no mostrar "0"
    $refIdVal = '';
    if (isset($ref_id)) {
        $tmp = trim((string)$ref_id);
        if ($tmp !== '' && (int)$tmp > 0) {
            $refIdVal = (string)((int)$tmp);
        }
    }
  ?>

  <form method="get" action="/pagos">
    <label>Buscar</label>
    <input name="q" value="<?= htmlspecialchars((string)($q ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="120">

    <label>Tipo</label>
    <select name="tipo_ref">
      <option value="" <?= (($tipo_ref ?? '') === '') ? 'selected' : '' ?>>Todos</option>
      <option value="factura" <?= (($tipo_ref ?? '') === 'factura') ? 'selected' : '' ?>>Factura</option>
      <option value="compra" <?= (($tipo_ref ?? '') === 'compra') ? 'selected' : '' ?>>Compra</option>
    </select>

    <label>Ref ID</label>
    <input name="ref_id" value="<?= htmlspecialchars($refIdVal, ENT_QUOTES, 'UTF-8') ?>" style="width:90px;">

    <button type="submit">Filtrar</button>
    <a href="/pagos">Limpiar</a>
  </form>

  <p style="margin-top:12px;">
    <?php if (\Erp2\Core\Auth::has('pagos.crear')): ?>
      <a href="/pagos/crear">Registrar pago</a>
    <?php endif; ?>
  </p>

  <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%;">
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
        ?>
        <tr>
          <td><?= htmlspecialchars((string)$pid, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($tref, ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <a href="<?= htmlspecialchars($link, ENT_QUOTES, 'UTF-8') ?>">
              <?= htmlspecialchars($refNum !== '' ? $refNum : ('#' . $rid), ENT_QUOTES, 'UTF-8') ?>
            </a>
          </td>
          <td><?= htmlspecialchars((string)($p['tercero_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($p['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($p['monto'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($p['metodo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($p['referencia'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($p['nota'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <?php if (\Erp2\Core\Auth::has('pagos.eliminar')): ?>
              <form method="post" action="/pagos/<?= $pid ?>/eliminar" style="display:inline;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" onclick="return confirm('¿Eliminar pago?');">Eliminar</button>
              </form>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php if (empty($items)): ?>
        <tr><td colspan="10">Sin resultados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>