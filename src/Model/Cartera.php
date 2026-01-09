<?php
declare(strict_types=1);

namespace Erp2\Model;

use Erp2\Core\Database;

final class Cartera
{
    /**
     * @param array{
     *   q?: string,
     *   tercero_id?: int|null,
     *   desde?: string|null,
     *   hasta?: string|null,
     *   estado_pago?: string|null,
     *   limit?: int
     * } $filters
     * @return array<int, array<string,mixed>>
     */
    public static function cxcFacturas(array $filters): array
    {
        $pdo = Database::pdo();

        $q = trim((string)($filters['q'] ?? ''));
        $terceroId = $filters['tercero_id'] ?? null;
        $desde = $filters['desde'] ?? null;
        $hasta = $filters['hasta'] ?? null;
        $estadoPago = $filters['estado_pago'] ?? null;
        $limit = (int)($filters['limit'] ?? 300);
        if ($limit <= 0 || $limit > 300) $limit = 300;

        $params = [];
        $where = " WHERE f.estado = 'emitida' ";

        if ($q !== '') {
            $where .= " AND (f.numero LIKE :q1 OR t.nombre_comercial LIKE :q2) ";
            $like = '%' . $q . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
        }

        if (is_int($terceroId) && $terceroId > 0) {
            $where .= " AND f.tercero_id = :tercero_id ";
            $params[':tercero_id'] = $terceroId;
        }

        if (is_string($desde) && $desde !== '') {
            $where .= " AND f.fecha >= :desde ";
            $params[':desde'] = $desde;
        }

        if (is_string($hasta) && $hasta !== '') {
            $where .= " AND f.fecha <= :hasta ";
            $params[':hasta'] = $hasta;
        }

        $outerWhere = "";
        if (is_string($estadoPago) && in_array($estadoPago, ['pendiente', 'parcial', 'pagado'], true)) {
            $outerWhere = " WHERE x.estado_pago = :estado_pago ";
            $params[':estado_pago'] = $estadoPago;
        }

        $sql = "
            SELECT *
            FROM (
                SELECT
                    f.id,
                    f.numero,
                    f.fecha,
                    f.tercero_id,
                    t.nombre_comercial AS tercero_nombre,
                    f.total,
                    COALESCE(pg.pagado, 0) AS pagado,
                    GREATEST(ROUND(f.total - COALESCE(pg.pagado, 0), 2), 0) AS saldo,
                    CASE
                        WHEN COALESCE(pg.pagado, 0) <= 0 THEN 'pendiente'
                        WHEN ABS(COALESCE(pg.pagado, 0) - f.total) < 0.00001 THEN 'pagado'
                        ELSE 'parcial'
                    END AS estado_pago
                FROM facturas f
                INNER JOIN terceros t ON t.id = f.tercero_id
                LEFT JOIN (
                    SELECT ref_id, SUM(monto) AS pagado
                    FROM pagos
                    WHERE tipo_ref = 'factura'
                    GROUP BY ref_id
                ) pg ON pg.ref_id = f.id
                {$where}
                ORDER BY f.fecha DESC, f.id DESC
                LIMIT {$limit}
            ) x
            {$outerWhere}
            ORDER BY x.fecha DESC, x.id DESC
        ";

        $st = $pdo->prepare($sql);
        $st->execute($params);

        /** @var array<int, array<string,mixed>> */
        return $st->fetchAll();
    }

    public static function cxpCompras(array $filters): array
    {
        $pdo = Database::pdo();

        $q = trim((string)($filters['q'] ?? ''));
        $terceroId = $filters['tercero_id'] ?? null;
        $desde = $filters['desde'] ?? null;
        $hasta = $filters['hasta'] ?? null;
        $estadoPago = $filters['estado_pago'] ?? null;
        $limit = (int)($filters['limit'] ?? 300);
        if ($limit <= 0 || $limit > 300) $limit = 300;

        $params = [];
        $where = " WHERE c.estado = 'emitida' ";

        if ($q !== '') {
            $where .= " AND (c.numero LIKE :q1 OR t.nombre_comercial LIKE :q2) ";
            $like = '%' . $q . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
        }

        if (is_int($terceroId) && $terceroId > 0) {
            $where .= " AND c.tercero_id = :tercero_id ";
            $params[':tercero_id'] = $terceroId;
        }

        if (is_string($desde) && $desde !== '') {
            $where .= " AND c.fecha >= :desde ";
            $params[':desde'] = $desde;
        }

        if (is_string($hasta) && $hasta !== '') {
            $where .= " AND c.fecha <= :hasta ";
            $params[':hasta'] = $hasta;
        }

        $outerWhere = "";
        if (is_string($estadoPago) && in_array($estadoPago, ['pendiente', 'parcial', 'pagado'], true)) {
            $outerWhere = " WHERE x.estado_pago = :estado_pago ";
            $params[':estado_pago'] = $estadoPago;
        }

        $sql = "
            SELECT *
            FROM (
                SELECT
                    c.id,
                    c.numero,
                    c.fecha,
                    c.tercero_id,
                    t.nombre_comercial AS tercero_nombre,
                    c.total,
                    COALESCE(pg.pagado, 0) AS pagado,
                    GREATEST(ROUND(c.total - COALESCE(pg.pagado, 0), 2), 0) AS saldo,
                    CASE
                        WHEN COALESCE(pg.pagado, 0) <= 0 THEN 'pendiente'
                        WHEN ABS(COALESCE(pg.pagado, 0) - c.total) < 0.00001 THEN 'pagado'
                        ELSE 'parcial'
                    END AS estado_pago
                FROM compras c
                INNER JOIN terceros t ON t.id = c.tercero_id
                LEFT JOIN (
                    SELECT ref_id, SUM(monto) AS pagado
                    FROM pagos
                    WHERE tipo_ref = 'compra'
                    GROUP BY ref_id
                ) pg ON pg.ref_id = c.id
                {$where}
                ORDER BY c.fecha DESC, c.id DESC
                LIMIT {$limit}
            ) x
            {$outerWhere}
            ORDER BY x.fecha DESC, x.id DESC
        ";

        $st = $pdo->prepare($sql);
        $st->execute($params);

        /** @var array<int, array<string,mixed>> */
        return $st->fetchAll();
    }
}