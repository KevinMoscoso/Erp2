<?php
declare(strict_types=1);

use Erp2\Core\Auth;

$pid = (int)($producto['id'] ?? 0);
$tipo = (string)($producto['tipo'] ?? '');
$accionVal = (string)old('accion', 'sumar');
$cantidadVal = (string)old('cantidad', '');
$notaVal = (string)old('nota', '');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Kardex</title>
    <style>
        .err{color:#b00020;font-size:.92em;margin-top:4px}
        .invalid{outline:2px solid #b00020}
    </style>
</head>
<body>
    <p><a href="/inventario">← Volver</a></p>

    <h1>Kardex - Producto #<?= htmlspecialchars((string)$pid, ENT_QUOTES, 'UTF-8') ?></h1>

    <?php if (!empty($flash_error)): ?>
        <p style="color:#b00020;"><?= htmlspecialchars((string)$flash_error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if (!empty($flash_success)): ?>
        <p style="color:#0a7a0a;"><?= htmlspecialchars((string)$flash_success, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <h2>Datos del producto</h2>
    <ul>
        <li>Referencia: <?= htmlspecialchars((string)($producto['referencia'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
        <li>Nombre: <?= htmlspecialchars((string)($producto['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
        <li>Tipo: <?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?></li>
        <li>Stock actual: <?= htmlspecialchars((string)($producto['stock_actual'] ?? 0), ENT_QUOTES, 'UTF-8') ?></li>
        <li>Estado: <?= ((int)($producto['estado'] ?? 1) === 1) ? 'Activo' : 'Inactivo' ?></li>
    </ul>

    <?php if (Auth::has('inventario.ajustar') && $tipo === 'producto'): ?>
        <h2>Ajuste manual</h2>

        <?php if (err('form')): ?>
            <p class="err"><?= htmlspecialchars((string)err('form'), ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="post" action="/inventario/<?= $pid ?>/ajustar">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>">

            <label>
                Acción:
                <select name="accion" class="<?= hasErr('accion') ? 'invalid' : '' ?>">
                    <option value="sumar" <?= $accionVal === 'sumar' ? 'selected' : '' ?>>Sumar</option>
                    <option value="restar" <?= $accionVal === 'restar' ? 'selected' : '' ?>>Restar</option>
                </select>
            </label>
            <?php if (err('accion')): ?>
                <div class="err"><?= htmlspecialchars((string)err('accion'), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <br><br>

            <label>
                Cantidad:
                <input
                    type="number"
                    step="0.01"
                    min="0.01"
                    name="cantidad"
                    required
                    value="<?= htmlspecialchars($cantidadVal, ENT_QUOTES, 'UTF-8') ?>"
                    class="<?= hasErr('cantidad') ? 'invalid' : '' ?>"
                >
            </label>
            <?php if (err('cantidad')): ?>
                <div class="err"><?= htmlspecialchars((string)err('cantidad'), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <br><br>

            <label>
                Nota (opcional):
                <input
                    type="text"
                    name="nota"
                    maxlength="255"
                    value="<?= htmlspecialchars($notaVal, ENT_QUOTES, 'UTF-8') ?>"
                    class="<?= hasErr('nota') ? 'invalid' : '' ?>"
                >
            </label>
            <?php if (err('nota')): ?>
                <div class="err"><?= htmlspecialchars((string)err('nota'), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <br><br>

            <button type="submit">Aplicar ajuste</button>
        </form>
    <?php endif; ?>

    <hr>

    <h2>Movimientos</h2>
    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Cantidad</th>
                <th>Saldo anterior</th>
                <th>Saldo nuevo</th>
                <th>Ref</th>
                <th>Nota</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach (($movimientos ?? []) as $m): ?>
            <tr>
                <td><?= htmlspecialchars((string)($m['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($m['tipo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($m['cantidad'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($m['saldo_anterior'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($m['saldo_nuevo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <?= htmlspecialchars((string)($m['referencia_tipo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    <?php if (!empty($m['referencia_id'])): ?>
                        #<?= (int)$m['referencia_id'] ?>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars((string)($m['nota'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>

        <?php if (empty($movimientos)): ?>
            <tr><td colspan="7">Sin movimientos.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>