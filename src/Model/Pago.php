<?php
declare(strict_types=1);

namespace Erp2\Model;

use Erp2\Core\Database;
use PDO;
use Throwable;

final class Pago
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function search(string $q, ?string $tipoRef = null, ?int $refId = null): array
    {
        $pdo = Database::pdo();

        $q = trim($q);
        $params = [];

        $sql = "
            SELECT
                p.*,
                t.nombre_comercial AS tercero_nombre,
                COALESCE(f.numero, c.numero) AS ref_numero
            FROM pagos p
            LEFT JOIN terceros t ON t.id = p.tercero_id
            LEFT JOIN facturas f ON (p.tipo_ref = 'factura' AND f.id = p.ref_id)
            LEFT JOIN compras  c ON (p.tipo_ref = 'compra'  AND c.id = p.ref_id)
            WHERE 1=1
        ";

        if (is_string($tipoRef) && in_array($tipoRef, ['factura', 'compra'], true)) {
            $sql .= " AND p.tipo_ref = :tipo_ref";
            $params[':tipo_ref'] = $tipoRef;
        }

        if (is_int($refId) && $refId > 0) {
            $sql .= " AND p.ref_id = :ref_id";
            $params[':ref_id'] = $refId;
        }

        if ($q !== '') {
            $like = '%' . $q . '%';

            // Detecta el nombre real de la columna "Ref." en pagos (referencia o ref)
            $refCol = self::resolvePagosRefColumn($pdo);

            $sql .= " AND (";
            $sql .= " p.metodo LIKE :q1";
            $params[':q1'] = $like;

            // Si existe columna de referencia, la incluimos sin romper compatibilidad
            if ($refCol !== null) {
                $sql .= " OR p.`{$refCol}` LIKE :q2";
                $params[':q2'] = $like;
            }

            $sql .= " OR p.nota LIKE :q3";
            $params[':q3'] = $like;

            $sql .= " OR t.nombre_comercial LIKE :q4";
            $params[':q4'] = $like;

            // OJO: NO uses alias ref_numero en WHERE (MySQL no lo permite). Usa la expresión.
            $sql .= " OR COALESCE(f.numero, c.numero) LIKE :q5";
            $params[':q5'] = $like;

            $sql .= " )";
        }

        $sql .= " ORDER BY p.fecha DESC, p.id DESC LIMIT 300";

        $st = $pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll();
    }

    /**
    * Cachea el nombre real de la columna referencia en la tabla pagos.
    * Acepta 'referencia' o 'ref'. Si no existe ninguna, retorna null.
    */
    private static ?string $pagosRefColumn = null;

    private static function resolvePagosRefColumn(\PDO $pdo): ?string
    {
        if (self::$pagosRefColumn !== null) {
            return self::$pagosRefColumn;
        }

        // orden de preferencia
        foreach (['referencia', 'ref'] as $col) {
            if (self::tableHasColumn($pdo, 'pagos', $col)) {
                self::$pagosRefColumn = $col;
                return self::$pagosRefColumn;
            }
        }

        self::$pagosRefColumn = null;
        return null;
    }

    private static function tableHasColumn(\PDO $pdo, string $table, string $column): bool
    {
        $st = $pdo->prepare("
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = :t
              AND column_name = :c
            LIMIT 1
        ");
        $st->execute([':t' => $table, ':c' => $column]);
        return (bool)$st->fetchColumn();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listByRef(string $tipoRef, int $refId): array
    {
        if (!in_array($tipoRef, ['factura', 'compra'], true) || $refId <= 0) {
            return [];
        }

        $pdo = Database::pdo();
        $st = $pdo->prepare("
            SELECT p.*, t.nombre_comercial AS tercero_nombre
            FROM pagos p
            LEFT JOIN terceros t ON t.id = p.tercero_id
            WHERE p.tipo_ref = :t AND p.ref_id = :rid
            ORDER BY p.fecha DESC, p.id DESC
        ");
        $st->execute([':t' => $tipoRef, ':rid' => $refId]);

        return $st->fetchAll();
    }

    public static function sumByRef(string $tipoRef, int $refId): float
    {
        if (!in_array($tipoRef, ['factura', 'compra'], true) || $refId <= 0) {
            return 0.0;
        }

        $pdo = Database::pdo();
        $st = $pdo->prepare("
            SELECT COALESCE(SUM(monto), 0) AS total_pagado
            FROM pagos
            WHERE tipo_ref = :t AND ref_id = :rid
        ");
        $st->execute([':t' => $tipoRef, ':rid' => $refId]);

        $row = $st->fetch();
        $v = is_array($row) ? (string)($row['total_pagado'] ?? '0') : '0';

        return (float)$v;
    }

    /**
     * Inserta un pago usando el PDO de una transacción existente.
     *
     * @param array{
     *   tipo_ref:string, ref_id:int, tercero_id:int, fecha:string, monto:string,
     *   metodo:string, referencia:?string, nota:?string, usuario_id:?int
     * } $data
     */
    public static function createWithPdo(PDO $pdo, array $data): int
    {
        $st = $pdo->prepare("
            INSERT INTO pagos
              (tipo_ref, ref_id, tercero_id, fecha, monto, metodo, referencia, nota, usuario_id, created_at, updated_at)
            VALUES
              (:tipo_ref, :ref_id, :tercero_id, :fecha, :monto, :metodo, :referencia, :nota, :usuario_id, NOW(), NOW())
        ");

        $st->execute([
            ':tipo_ref' => $data['tipo_ref'],
            ':ref_id' => $data['ref_id'],
            ':tercero_id' => $data['tercero_id'],
            ':fecha' => $data['fecha'],
            ':monto' => $data['monto'],
            ':metodo' => $data['metodo'],
            ':referencia' => $data['referencia'],
            ':nota' => $data['nota'],
            ':usuario_id' => $data['usuario_id'],
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Devuelve el pago para borrado seguro.
     *
     * @return array<string,mixed>|null
     */
    public static function findByIdForUpdate(PDO $pdo, int $id): ?array
    {
        if ($id <= 0) return null;

        $st = $pdo->prepare("SELECT * FROM pagos WHERE id = :id FOR UPDATE");
        $st->execute([':id' => $id]);
        $row = $st->fetch();

        return is_array($row) ? $row : null;
    }

    public static function deleteByIdWithPdo(PDO $pdo, int $id): bool
    {
        if ($id <= 0) return false;

        $st = $pdo->prepare("DELETE FROM pagos WHERE id = :id");
        $st->execute([':id' => $id]);

        return $st->rowCount() > 0;
    }
}