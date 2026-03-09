<?php
declare(strict_types=1);

$title = 'Detalle de Auditoría';
require __DIR__ . '/../partials/app_shell_top.php';

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
if ($detallePretty === null) $detallePretty = $detalleRaw;

// Campos (sin inventar)
$id = (string)$get('id', '');
$createdAt = (string)$get('created_at', '');
$usuarioId = (string)$get('usuario_id', '');
$usuarioEmail = (string)$get('usuario_email', '');
$accion = (string)$get('accion', '');
$entidad = (string)$get('entidad', '');
$entidadId = (string)$get('entidad_id', '');
$ip = (string)$get('ip', '');
$userAgent = (string)$get('user_agent', '');
?>

<div class="card" style="padding:16px;">
  <div class="section-header" style="margin-bottom:12px;">
    <h3>Resumen</h3>
    <div class="table-actions">
      <a class="btn btn-secondary" href="/auditoria">Volver</a>
    </div>
  </div>

  <div class="kv-grid">
    <div class="kv">
      <div class="k">ID</div>
      <div class="v"><?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="kv">
      <div class="k">Fecha / hora</div>
      <div class="v"><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="kv">
      <div class="k">Usuario</div>
      <div class="v">
        <?= htmlspecialchars($usuarioId, ENT_QUOTES, 'UTF-8') ?>
        <?php if ($usuarioEmail !== ''): ?>
          <div class="muted"><?= htmlspecialchars($usuarioEmail, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="kv">
      <div class="k">Acción</div>
      <div class="v"><span class="badge"><?= htmlspecialchars($accion, ENT_QUOTES, 'UTF-8') ?></span></div>
    </div>

    <div class="kv">
      <div class="k">Entidad</div>
      <div class="v">
        <span class="badge badge-muted"><?= htmlspecialchars($entidad, ENT_QUOTES, 'UTF-8') ?></span>
        <?php if ($entidadId !== ''): ?>
          <div class="muted">ID: <?= htmlspecialchars($entidadId, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="kv">
      <div class="k">IP</div>
      <div class="v"><?= htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <?php if ($userAgent !== ''): ?>
      <div class="kv" style="grid-column: 1 / -1;">
        <div class="k">User Agent</div>
        <div class="v" style="font-weight:700;"><?= htmlspecialchars($userAgent, ENT_QUOTES, 'UTF-8') ?></div>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="card" style="padding:16px; margin-top:14px;">
  <div class="section-header" style="margin-bottom:10px;">
    <h3>Detalle</h3>
    <span class="muted">JSON (si aplica)</span>
  </div>

  <pre class="json"><?= htmlspecialchars((string)$detallePretty, ENT_QUOTES, 'UTF-8') ?></pre>

  <div style="margin-top:12px;">
    <a class="btn btn-secondary" href="/auditoria">Volver a Auditoría</a>
  </div>
</div>

<?php require __DIR__ . '/../partials/app_shell_bottom.php'; ?>