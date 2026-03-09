<?php
declare(strict_types=1);

namespace Erp2\Model;

use PDO;

final class FacturaDetalle
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listByFactura(int $facturaId): array
    {
        if ($facturaId <= 0) {
            return [];
        }

        $pdo = \Erp2\Core\Database::pdo();
        $stmt = $pdo->prepare('
            SELECT fd.*,
                   p.referencia AS producto_referencia,
                   p.nombre AS producto_nombre
            FROM factura_detalles fd
            LEFT JOIN productos p ON p.id = fd.producto_id
            WHERE fd.factura_id = :fid
            ORDER BY fd.id ASC
        ');
        $stmt->execute([':fid' => $facturaId]);

        return $stmt->fetchAll();
    }

    /**
     * @param array<int, array{producto_id:?int,descripcion:string,cantidad:string,precio_unitario:string,subtotal_linea:string}> $lines
     */
    public static function insertMany(PDO $pdo, int $facturaId, array $lines): void
    {
        $stmt = $pdo->prepare('
            INSERT INTO factura_detalles
              (factura_id, producto_id, descripcion, cantidad, precio_unitario, subtotal_linea)
            VALUES
              (:factura_id, :producto_id, :descripcion, :cantidad, :precio_unitario, :subtotal_linea)
        ');

        foreach ($lines as $ln) {
            $stmt->execute([
                ':factura_id' => $facturaId,
                ':producto_id' => $ln['producto_id'],
                ':descripcion' => $ln['descripcion'],
                ':cantidad' => $ln['cantidad'],
                ':precio_unitario' => $ln['precio_unitario'],
                ':subtotal_linea' => $ln['subtotal_linea'],
            ]);
        }
    }
}