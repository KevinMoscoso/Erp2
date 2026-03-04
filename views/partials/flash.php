<?php
declare(strict_types=1);

use Erp2\Core\Flash;

/**
 * Flash renderer robusto:
 * - Si el controlador pasó $error/$success/$info (ya consumió Flash::get), se muestran aquí.
 * - Si NO existen esas variables, entonces sí usamos Flash::get().
 * - Evita duplicación: nunca muestra ambas fuentes a la vez.
 */

$hasViewVars =
    (isset($success) && (string)$success !== '') ||
    (isset($error) && (string)$error !== '') ||
    (isset($info) && (string)$info !== '');

if ($hasViewVars) {
    $s = isset($success) ? (string)$success : '';
    $e = isset($error) ? (string)$error : '';
    $i = isset($info) ? (string)$info : '';

    if ($s !== ''): ?>
      <div class="alert alert-success" role="alert"><?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($e !== ''): ?>
      <div class="alert alert-error" role="alert"><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($i !== ''): ?>
      <div class="alert alert-info" role="alert"><?= htmlspecialchars($i, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif;

    return;
}

// Fallback: usar Flash directo (si el controlador NO lo consumió)
if (!class_exists(Flash::class) || !method_exists(Flash::class, 'get')) {
    return;
}

$success = (string)(Flash::get('success') ?? '');
$error   = (string)(Flash::get('error') ?? '');
$info    = (string)(Flash::get('info') ?? '');

if ($success !== ''): ?>
  <div class="alert alert-success" role="alert"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($error !== ''): ?>
  <div class="alert alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($info !== ''): ?>
  <div class="alert alert-info" role="alert"><?= htmlspecialchars($info, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>