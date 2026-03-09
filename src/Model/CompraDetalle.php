<?php
declare(strict_types=1);

namespace Erp2\Model;

use PDO;

final class CompraDetalle
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listByCompra(int $compraId): array
    {
        if ($compraId <= 0) {
            return [];
        }

        $pdo = \Erp2\Core\Database::pdo();
        $st = $pdo->prepare('
            SELECT cd.*,
                   p.referencia AS producto_referencia,
                   p.nombre AS producto_nombre,
                   p.tipo AS producto_tipo
            FROM compra_detalles cd
            LEFT JOIN productos p ON p.id = cd.producto_id
            WHERE cd.compra_id = :cid
            ORDER BY cd.id ASC
        ');
        $st->execute([':cid' => $compraId]);

        return $st->fetchAll();
    }

    /**
     * @param array<int, array{producto_id:?int,descripcion:string,cantidad:string,costo_unitario:string,subtotal_linea:string}> $lines
     */
    public static function insertMany(PDO $pdo, int $compraId, array $lines): void
    {
        $st = $pdo->prepare('
            INSERT INTO compra_detalles
              (compra_id, producto_id, descripcion, cantidad, costo_unitario, subtotal_linea)
            VALUES
              (:compra_id, :producto_id, :descripcion, :cantidad, :costo_unitario, :subtotal_linea)
        ');

        foreach ($lines as $ln) {
            $st->execute([
                ':compra_id' => $compraId,
                ':producto_id' => $ln['producto_id'],
                ':descripcion' => $ln['descripcion'],
                ':cantidad' => $ln['cantidad'],
                ':costo_unitario' => $ln['costo_unitario'],
                ':subtotal_linea' => $ln['subtotal_linea'],
            ]);
        }
    }
}