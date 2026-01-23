<?php
declare(strict_types=1);

namespace Erp2\Model;

use Erp2\Core\Database;

final class Producto
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function search(string $q): array
    {
        $q = trim($q);
        $pdo = Database::pdo();

        if ($q === '') {
            $stmt = $pdo->prepare('SELECT * FROM productos WHERE estado = 1 ORDER BY id DESC LIMIT 200');
            $stmt->execute();
            return $stmt->fetchAll();
        }

        // Evitar HY093: PDO (MySQL) no tolera placeholders nombrados repetidos en un mismo statement.
        $like = '%' . $q . '%';
        $stmt = $pdo->prepare('
            SELECT *
            FROM productos
            WHERE estado = 1
              AND (
                referencia LIKE :q1
                OR nombre LIKE :q2
                OR descripcion LIKE :q3
              )
            ORDER BY id DESC
            LIMIT 200
        ');
        $stmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like]);

        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM productos WHERE id = :id AND estado = 1 LIMIT 1');
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare('
            INSERT INTO productos
              (tipo, referencia, nombre, descripcion, precio_venta, costo, estado, created_at, updated_at)
            VALUES
              (:tipo, :referencia, :nombre, :descripcion, :precio_venta, :costo, 1, NOW(), NOW())
        ');

        $stmt->execute([
            ':tipo' => (string)($data['tipo'] ?? ''),
            ':referencia' => (string)($data['referencia'] ?? ''),
            ':nombre' => (string)($data['nombre'] ?? ''),
            ':descripcion' => (string)($data['descripcion'] ?? ''),
            ':precio_venta' => (string)($data['precio_venta'] ?? '0'),
            ':costo' => $data['costo'] ?? null,
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            UPDATE productos
            SET tipo = :tipo,
                referencia = :referencia,
                nombre = :nombre,
                descripcion = :descripcion,
                precio_venta = :precio_venta,
                costo = :costo,
                updated_at = NOW()
            WHERE id = :id AND estado = 1
            LIMIT 1
        ');

        $stmt->execute([
            ':tipo' => (string)($data['tipo'] ?? ''),
            ':referencia' => (string)($data['referencia'] ?? ''),
            ':nombre' => (string)($data['nombre'] ?? ''),
            ':descripcion' => (string)($data['descripcion'] ?? ''),
            ':precio_venta' => (string)($data['precio_venta'] ?? '0'),
            ':costo' => $data['costo'] ?? null,
            ':id' => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function softDelete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('UPDATE productos SET estado = 0, updated_at = NOW() WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }
}