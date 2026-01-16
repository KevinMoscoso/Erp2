<?php
declare(strict_types=1);

// Helpers disponibles por HITO 9A: old(), err(), hasErr()

// CSRF: usar el valor que venga del controller, con fallback seguro
$csrfVal = '';
if (isset($csrf) && is_string($csrf)) {
    $csrfVal = $csrf;
} elseif (class_exists('\Erp2\Core\Csrf')) {
    $csrfVal = (string)\Erp2\Core\Csrf::token();
}

// Datos para selects
$tercerosList = is_array($terceros ?? null) ? $terceros : [];
$productosList = is_array($productos ?? null) ? $productos : [];

// Campos principales
$fechaVal = (string) old('fecha', $today ?? date('Y-m-d'));
$terceroOld = (string) old('tercero_id', '');

// Mensajes globales (si el controller los pasa)
$errorMsg = isset($error) ? (string)$error : '';
$successMsg = isset($success) ? (string)$success : '';

// Partials opcionales si existen en tu repo (no rompe si no existen)
$partialsDir = __DIR__ . '/../partials';
$hasHeader = is_file($partialsDir . '/header.php');
$hasFooter = is_file($partialsDir . '/footer.php');

$title = $title ?? 'Crear factura';

if ($hasHeader) {
    require $partialsDir . '/header.php';
} else {
    ?>
    <!doctype html>
    <html lang="es">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title><?= htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8') ?></title>
      <style>
        .err{color:#b00020;font-size:.95em;margin-top:4px}
        .field{margin:10px 0}
        .invalid{outline:2px solid #b00020}
        table{border-collapse:collapse;width:100%}
        th,td{border:1px solid #ddd;padding:6px}
      </style>
    </head>
    <body>
    <?php
}
?>

<h1><?= htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8') ?></h1>

<p><a href="/facturas">← Volver</a></p>

<?php if ($errorMsg !== ''): ?>
  <p style="color:#b00020;"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if ($successMsg !== ''): ?>
  <p style="color:#0a7a0a;"><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<form method="post" action="/facturas/crear">
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfVal, ENT_QUOTES, 'UTF-8') ?>">

  <div class="field">
    <label for="fecha">Fecha</label><br>
    <input
      id="fecha"
      type="date"
      name="fecha"
      value="<?= htmlspecialchars($fechaVal, ENT_QUOTES, 'UTF-8') ?>"
      class="<?= hasErr('fecha') ? 'invalid' : '' ?>"
      required
    >
    <?php if (err('fecha')): ?>
      <div class="err"><?= htmlspecialchars(err('fecha') ?? '', ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
  </div>

  <div class="field">
    <label for="tercero_id">Cliente</label><br>
    <select
      id="tercero_id"
      name="tercero_id"
      class="<?= hasErr('tercero_id') ? 'invalid' : '' ?>"
      required
    >
      <option value="">-- seleccionar --</option>
      <?php foreach ($tercerosList as $t): ?>
        <?php
          $tid = (string)($t['id'] ?? '');
          $sel = ($terceroOld !== '' && $terceroOld === $tid) ? 'selected' : '';
          $label = (string)($t['nombre'] ?? ($t['razon_social'] ?? ($t['nombre_comercial'] ?? 'Tercero #' . $tid)));
        ?>
        <option value="<?= htmlspecialchars($tid, ENT_QUOTES, 'UTF-8') ?>" <?= $sel ?>>
          <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>

    <?php if (err('tercero_id')): ?>
      <div class="err"><?= htmlspecialchars(err('tercero_id') ?? '', ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
  </div>

  <hr>

  <h2>Líneas</h2>
  <?php if (err('lines')): ?>
    <p class="err"><?= htmlspecialchars(err('lines') ?? '', ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <table>
    <thead>
      <tr>
        <th style="width:20%;">Producto/Servicio</th>
        <th>Descripción</th>
        <th style="width:12%;">Cantidad</th>
        <th style="width:12%;">Precio unit.</th>
      </tr>
    </thead>
    <tbody>
      <?php for ($i = 0; $i < 5; $i++): ?>
        <?php
          $prodOld = (string) old("line_producto_id.$i", '');
          $descOld = (string) old("line_descripcion.$i", '');
          $qtyOld  = (string) old("line_cantidad.$i", '');
          $preOld  = (string) old("line_precio_unitario.$i", '');
        ?>
        <tr>
          <td>
            <select name="line_producto_id[]">
              <option value="">-- (opcional) --</option>
              <?php foreach ($productosList as $p): ?>
                <?php
                  $pid = (string)($p['id'] ?? '');
                  $sel = ($prodOld !== '' && $prodOld === $pid) ? 'selected' : '';
                  $pNombre = (string)($p['nombre'] ?? ($p['descripcion'] ?? ('Item #' . $pid)));
                  $pTipo = (string)($p['tipo'] ?? 'producto'); // 'producto'|'servicio'
                ?>
                <option value="<?= htmlspecialchars($pid, ENT_QUOTES, 'UTF-8') ?>" <?= $sel ?>>
                  <?= htmlspecialchars($pNombre, ENT_QUOTES, 'UTF-8') ?><?= $pTipo ? ' (' . htmlspecialchars($pTipo, ENT_QUOTES, 'UTF-8') . ')' : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>

          <td>
            <input
              type="text"
              name="line_descripcion[]"
              value="<?= htmlspecialchars($descOld, ENT_QUOTES, 'UTF-8') ?>"
              placeholder="Descripción"
              style="width:100%;"
            >
          </td>

          <td>
            <input
              type="number"
              name="line_cantidad[]"
              value="<?= htmlspecialchars($qtyOld, ENT_QUOTES, 'UTF-8') ?>"
              min="0.01"
              step="0.01"
              placeholder="1.00"
            >
          </td>

          <td>
            <input
              type="number"
              name="line_precio_unitario[]"
              value="<?= htmlspecialchars($preOld, ENT_QUOTES, 'UTF-8') ?>"
              min="0"
              step="0.01"
              placeholder="0.00"
            >
          </td>
        </tr>
      <?php endfor; ?>
    </tbody>
  </table>

  <div class="field" style="margin-top:12px;">
    <button type="submit">Guardar (borrador)</button>
  </div>
</form>

<?php
if ($hasFooter) {
    require $partialsDir . '/footer.php';
} elseif (!$hasHeader) {
    echo "</body></html>";
}
?>