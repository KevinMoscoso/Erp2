<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'ERP2', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <?php
    $user = \Erp2\Core\Auth::user();
    $userLabel = '';
    if (is_array($user)) {
      $userLabel = (string)($user['email'] ?? ($user['nombre'] ?? 'Usuario'));
    }
  ?>

  <h1><?= htmlspecialchars($title ?? 'ERP2', ENT_QUOTES, 'UTF-8') ?></h1>

  <div style="margin-bottom: 12px;">
    <div>Sesión: <strong><?= htmlspecialchars($userLabel !== '' ? $userLabel : '—', ENT_QUOTES, 'UTF-8') ?></strong></div>
    <div style="margin-top:6px;">
      <a href="/logout">Cerrar sesión</a>
    </div>
  </div>

  <h2>Navegación</h2>
  <ul>
    <li><a href="/health">/health</a> (público)</li>

    <?php if (\Erp2\Core\Auth::has('terceros.ver')): ?>
      <li><a href="/terceros">Terceros</a></li>
    <?php endif; ?>

    <?php if (\Erp2\Core\Auth::has('productos.ver')): ?>
      <li><a href="/productos">Productos/Servicios</a></li>
    <?php endif; ?>

    <?php if (\Erp2\Core\Auth::has('facturas.ver')): ?>
      <li><a href="/facturas">Facturas</a></li>
    <?php endif; ?>

    <?php if (\Erp2\Core\Auth::has('inventario.ver')): ?>
      <li><a href="/inventario">Inventario</a></li>
    <?php endif; ?>

    <?php if (\Erp2\Core\Auth::has('compras.ver')): ?>
      <li><a href="/compras">Compras</a></li>
    <?php endif; ?>

    <?php if (\Erp2\Core\Auth::has('pagos.ver')): ?>
      <li><a href="/pagos">Pagos</a></li>
    <?php endif; ?>

    <?php if (\Erp2\Core\Auth::has('cartera.ver')): ?>
      <li><a href="/cartera">Cartera (CXC/CXP)</a></li>
    <?php endif; ?>
  </ul>

  <p>Prototipo ERP2: módulos incrementales con RBAC, auditoría y validaciones mínimas.</p>
</body>
</html>