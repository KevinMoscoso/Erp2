<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Auditoría', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Auditoría', ENT_QUOTES, 'UTF-8') ?></h1>

  <p>
    <a href="/">Inicio</a> |
    <a href="/auditoria">Volver a Auditoría</a>
  </p>

  <?php if (!empty($flash_error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$flash_error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if (!empty($flash_success)): ?>
    <p style="color:#0a7a0a;"><?= htmlspecialchars((string)$flash_success, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php
    $r = $row ?? [];
    $get = static function(string $k, $default = '') use ($r) {
      return array_key_exists($k, $r) ? $r[$k] : $default;
    };

    $detalleRaw = (string)$get('detalle_json', '');
    $detallePretty = null;

    if ($detalleRaw !== '') {
      $decoded = json_decode($detalleRaw, true);
      if (json_last_error() === JSON_ERROR_NONE) {
        $detallePretty = json_encode(
          $decoded,
          JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
      }
    }
    if ($detallePretty === null) {
      $detallePretty = $detalleRaw;
    }
  ?>

  <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width:100%;">
    <tbody>
      <tr><th style="text-align:left;">ID</th><td><?= htmlspecialchars((string)$get('id',''), ENT_QUOTES, 'UTF-8') ?></td></tr>
      <tr><th style="text-align:left;">Fecha</th><td><?= htmlspecialchars((string)$get('created_at',''), ENT_QUOTES, 'UTF-8') ?></td></tr>
      <tr><th style="text-align:left;">Usuario ID</th><td><?= htmlspecialchars((string)$get('usuario_id',''), ENT_QUOTES, 'UTF-8') ?></td></tr>
      <tr><th style="text-align:left;">Usuario Email</th><td><?= htmlspecialchars((string)$get('usuario_email',''), ENT_QUOTES, 'UTF-8') ?></td></tr>
      <tr><th style="text-align:left;">Acción</th><td><?= htmlspecialchars((string)$get('accion',''), ENT_QUOTES, 'UTF-8') ?></td></tr>
      <tr><th style="text-align:left;">Entidad</th><td><?= htmlspecialchars((string)$get('entidad',''), ENT_QUOTES, 'UTF-8') ?></td></tr>
      <tr><th style="text-align:left;">Entidad ID</th><td><?= htmlspecialchars((string)$get('entidad_id',''), ENT_QUOTES, 'UTF-8') ?></td></tr>
      <tr><th style="text-align:left;">IP</th><td><?= htmlspecialchars((string)$get('ip',''), ENT_QUOTES, 'UTF-8') ?></td></tr>
      <tr><th style="text-align:left;">User Agent</th><td><?= htmlspecialchars((string)$get('user_agent',''), ENT_QUOTES, 'UTF-8') ?></td></tr>
    </tbody>
  </table>

  <h2>Detalle</h2>
  <pre style="white-space: pre-wrap; word-wrap: break-word; border:1px solid #ddd; padding:10px;"><?= htmlspecialchars((string)$detallePretty, ENT_QUOTES, 'UTF-8') ?></pre>
</body>
</html>