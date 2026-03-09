<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Iniciar sesión', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/erp.css">
</head>
<body class="auth-page">
  <div class="auth-card">
    <div class="auth-header">
      <h1 class="auth-title">Iniciar sesión</h1>
      <p class="auth-subtitle">Accede con tus credenciales para continuar.</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-error" role="alert">
        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <form method="post" action="/login" autocomplete="off" novalidate>
      <?php
        // CSRF: compatibilidad máxima SIN tocar controladores.
        // 1) Preferir variable ya entregada por el controlador ($csrf o $csrfToken).
        // 2) Fallback: generar token si existe la clase Csrf.
        $token = '';

        if (isset($csrf) && is_string($csrf) && $csrf !== '') {
          $token = $csrf;
        } elseif (isset($csrfToken) && is_string($csrfToken) && $csrfToken !== '') {
          $token = $csrfToken;
        } elseif (class_exists('\\Erp2\\Core\\Csrf') && method_exists('\\Erp2\\Core\\Csrf', 'token')) {
          /** @noinspection PhpFullyQualifiedNameUsageInspection */
          $token = (string)\Erp2\Core\Csrf::token();
        }

        if ($token !== ''):
          $safeToken = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
      ?>
        <!-- Enviamos el MISMO token con varios name posibles -->
        <input type="hidden" name="csrf" value="<?= $safeToken ?>">
        <input type="hidden" name="_csrf" value="<?= $safeToken ?>">
        <input type="hidden" name="csrf_token" value="<?= $safeToken ?>">
      <?php endif; ?>

      <div class="form-group">
        <label for="email">Email</label>
        <input
          class="input"
          id="email"
          name="email"
          type="email"
          required
          maxlength="190"
          value="<?= htmlspecialchars(function_exists('old') ? (string)old('email', '') : '', ENT_QUOTES, 'UTF-8') ?>"
        >
      </div>

      <div class="form-group">
        <label for="password">Contraseña</label>
        <input
          class="input"
          id="password"
          name="password"
          type="password"
          required
          maxlength="255"
        >
      </div>

      <div class="actions">
        <button class="btn btn-primary" type="submit">Entrar</button>
      </div>
    </form>
  </div>
</body>
</html>