<?php
declare(strict_types=1);

namespace Erp2\Model;

use Erp2\Core\Database;
use PDO;
use PDOException;

final class Compra
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function search(string $q): array
    {
        $q = trim($q);
        $pdo = Database::pdo();

        if ($q === '') {
            $st = $pdo->prepare('
                SELECT c.*, t.nombre_comercial AS tercero_nombre
                FROM compras c
                LEFT JOIN terceros t ON t.id = c.tercero_id
                ORDER BY c.id DESC
                LIMIT 200
            ');
            $st->execute();
            return $st->fetchAll();
        }

        $like = '%' . $q . '%';
        $st = $pdo->prepare('
            SELECT c.*, t.nombre_comercial AS tercero_nombre
            FROM compras c
            LEFT JOIN terceros t ON t.id = c.tercero_id
            WHERE c.numero LIKE :q
            ORDER BY c.id DESC
            LIMIT 200
        ');
        $st->execute([':q' => $like]);

        return $st->fetchAll();
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
        $st = $pdo->prepare('
            SELECT c.*,
                   t.nombre_comercial AS tercero_nombre,
                   t.identificacion AS tercero_identificacion,
                   t.email AS tercero_email,
                   t.tipo AS tercero_tipo
            FROM compras c
            LEFT JOIN terceros t ON t.id = c.tercero_id
            WHERE c.id = :id
            LIMIT 1
        ');
        $st->execute([':id' => $id]);

        $row = $st->fetch();
        return is_array($row) ? $row : null;
    }

    /**
     * @param array{fecha:string,tercero_id:int,estado:string,subtotal:string,total:string} $header
     * @param array<int, array{producto_id:?int,descripcion:string,cantidad:string,costo_unitario:string,subtotal_linea:string}> $lines
     */
    public static function createWithDetails(array $header, array $lines): int
    {
        $pdo = Database::pdo();

        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $pdo->beginTransaction();

                $numero = self::generateNumeroFromMaxId($pdo);

                $st = $pdo->prepare('
                    INSERT INTO compras (numero, fecha, tercero_id, estado, subtotal, total, created_at, updated_at)
                    VALUES (:numero, :fecha, :tercero_id, :estado, :subtotal, :total, NOW(), NOW())
                ');
                $st->execute([
                    ':numero' => $numero,
                    ':fecha' => $header['fecha'],
                    ':tercero_id' => $header['tercero_id'],
                    ':estado' => $header['estado'],
                    ':subtotal' => $header['subtotal'],
                    ':total' => $header['total'],
                ]);

                $compraId = (int)$pdo->lastInsertId();

                CompraDetalle::insertMany($pdo, $compraId, $lines);

                $pdo->commit();
                return $compraId;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($attempt === 0 && self::isDuplicateKey($e)) {
                    continue;
                }
                throw $e;
            }
        }

        throw new PDOException('No se pudo generar número de compra único.');
    }

    private static function generateNumeroFromMaxId(PDO $pdo): string
    {
        $st = $pdo->prepare('SELECT IFNULL(MAX(id), 0) + 1 AS next_id FROM compras');
        $st->execute();
        $row = $st->fetch();

        $next = 1;
        if (is_array($row) && isset($row['next_id']) && is_numeric($row['next_id'])) {
            $next = (int)$row['next_id'];
            if ($next <= 0) {
                $next = 1;
            }
        }

        return 'C-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
    }

    private static function isDuplicateKey(PDOException $e): bool
    {
        if ($e->getCode() !== '23000') {
            return false;
        }
        $info = $e->errorInfo ?? null;
        return is_array($info) && isset($info[1]) && (int)$info[1] === 1062;
    }
}