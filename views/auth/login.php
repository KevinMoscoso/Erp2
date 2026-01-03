<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Login', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Iniciar sesión', ENT_QUOTES, 'UTF-8') ?></h1>

  <?php if (!empty($error)): ?>
    <p style="color: #b00020;">
      <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </p>
  <?php endif; ?>

  <form method="post" action="/login" autocomplete="off">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <div>
      <label for="email">Email</label><br>
      <input id="email" name="email" type="email" required maxlength="190">
    </div>

    <div style="margin-top: 8px;">
      <label for="password">Contraseña</label><br>
      <input id="password" name="password" type="password" required maxlength="255">
    </div>

    <div style="margin-top: 12px;">
      <button type="submit">Entrar</button>
    </div>
  </form>

  <hr>
  <ul>
    <li><a href="/health">/health</a> (público)</li>
  </ul>
</body>
</html>