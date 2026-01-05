<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Crear factura', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Crear factura', ENT_QUOTES, 'UTF-8') ?></h1>

  <p><a href="/facturas">Volver al listado</a></p>

  <?php if (!empty($error)): ?>
    <p style="color:#b00020;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="post" action="<?= htmlspecialchars((string)($action ?? '/facturas/crear'), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <div>
      <label for="fecha">Fecha</label><br>
      <input id="fecha" name="fecha" type="date" value="<?= htmlspecialchars((string)($today ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8') ?>" required>
    </div>

    <div style="margin-top:8px;">
      <label for="tercero_id">Tercero</label><br>
      <select id="tercero_id" name="tercero_id" required>
        <option value="">-- Seleccionar --</option>
        <?php foreach (($terceros ?? []) as $t): ?>
          <option value="<?= (int)($t['id'] ?? 0) ?>">
            <?= htmlspecialchars((string)($t['nombre_comercial'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <hr>

    <h2>Líneas</h2>
    <p>Ingresa al menos 1 línea válida. Si eliges un producto, puedes dejar la descripción vacía.</p>

    <?php for ($i = 0; $i < 5; $i++): ?>
      <fieldset style="margin-bottom: 10px;">
        <legend>Línea <?= $i + 1 ?></legend>

        <div>
          <label>Producto (opcional)</label><br>
          <select name="line_producto_id[]">
            <option value="">-- ninguno --</option>
            <?php foreach (($productos ?? []) as $p): ?>
              <option value="<?= (int)($p['id'] ?? 0) ?>">
                <?= htmlspecialchars((string)($p['referencia'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                — <?= htmlspecialchars((string)($p['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="margin-top:6px;">
          <label>Descripción</label><br>
          <input name="line_descripcion[]" maxlength="255" style="width: 100%;">
        </div>

        <div style="margin-top:6px;">
          <label>Cantidad</label><br>
          <input name="line_cantidad[]" inputmode="decimal" placeholder="1" style="width: 140px;">
        </div>

        <div style="margin-top:6px;">
          <label>Precio unitario</label><br>
          <input name="line_precio_unitario[]" inputmode="decimal" placeholder="0.00" style="width: 140px;">
        </div>
      </fieldset>
    <?php endfor; ?>

    <div style="margin-top:12px;">
      <button type="submit">Crear factura</button>
    </div>
  </form>
</body>
</html>