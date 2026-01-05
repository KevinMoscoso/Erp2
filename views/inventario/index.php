<?php
declare(strict_types=1);

use Erp2\Core\Auth;

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Inventario</title>
</head>
<body>
    <h1>Inventario</h1>

    <p>
        <a href="/">← Home</a>
    </p>

    <?php if (!empty($flash_error)): ?>
        <p style="color:#b00020;"><?= htmlspecialchars((string)$flash_error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if (!empty($flash_success)): ?>
        <p style="color:#0a7a0a;"><?= htmlspecialchars((string)$flash_success, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="get" action="/inventario">
        <label>
            Buscar (referencia o nombre):
            <input type="text" name="q" value="<?= htmlspecialchars((string)($q ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <button type="submit">Buscar</button>
    </form>

    <hr>

    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tipo</th>
                <th>Referencia</th>
                <th>Nombre</th>
                <th>Stock actual</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach (($productos ?? []) as $p): ?>
            <tr>
                <td><?= htmlspecialchars((string)$p['id'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)$p['tipo'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)$p['referencia'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)$p['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)$p['stock_actual'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= ((int)($p['estado'] ?? 1) === 1) ? 'Activo' : 'Inactivo' ?></td>
                <td>
                    <?php if (Auth::has('inventario.ver')): ?>
                        <a href="/inventario/<?= (int)$p['id'] ?>">Ver Kardex</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php if (empty($productos)): ?>
            <tr><td colspan="7">Sin resultados.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>