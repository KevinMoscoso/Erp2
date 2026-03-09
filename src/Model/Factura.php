<?php
declare(strict_types=1);

namespace Erp2\Model;

use Erp2\Core\Database;
use PDO;
use PDOException;

final class Factura
{
    /**
     * Listado + filtro por número (LIKE).
     * @return array<int, array<string, mixed>>
     */
    public static function search(string $q): array
    {
        $q = trim($q);
        $pdo = Database::pdo();

        if ($q === '') {
            $stmt = $pdo->prepare('
                SELECT f.*, t.nombre_comercial AS tercero_nombre
                FROM facturas f
                LEFT JOIN terceros t ON t.id = f.tercero_id
                ORDER BY f.id DESC
                LIMIT 200
            ');
            $stmt->execute();
            return $stmt->fetchAll();
        }

        $like = '%' . $q . '%';
        $stmt = $pdo->prepare('
            SELECT f.*, t.nombre_comercial AS tercero_nombre
            FROM facturas f
            LEFT JOIN terceros t ON t.id = f.tercero_id
            WHERE f.numero LIKE :q
            ORDER BY f.id DESC
            LIMIT 200
        ');
        $stmt->execute([':q' => $like]);

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
        $stmt = $pdo->prepare('
            SELECT f.*, t.nombre_comercial AS tercero_nombre, t.identificacion AS tercero_identificacion, t.email AS tercero_email
            FROM facturas f
            LEFT JOIN terceros t ON t.id = f.tercero_id
            WHERE f.id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /**
     * Crea factura (cabecera + detalle) en transacción.
     * Regla número: F-000001 basado en MAX(id)+1.
     *
     * @param array{fecha:string,tercero_id:int,estado:string,subtotal:string,total:string} $header
     * @param array<int, array{producto_id:?int,descripcion:string,cantidad:string,precio_unitario:string,subtotal_linea:string}> $lines
     */
    public static function createWithDetails(array $header, array $lines): int
    {
        $pdo = Database::pdo();

        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $pdo->beginTransaction();

                $numero = self::generateNumeroFromMaxId($pdo);

                $stmt = $pdo->prepare('
                    INSERT INTO facturas (numero, fecha, tercero_id, estado, subtotal, total, created_at, updated_at)
                    VALUES (:numero, :fecha, :tercero_id, :estado, :subtotal, :total, NOW(), NOW())
                ');
                $stmt->execute([
                    ':numero' => $numero,
                    ':fecha' => $header['fecha'],
                    ':tercero_id' => $header['tercero_id'],
                    ':estado' => $header['estado'],
                    ':subtotal' => $header['subtotal'],
                    ':total' => $header['total'],
                ]);

                $facturaId = (int)$pdo->lastInsertId();

                FacturaDetalle::insertMany($pdo, $facturaId, $lines);

                $pdo->commit();

                return $facturaId;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                // Retry solo si es duplicate key (numero UNIQUE) en primer intento
                if ($attempt === 0 && self::isDuplicateKey($e)) {
                    continue;
                }

                throw $e;
            }
        }

        // No debería llegar aquí
        throw new PDOException('No se pudo generar número de factura único.');
    }

    public static function anular(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            UPDATE facturas
            SET estado = 'anulada', updated_at = NOW()
            WHERE id = :id AND estado <> 'anulada'
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    private static function generateNumeroFromMaxId(PDO $pdo): string
    {
        // Basado en MAX(id)+1 (prototipo)
        $stmt = $pdo->prepare('SELECT IFNULL(MAX(id), 0) + 1 AS next_id FROM facturas');
        $stmt->execute();
        $row = $stmt->fetch();

        $next = 1;
        if (is_array($row) && isset($row['next_id']) && is_numeric($row['next_id'])) {
            $next = (int)$row['next_id'];
            if ($next <= 0) {
                $next = 1;
            }
        }

        return 'F-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
    }

    private static function isDuplicateKey(PDOException $e): bool
    {
        // MySQL: SQLSTATE 23000, driver error 1062
        if ($e->getCode() !== '23000') {
            return false;
        }

        $info = $e->errorInfo ?? null;
        return is_array($info) && isset($info[1]) && (int)$info[1] === 1062;
    }
}