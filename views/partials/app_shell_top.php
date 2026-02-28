<?php
declare(strict_types=1);

use Erp2\Core\Auth;

// Title (puede venir definido en la vista)
$titleSafe = htmlspecialchars((string)($title ?? 'ERP2'), ENT_QUOTES, 'UTF-8');

// Usuario actual
$user = null;
if (class_exists(Auth::class) && method_exists(Auth::class, 'user')) {
    $user = Auth::user();
}
$userId = (is_array($user) && isset($user['id']) && is_numeric($user['id'])) ? (int)$user['id'] : 0;
$userLabel = is_array($user) ? (string)($user['email'] ?? ($user['nombre'] ?? 'Usuario')) : 'Usuario';

// Ruta actual para marcar nav activo
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$active = static fn(string $href): string => ($path === $href ? 'active' : '');

// RBAC seguro: si no puedo comprobar, NO muestro.
$can = static function (string $perm): bool {
    return (class_exists(Auth::class) && method_exists(Auth::class, 'has')) ? Auth::has($perm) : false;
};

?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= $titleSafe ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/erp.css">
</head>
<body>
  <div class="app-container">
    <aside class="sidebar">
      <div class="sidebar-brand">
        <div class="sidebar-logo">E</div>
        <div>
          <div class="sidebar-title">ERP2</div>
          <div class="sidebar-subtitle">Sistema Empresarial</div>
        </div>
      </div>

      <nav class="nav">
        <a class="<?= $active('/') ?>" href="/"><span class="icon">🏠</span> Dashboard</a>

        <?php if ($can('terceros.ver')): ?>
          <a class="<?= $active('/terceros') ?>" href="/terceros"><span class="icon">👤</span> Terceros</a>
        <?php endif; ?>

        <?php if ($can('productos.ver')): ?>
          <a class="<?= $active('/productos') ?>" href="/productos"><span class="icon">📦</span> Productos</a>
        <?php endif; ?>

        <?php if ($can('inventario.ver')): ?>
          <a class="<?= $active('/inventario') ?>" href="/inventario"><span class="icon">📊</span> Inventario</a>
        <?php endif; ?>

        <?php if ($can('facturas.ver')): ?>
          <a class="<?= $active('/facturas') ?>" href="/facturas"><span class="icon">🧾</span> Facturas</a>
        <?php endif; ?>

        <?php if ($can('compras.ver')): ?>
          <a class="<?= $active('/compras') ?>" href="/compras"><span class="icon">🛒</span> Compras</a>
        <?php endif; ?>

        <?php if ($can('pagos.ver')): ?>
          <a class="<?= $active('/pagos') ?>" href="/pagos"><span class="icon">💳</span> Pagos</a>
        <?php endif; ?>

        <?php if ($can('cartera.ver')): ?>
          <a class="<?= $active('/cartera') ?>" href="/cartera"><span class="icon">🧮</span> Cartera</a>
        <?php endif; ?>

        <?php if ($can('auditoria.ver')): ?>
          <a class="<?= $active('/auditoria') ?>" href="/auditoria"><span class="icon">🕵️</span> Auditoría</a>
        <?php endif; ?>

        <?php if ($userId === 1): ?>
          <div class="nav-section">
            <div class="nav-section-title">Seguridad</div>
            <a class="<?= $active('/usuarios') ?>" href="/usuarios"><span class="icon">👥</span> Usuarios</a>
            <a class="<?= $active('/roles') ?>" href="/roles"><span class="icon">🔑</span> Roles</a>
            <a class="<?= $active('/permisos') ?>" href="/permisos"><span class="icon">🧩</span> Permisos</a>
          </div>
        <?php endif; ?>
      </nav>
    </aside>

    <main class="main-content">
      <div class="header">
        <h2><?= $titleSafe ?></h2>
        <div class="header-actions">
          <span class="muted">Sesión: <strong><?= htmlspecialchars($userLabel, ENT_QUOTES, 'UTF-8') ?></strong></span>
          <a class="btn btn-secondary" href="/logout">Cerrar sesión</a>
        </div>
      </div>

      <div class="content-wrap">
        <?php require __DIR__ . '/flash.php'; ?>