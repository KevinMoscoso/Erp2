<?php
declare(strict_types=1);

use Erp2\Core\Flash;

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