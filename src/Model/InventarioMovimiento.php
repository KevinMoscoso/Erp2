<?php
declare(strict_types=1);

namespace Erp2\Model;

use Erp2\Core\Database;
use PDO;

final class InventarioMovimiento
{
    /**
     * Devuelve movimientos del producto (Kardex), ordenados por created_at desc (y id desc como desempate).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function listByProducto(int $productoId): array
    {
        $pdo = Database::pdo();

        $sql = "
            SELECT
                id,
                producto_id,
                tipo,
                cantidad,
                saldo_anterior,
                saldo_nuevo,
                referencia_tipo,
                referencia_id,
                usuario_id,
                nota,
                created_at
            FROM inventario_movimientos
            WHERE producto_id = :pid
            ORDER BY created_at DESC, id DESC
        ";

        $st = $pdo->prepare($sql);
        $st->execute([':pid' => $productoId]);

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $st->fetchAll();
        return $rows;
    }

    /**
     * Inserta un movimiento de inventario dentro de una transacción existente.
     * Requiere que el caller calcule saldo_anterior/saldo_nuevo de forma consistente.
     */
    public static function insert(
        PDO $pdo,
        int $productoId,
        string $tipo,
        float $cantidad,
        float $saldoAnterior,
        float $saldoNuevo,
        ?int $usuarioId,
        ?string $nota = null,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null
    ): void {
        $sql = "
            INSERT INTO inventario_movimientos
                (producto_id, tipo, cantidad, saldo_anterior, saldo_nuevo, referencia_tipo, referencia_id, usuario_id, nota, created_at)
            VALUES
                (:producto_id, :tipo, :cantidad, :saldo_anterior, :saldo_nuevo, :referencia_tipo, :referencia_id, :usuario_id, :nota, NOW())
        ";

        $st = $pdo->prepare($sql);
        $st->execute([
            ':producto_id' => $productoId,
            ':tipo' => $tipo,
            ':cantidad' => $cantidad,
            ':saldo_anterior' => $saldoAnterior,
            ':saldo_nuevo' => $saldoNuevo,
            ':referencia_tipo' => $referenciaTipo,
            ':referencia_id' => $referenciaId,
            ':usuario_id' => $usuarioId,
            ':nota' => $nota,
        ]);
    }
}