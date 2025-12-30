<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'ERPia2', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'ERPia2', ENT_QUOTES, 'UTF-8') ?></h1>
  <p>Proyecto base listo. Siguiente: autenticación, permisos, y primer módulo (Clientes).</p>
  <ul>
    <li><a href="/health">/health</a> (dev)</li>
  </ul>
</body>
</html>