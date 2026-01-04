<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Producto/Servicio', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Producto/Servicio', ENT_QUOTES, 'UTF-8') ?></h1>

  <p><a href="/productos">Volver al listado</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php
    $p = $producto ?? [];
    $tipo = (string)($p['tipo'] ?? 'producto');
    $referencia = (string)($p['referencia'] ?? '');
    $nombre = (string)($p['nombre'] ?? '');
    $descripcion = (string)($p['descripcion'] ?? '');
    $precio_venta = (string)($p['precio_venta'] ?? '0');
    $costo = (string)($p['costo'] ?? '');
  ?>

  <form method="post" action="<?= htmlspecialchars((string)($action ?? '/productos'), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <div>
      <label for="tipo">Tipo</label><br>
      <select id="tipo" name="tipo" required>
        <option value="producto" <?= $tipo === 'producto' ? 'selected' : '' ?>>producto</option>
        <option value="servicio" <?= $tipo === 'servicio' ? 'selected' : '' ?>>servicio</option>
      </select>
    </div>

    <div style="margin-top:8px;">
      <label for="referencia">Referencia</label><br>
      <input id="referencia" name="referencia" required maxlength="64"
             value="<?= htmlspecialchars($referencia, ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div style="margin-top:8px;">
      <label for="nombre">Nombre</label><br>
      <input id="nombre" name="nombre" required maxlength="160"
             value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div style="margin-top:8px;">
      <label for="descripcion">Descripción</label><br>
      <textarea id="descripcion" name="descripcion" rows="4" cols="60"><?= htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>

    <div style="margin-top:8px;">
      <label for="precio_venta">Precio de venta</label><br>
      <input id="precio_venta" name="precio_venta" required inputmode="decimal"
             value="<?= htmlspecialchars($precio_venta, ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div style="margin-top:8px;">
      <label for="costo">Costo (opcional)</label><br>
      <input id="costo" name="costo" inputmode="decimal"
             value="<?= htmlspecialchars($costo, ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div style="margin-top:12px;">
      <button type="submit">Guardar</button>
      <?php if (($mode ?? '') === 'edit' && !empty($p['id'])): ?>
        <a href="/productos/<?= (int)$p['id'] ?>">Cancelar</a>
      <?php endif; ?>
    </div>
  </form>
</body>
</html>