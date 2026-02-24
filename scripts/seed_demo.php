<?php
declare(strict_types=1);

/**
 * Seed/Demo mínimo reproducible (IDEMPOTENTE)
 * Ejecutar:
 *   php scripts/seed_demo.php
 */

use Dotenv\Dotenv;
use Erp2\Core\Database;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script debe ejecutarse por CLI.\n");
    exit(1);
}

$root = dirname(__DIR__);

// Autoload
$autoload = $root . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "No se encontró vendor/autoload.php. Ejecuta: composer install\n");
    exit(1);
}
require $autoload;

// .env
if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

function out(string $msg): void {
    fwrite(STDOUT, $msg . PHP_EOL);
}

function warn(string $msg): void {
    fwrite(STDERR, $msg . PHP_EOL);
}

function tableExists(PDO $pdo, string $table): bool {
    $st = $pdo->prepare("
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = :t
        LIMIT 1
    ");
    $st->execute([':t' => $table]);
    return (bool)$st->fetchColumn();
}

/** @return array<string,bool> */
function columns(PDO $pdo, string $table): array {
    $cols = [];
    try {
        $st = $pdo->prepare("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = :t
        ");
        $st->execute([':t' => $table]);
        while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
            $c = $r['column_name'] ?? null;
            if (is_string($c) && $c !== '') $cols[$c] = true;
        }
        if (!empty($cols)) return $cols;
    } catch (Throwable) {
        // fallback
    }

    try {
        $st = $pdo->query('DESCRIBE ' . $table);
        if ($st) {
            while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
                $f = $r['Field'] ?? null;
                if (is_string($f) && $f !== '') $cols[$f] = true;
            }
        }
    } catch (Throwable) {
        // no-op
    }

    return $cols;
}

function firstExistingTable(PDO $pdo, array $candidates): ?string {
    foreach ($candidates as $t) {
        if (is_string($t) && $t !== '' && tableExists($pdo, $t)) return $t;
    }
    return null;
}

final class RawExpr {
    public string $sql;
    public function __construct(string $sql) { $this->sql = $sql; }
}

/**
 * Inserta (si no existe) y retorna ID.
 * $whereSql ejemplo: "email = :email"
 * $whereParams ejemplo: [':email' => 'x@x.com']
 * $data cols=>val para INSERT (solo se usarán cols existentes en tabla)
 */
function ensureRow(PDO $pdo, string $table, string $whereSql, array $whereParams, array $data): int
{
    $st = $pdo->prepare("SELECT id FROM {$table} WHERE {$whereSql} LIMIT 1");
    $st->execute($whereParams);
    $id = $st->fetchColumn();
    if (is_numeric($id)) return (int)$id;

    $cols = columns($pdo, $table);
    if (empty($cols)) {
        throw new RuntimeException("No se pudieron leer columnas de {$table}");
    }

    $insertCols = [];
    $placeholders = [];
    $params = [];

    foreach ($data as $k => $v) {
        if (!is_string($k) || $k === '') continue;
        if (!isset($cols[$k])) continue;

        $insertCols[] = $k;

        if ($v instanceof RawExpr) {
            $placeholders[] = $v->sql;
        } else {
            $ph = ':' . $k;
            $placeholders[] = $ph;
            $params[$ph] = $v;
        }
    }

    if (empty($insertCols)) {
        throw new RuntimeException("No hay columnas insertables compatibles para {$table}");
    }

    $sql = "INSERT INTO {$table} (" . implode(',', $insertCols) . ") VALUES (" . implode(',', $placeholders) . ")";
    $ins = $pdo->prepare($sql);
    $ins->execute($params);

    return (int)$pdo->lastInsertId();
}

/** Asigna rol a usuario si no existe */
function ensureUserRole(PDO $pdo, int $uid, int $rid): void
{
    if ($uid <= 0 || $rid <= 0) return;
    if (!tableExists($pdo, 'usuario_roles')) return;

    $st = $pdo->prepare("SELECT 1 FROM usuario_roles WHERE usuario_id = :u AND rol_id = :r LIMIT 1");
    $st->execute([':u' => $uid, ':r' => $rid]);
    if ($st->fetchColumn()) return;

    $ins = $pdo->prepare("INSERT INTO usuario_roles (usuario_id, rol_id) VALUES (:u, :r)");
    $ins->execute([':u' => $uid, ':r' => $rid]);
}

/** Asigna permiso a rol si no existe */
function ensureRolePerm(PDO $pdo, int $rid, int $pid): void
{
    if ($rid <= 0 || $pid <= 0) return;
    if (!tableExists($pdo, 'rol_permisos')) return;

    $st = $pdo->prepare("SELECT 1 FROM rol_permisos WHERE rol_id = :r AND permiso_id = :p LIMIT 1");
    $st->execute([':r' => $rid, ':p' => $pid]);
    if ($st->fetchColumn()) return;

    $ins = $pdo->prepare("INSERT INTO rol_permisos (rol_id, permiso_id) VALUES (:r, :p)");
    $ins->execute([':r' => $rid, ':p' => $pid]);
}

/** Inserta pago con detección de columna referencia/ref y created_at/updated_at opcionales */
function ensurePago(
    PDO $pdo,
    string $tipoRef,
    int $refId,
    int $terceroId,
    string $fecha,
    string $monto,
    string $metodo,
    string $referencia,
    string $nota,
    ?int $usuarioId
): int {
    if (!tableExists($pdo, 'pagos')) return 0;

    $cols = columns($pdo, 'pagos');
    $refCol = null;
    if (isset($cols['referencia'])) $refCol = 'referencia';
    elseif (isset($cols['ref'])) $refCol = 'ref';

    // Idempotencia: mismo tipo_ref + ref_id + monto + fecha
    $st = $pdo->prepare("
        SELECT id FROM pagos
        WHERE tipo_ref = :t AND ref_id = :rid AND fecha = :f AND monto = :m
        LIMIT 1
    ");
    $st->execute([':t' => $tipoRef, ':rid' => $refId, ':f' => $fecha, ':m' => $monto]);
    $id = $st->fetchColumn();
    if (is_numeric($id)) return (int)$id;

    $data = [
        'tipo_ref' => $tipoRef,
        'ref_id' => $refId,
        'tercero_id' => $terceroId,
        'fecha' => $fecha,
        'monto' => $monto,
        'metodo' => $metodo,
        'nota' => $nota,
        'usuario_id' => $usuarioId,
    ];

    if ($refCol !== null) {
        $data[$refCol] = $referencia;
    }

    if (isset($cols['created_at'])) $data['created_at'] = new RawExpr('NOW()');
    if (isset($cols['updated_at'])) $data['updated_at'] = new RawExpr('NOW()');

    $insertCols = [];
    $placeholders = [];
    $params = [];
    foreach ($data as $k => $v) {
        if (!isset($cols[$k])) continue;
        $insertCols[] = $k;
        if ($v instanceof RawExpr) {
            $placeholders[] = $v->sql;
        } else {
            $ph = ':' . $k;
            $placeholders[] = $ph;
            $params[$ph] = $v;
        }
    }

    if (empty($insertCols)) return 0;

    $sql = "INSERT INTO pagos (" . implode(',', $insertCols) . ") VALUES (" . implode(',', $placeholders) . ")";
    $ins = $pdo->prepare($sql);
    $ins->execute($params);

    return (int)$pdo->lastInsertId();
}

/**
 * Inserta movimiento de inventario (idempotente) pero tolerante al schema.
 * - Inserta SOLO columnas existentes.
 * - Si faltan columnas críticas para idempotencia / referencia, degrada sin fallar.
 * - Si faltan columnas mínimas para un movimiento, emite WARN y omite el insert.
 */
function ensureInventarioMov(
    PDO $pdo,
    int $productoId,
    string $tipo,
    float $cantidad,
    float $saldoAnterior,
    float $saldoNuevo,
    ?int $usuarioId,
    string $nota,
    ?string $refTipo,
    ?int $refId
): void {
    if (!tableExists($pdo, 'inventario_movimientos')) return;

    $cols = columns($pdo, 'inventario_movimientos');
    if (empty($cols)) {
        warn("WARN: no se pudieron leer columnas de inventario_movimientos; se omite movimiento.");
        return;
    }

    // Columnas mínimas razonables para insertar algo útil:
    // (producto_id, tipo, cantidad) como base.
    $minOk = isset($cols['producto_id']) && isset($cols['tipo']) && isset($cols['cantidad']);
    if (!$minOk) {
        warn("WARN: inventario_movimientos no tiene columnas mínimas (producto_id/tipo/cantidad); se omite movimiento.");
        return;
    }

    // Determinar nombres de referencia (según schema)
    $refTipoCol = null;
    $refIdCol   = null;
    if (isset($cols['referencia_tipo'])) $refTipoCol = 'referencia_tipo';
    elseif (isset($cols['ref_tipo'])) $refTipoCol = 'ref_tipo';

    if (isset($cols['referencia_id'])) $refIdCol = 'referencia_id';
    elseif (isset($cols['ref_id'])) $refIdCol = 'ref_id';

    // Idempotencia (solo si hay columnas de referencia; si no, degradamos a una idempotencia más simple)
    try {
        if ($refTipoCol !== null && $refIdCol !== null) {
            $st = $pdo->prepare("
                SELECT 1
                FROM inventario_movimientos
                WHERE producto_id = :pid
                  AND tipo = :t
                  AND cantidad = :c
                  AND COALESCE({$refTipoCol},'') = :rt
                  AND COALESCE({$refIdCol},0) = :rid
                LIMIT 1
            ");
            $st->execute([
                ':pid' => $productoId,
                ':t'   => $tipo,
                ':c'   => $cantidad,
                ':rt'  => (string)($refTipo ?? ''),
                ':rid' => (int)($refId ?? 0),
            ]);
            if ($st->fetchColumn()) return;
        } else {
            // Fallback idempotencia: mismo producto_id + tipo + cantidad + nota (si existe)
            if (isset($cols['nota'])) {
                $st = $pdo->prepare("
                    SELECT 1
                    FROM inventario_movimientos
                    WHERE producto_id = :pid AND tipo = :t AND cantidad = :c AND nota = :n
                    LIMIT 1
                ");
                $st->execute([
                    ':pid' => $productoId,
                    ':t'   => $tipo,
                    ':c'   => $cantidad,
                    ':n'   => $nota,
                ]);
                if ($st->fetchColumn()) return;
            } else {
                // Sin nota ni referencia: no podemos asegurar idempotencia perfecta; evitamos fallar e insertamos 1 vez por ejecución
                // (Este caso es raro. Preferimos omitir para no duplicar.)
                warn("WARN: inventario_movimientos sin columnas de referencia/nota; se omite movimiento para evitar duplicados.");
                return;
            }
        }
    } catch (Throwable $e) {
        warn("WARN: error validando idempotencia de inventario_movimientos: " . $e->getMessage());
        // Continuamos a insertar intentando no fallar; si inserta duplicado, no es crítico para demo.
    }

    // Construir insert dinámico (solo cols existentes)
    $data = [
        'producto_id' => $productoId,
        'tipo' => $tipo,
        'cantidad' => $cantidad,
        'saldo_anterior' => $saldoAnterior,
        'saldo_nuevo' => $saldoNuevo,
        'usuario_id' => $usuarioId,
        'nota' => $nota,
    ];

    if ($refTipoCol !== null) $data[$refTipoCol] = $refTipo;
    if ($refIdCol !== null)   $data[$refIdCol]   = $refId;

    // timestamps opcionales
    if (isset($cols['created_at'])) $data['created_at'] = new RawExpr('NOW()');
    if (isset($cols['updated_at'])) $data['updated_at'] = new RawExpr('NOW()');

    $insertCols = [];
    $placeholders = [];
    $params = [];

    foreach ($data as $k => $v) {
        if (!isset($cols[$k])) continue;

        $insertCols[] = $k;
        if ($v instanceof RawExpr) {
            $placeholders[] = $v->sql;
        } else {
            $ph = ':' . $k;
            $placeholders[] = $ph;
            $params[$ph] = $v;
        }
    }

    if (empty($insertCols)) {
        warn("WARN: no hay columnas compatibles para insertar en inventario_movimientos; se omite movimiento.");
        return;
    }

    $sql = "INSERT INTO inventario_movimientos (" . implode(',', $insertCols) . ")
            VALUES (" . implode(',', $placeholders) . ")";
    try {
        $ins = $pdo->prepare($sql);
        $ins->execute($params);
    } catch (Throwable $e) {
        warn("WARN: no se pudo insertar inventario_movimientos (se omite): " . $e->getMessage());
    }
}

try {
    $pdo = Database::pdo();
    $pdo->beginTransaction();

    // ====== 0) Verificaciones mínimas ======
    foreach (['usuarios','roles','permisos','usuario_roles','rol_permisos','terceros','productos','facturas','compras','pagos'] as $t) {
        if (!tableExists($pdo, $t)) {
            warn("WARN: No existe tabla {$t} (el seed intentará continuar donde sea posible).");
        }
    }

    $facturaDetalleTable = firstExistingTable($pdo, ['factura_detalles', 'factura_detalle']);
    $compraDetalleTable  = firstExistingTable($pdo, ['compra_detalles', 'compra_detalle']);

    if ($facturaDetalleTable === null) warn("WARN: No existe tabla factura_detalles/factura_detalle.");
    if ($compraDetalleTable === null) warn("WARN: No existe tabla compra_detalles/compra_detalle.");

    // ====== 1) Usuarios ======
    $ventasId = 0;
    $comprasId = 0;

    if (tableExists($pdo, 'usuarios')) {
        $uCols = columns($pdo, 'usuarios');
        $nameCol = isset($uCols['nombre']) ? 'nombre' : (isset($uCols['nombres']) ? 'nombres' : null);

        // Admin id=1: si existe no tocar, si no existe crear
        $stA = $pdo->query("SELECT id, email FROM usuarios WHERE id = 1 LIMIT 1");
        $adminRow = $stA ? $stA->fetch(PDO::FETCH_ASSOC) : null;

        if (is_array($adminRow) && (int)($adminRow['id'] ?? 0) === 1) {
            out("OK: admin id=1 existe (" . (string)($adminRow['email'] ?? 'sin-email') . ") — no se modifica.");
        } else {
            $adminEmail = 'admin@demo.local';
            $adminPass = 'Admin1234!';
            $hash = password_hash($adminPass, PASSWORD_DEFAULT);

            // Intento insertar con id=1 para respetar regla admin-only
            $data = [
                'id' => 1,
                'email' => $adminEmail,
                'password_hash' => $hash,
            ];
            if ($nameCol !== null) $data[$nameCol] = 'Admin Demo';
            if (isset($uCols['created_at'])) $data['created_at'] = new RawExpr('NOW()');
            if (isset($uCols['updated_at'])) $data['updated_at'] = new RawExpr('NOW()');

            try {
                $adminId = ensureRow($pdo, 'usuarios', 'id = :id', [':id' => 1], $data);
                out("OK: admin id=1 creado (email {$adminEmail}). PASS={$adminPass}");
            } catch (Throwable $e) {
                // fallback: crear sin id fijo
                $data2 = $data;
                unset($data2['id']);
                $adminId2 = ensureRow($pdo, 'usuarios', 'email = :email', [':email' => $adminEmail], $data2);
                warn("WARN: No se pudo crear admin con id=1. Se creó admin con id={$adminId2} (email {$adminEmail}).");
                warn("      OJO: el sistema exige admin-only por id=1; crea manualmente un usuario con id=1 si lo necesitas.");
            }
        }

        // Usuarios demo (ventas / compras)
        $demoPass = 'Demo1234!';
        $hashDemo = password_hash($demoPass, PASSWORD_DEFAULT);

        $ventasData = [
            'email' => 'ventas@demo.local',
            'password_hash' => $hashDemo,
        ];
        if ($nameCol !== null) $ventasData[$nameCol] = 'Usuario Ventas';
        if (isset($uCols['created_at'])) $ventasData['created_at'] = new RawExpr('NOW()');
        if (isset($uCols['updated_at'])) $ventasData['updated_at'] = new RawExpr('NOW()');

        $comprasData = [
            'email' => 'compras@demo.local',
            'password_hash' => $hashDemo,
        ];
        if ($nameCol !== null) $comprasData[$nameCol] = 'Usuario Compras';
        if (isset($uCols['created_at'])) $comprasData['created_at'] = new RawExpr('NOW()');
        if (isset($uCols['updated_at'])) $comprasData['updated_at'] = new RawExpr('NOW()');

        $ventasId = ensureRow($pdo, 'usuarios', 'email = :email', [':email' => 'ventas@demo.local'], $ventasData);
        out("OK: usuario ventas@demo.local listo (id={$ventasId})");

        $comprasId = ensureRow($pdo, 'usuarios', 'email = :email', [':email' => 'compras@demo.local'], $comprasData);
        out("OK: usuario compras@demo.local listo (id={$comprasId})");
    }

    // ====== 2) Roles/Permisos ======
    $ventasRoleId = 0;
    $comprasRoleId = 0;
    $adminRoleId = 0;

    if (tableExists($pdo, 'roles') && tableExists($pdo, 'permisos')) {
        $rCols = columns($pdo, 'roles');
        $createdCol = isset($rCols['created_at']) ? 'created_at' : (isset($rCols['creted_at']) ? 'creted_at' : null);

        $ventasRoleId = ensureRow(
            $pdo,
            'roles',
            'nombre = :n',
            [':n' => 'Ventas'],
            array_filter([
                'nombre' => 'Ventas',
                'descripcion' => 'Rol demo para ventas',
                $createdCol ?? '' => $createdCol ? new RawExpr('NOW()') : null,
            ], fn($v, $k) => is_string($k) && $k !== '' && $v !== null, ARRAY_FILTER_USE_BOTH)
        );

        $comprasRoleId = ensureRow(
            $pdo,
            'roles',
            'nombre = :n',
            [':n' => 'Compras'],
            array_filter([
                'nombre' => 'Compras',
                'descripcion' => 'Rol demo para compras',
                $createdCol ?? '' => $createdCol ? new RawExpr('NOW()') : null,
            ], fn($v, $k) => is_string($k) && $k !== '' && $v !== null, ARRAY_FILTER_USE_BOTH)
        );

        $adminRoleId = ensureRow(
            $pdo,
            'roles',
            'nombre = :n',
            [':n' => 'Admin'],
            array_filter([
                'nombre' => 'Admin',
                'descripcion' => 'Rol administrativo',
                $createdCol ?? '' => $createdCol ? new RawExpr('NOW()') : null,
            ], fn($v, $k) => is_string($k) && $k !== '' && $v !== null, ARRAY_FILTER_USE_BOTH)
        );

        out("OK: roles Ventas/Compras/Admin listos");

        // Permisos mínimos por código (MEJORA: incluye pagos.eliminar)
        $permCodes = [
            'terceros.ver','terceros.crear','terceros.editar',
            'productos.ver','productos.crear','productos.editar',
            'facturas.ver','facturas.crear','facturas.editar','facturas.emitir','facturas.anular',
            'compras.ver','compras.crear','compras.editar','compras.emitir','compras.anular',
            'pagos.ver','pagos.crear','pagos.eliminar',
            'inventario.ver','inventario.ajustar',
            'cartera.ver',
            'auditoria.ver',
            'seguridad.ver',
        ];

        $permCols = columns($pdo, 'permisos');

        /** @var array<string,int> $permIdByCode */
        $permIdByCode = [];
        foreach ($permCodes as $code) {
            if (!isset($permCols['codigo'])) continue;

            $pid = ensureRow(
                $pdo,
                'permisos',
                'codigo = :c',
                [':c' => $code],
                ['codigo' => $code]
            );
            $permIdByCode[$code] = $pid;
        }
        out("OK: permisos mínimos listos");

        // Asignación permisos a roles (roles basta)
        $ventasPerms = [
            'terceros.ver','terceros.crear','terceros.editar',
            'productos.ver',
            'facturas.ver','facturas.crear','facturas.editar','facturas.emitir','facturas.anular',
            'pagos.ver','pagos.crear',
            'cartera.ver',
        ];

        $comprasPerms = [
            'terceros.ver',
            'productos.ver',
            'compras.ver','compras.crear','compras.editar','compras.emitir','compras.anular',
            'pagos.ver','pagos.crear',
            'inventario.ver',
            'cartera.ver',
        ];

        // Admin: todos los permisos mínimos (incluye pagos.eliminar)
        $adminPerms = array_keys($permIdByCode);

        foreach ($ventasPerms as $c) {
            if (isset($permIdByCode[$c])) ensureRolePerm($pdo, $ventasRoleId, $permIdByCode[$c]);
        }
        foreach ($comprasPerms as $c) {
            if (isset($permIdByCode[$c])) ensureRolePerm($pdo, $comprasRoleId, $permIdByCode[$c]);
        }
        foreach ($adminPerms as $c) {
            if (isset($permIdByCode[$c])) ensureRolePerm($pdo, $adminRoleId, $permIdByCode[$c]);
        }

        // usuario_roles
        if ($ventasId > 0) ensureUserRole($pdo, $ventasId, $ventasRoleId);
        if ($comprasId > 0) ensureUserRole($pdo, $comprasId, $comprasRoleId);

        out("OK: roles asignados a usuarios demo");
    } else {
        warn("WARN: No se pudieron asegurar roles/permisos (tablas faltantes).");
    }

    // ====== 3) Terceros (cliente + proveedor) ======
    $clienteId = 0;
    $proveedorId = 0;

    if (tableExists($pdo, 'terceros')) {
        $tCols = columns($pdo, 'terceros');

        $clienteData = [
            'tipo' => 'cliente',
            'nombre_comercial' => 'Cliente Demo S.A.',
            'identificacion' => 'CLI-DEMO-001',
            'email' => 'cliente@demo.local',
        ];
        if (isset($tCols['estado'])) $clienteData['estado'] = 1;

        $proveedorData = [
            'tipo' => 'proveedor',
            'nombre_comercial' => 'Proveedor Demo S.A.',
            'identificacion' => 'PROV-DEMO-001',
            'email' => 'proveedor@demo.local',
        ];
        if (isset($tCols['estado'])) $proveedorData['estado'] = 1;

        $clienteId = ensureRow($pdo, 'terceros', 'identificacion = :i', [':i' => 'CLI-DEMO-001'], $clienteData);
        out("OK: cliente demo creado/encontrado (id={$clienteId})");

        $proveedorId = ensureRow($pdo, 'terceros', 'identificacion = :i', [':i' => 'PROV-DEMO-001'], $proveedorData);
        out("OK: proveedor demo creado/encontrado (id={$proveedorId})");
    } else {
        warn("WARN: No existe tabla terceros.");
    }

    // ====== 4) Productos/Servicios ======
    $productoId = 0;
    $servicioId = 0;

    if (tableExists($pdo, 'productos')) {
        $pCols = columns($pdo, 'productos');

        // Producto demo
        $prodRef = 'PROD-DEMO-001';
        $prodData = [
            'tipo' => 'producto',
            'referencia' => $prodRef,
            'nombre' => 'Laptop Demo',
            'descripcion' => 'Producto de demostración',
            'precio_venta' => '1200.00',
            'costo' => '800.00',
        ];
        if (isset($pCols['estado'])) $prodData['estado'] = 1;
        if (isset($pCols['created_at'])) $prodData['created_at'] = new RawExpr('NOW()');
        if (isset($pCols['updated_at'])) $prodData['updated_at'] = new RawExpr('NOW()');
        if (isset($pCols['stock_actual'])) $prodData['stock_actual'] = 10;

        $productoId = ensureRow($pdo, 'productos', 'referencia = :r', [':r' => $prodRef], $prodData);

        // Stock inicial (si existe inventario_movimientos) sin romper schema
        if (isset($pCols['stock_actual']) && tableExists($pdo, 'inventario_movimientos')) {
            $imCols = columns($pdo, 'inventario_movimientos');
            $refTipoCol = isset($imCols['referencia_tipo']) ? 'referencia_tipo' : (isset($imCols['ref_tipo']) ? 'ref_tipo' : null);
            $refIdCol   = isset($imCols['referencia_id']) ? 'referencia_id' : (isset($imCols['ref_id']) ? 'ref_id' : null);

            $hasInitMov = false;
            try {
                if ($refTipoCol && $refIdCol) {
                    $st = $pdo->prepare("
                        SELECT 1 FROM inventario_movimientos
                        WHERE producto_id = :pid AND COALESCE({$refTipoCol},'') = 'seed' AND COALESCE({$refIdCol},0) = 0
                        LIMIT 1
                    ");
                    $st->execute([':pid' => $productoId]);
                    $hasInitMov = (bool)$st->fetchColumn();
                } else {
                    // fallback: por nota si existe
                    if (isset($imCols['nota'])) {
                        $st = $pdo->prepare("
                            SELECT 1 FROM inventario_movimientos
                            WHERE producto_id = :pid AND nota = :n
                            LIMIT 1
                        ");
                        $st->execute([':pid' => $productoId, ':n' => 'Stock inicial seed demo']);
                        $hasInitMov = (bool)$st->fetchColumn();
                    }
                }
            } catch (Throwable) {
                $hasInitMov = false;
            }

            if (!$hasInitMov) {
                $stS = $pdo->prepare("SELECT stock_actual FROM productos WHERE id = :id LIMIT 1");
                $stS->execute([':id' => $productoId]);
                $cur = $stS->fetchColumn();
                $curF = is_numeric($cur) ? (float)$cur : 0.0;

                if ($curF <= 0.00001) {
                    $upd = $pdo->prepare("UPDATE productos SET stock_actual = :s" . (isset($pCols['updated_at']) ? ", updated_at = NOW()" : "") . " WHERE id = :id");
                    $upd->execute([':s' => 10, ':id' => $productoId]);

                    ensureInventarioMov($pdo, $productoId, 'entrada', 10.0, 0.0, 10.0, 1, 'Stock inicial seed demo', 'seed', 0);
                    out("OK: producto demo stock inicial=10 registrado");
                } else {
                    out("OK: producto demo existe con stock_actual={$curF} (no se modifica)");
                }
            } else {
                out("OK: producto demo ya tiene seed de stock inicial");
            }
        } else {
            out("OK: producto demo creado/encontrado (id={$productoId})");
        }

        // Servicio demo (sin stock)
        $srvRef = 'SERV-DEMO-001';
        $srvData = [
            'tipo' => 'servicio',
            'referencia' => $srvRef,
            'nombre' => 'Consultoría Demo',
            'descripcion' => 'Servicio de demostración',
            'precio_venta' => '300.00',
            'costo' => null,
        ];
        if (isset($pCols['estado'])) $srvData['estado'] = 1;
        if (isset($pCols['created_at'])) $srvData['created_at'] = new RawExpr('NOW()');
        if (isset($pCols['updated_at'])) $srvData['updated_at'] = new RawExpr('NOW()');

        $servicioId = ensureRow($pdo, 'productos', 'referencia = :r', [':r' => $srvRef], $srvData);

        out("OK: servicio demo creado/encontrado (id={$servicioId})");
    } else {
        warn("WARN: No existe tabla productos.");
    }

    // ====== 5) Compra demo emitida + pago total ======
    $compraId = 0;

    if (tableExists($pdo, 'compras') && $compraDetalleTable !== null && $proveedorId > 0) {
        $cCols = columns($pdo, 'compras');

        $compraNumero = 'C-DEMO-001';
        $fechaCompra = date('Y-m-d');

        // líneas compra: producto (qty 5) + servicio (qty 1)
        $qtyCompraProd = 5.0;
        $costoProd = 750.00;
        $subProd = round($qtyCompraProd * $costoProd, 2);

        $qtyCompraSrv = 1.0;
        $costoSrv = 200.00;
        $subSrv = round($qtyCompraSrv * $costoSrv, 2);

        $totalCompra = round($subProd + $subSrv, 2);
        $totalCompraStr = number_format($totalCompra, 2, '.', '');

        // Idempotencia por numero
        $st = $pdo->prepare("SELECT id FROM compras WHERE numero = :n LIMIT 1");
        $st->execute([':n' => $compraNumero]);
        $found = $st->fetchColumn();

        if (is_numeric($found)) {
            $compraId = (int)$found;
            out("OK: compra demo encontrada (id={$compraId})");
        } else {
            $data = [
                'numero' => $compraNumero,
                'fecha' => $fechaCompra,
                'tercero_id' => $proveedorId,
                'estado' => 'emitida',
                'subtotal' => $totalCompraStr,
                'total' => $totalCompraStr,
            ];
            if (isset($cCols['created_at'])) $data['created_at'] = new RawExpr('NOW()');
            if (isset($cCols['updated_at'])) $data['updated_at'] = new RawExpr('NOW()');

            $compraId = ensureRow($pdo, 'compras', 'numero = :n', [':n' => $compraNumero], $data);

            // Insert detalles si no existen
            $stD = $pdo->prepare("SELECT 1 FROM {$compraDetalleTable} WHERE compra_id = :cid LIMIT 1");
            $stD->execute([':cid' => $compraId]);
            $hasDet = (bool)$stD->fetchColumn();

            if (!$hasDet) {
                $insD = $pdo->prepare("
                    INSERT INTO {$compraDetalleTable}
                      (compra_id, producto_id, descripcion, cantidad, costo_unitario, subtotal_linea)
                    VALUES
                      (:cid, :pid, :desc, :cant, :costo, :sub)
                ");

                // producto
                $insD->execute([
                    ':cid' => $compraId,
                    ':pid' => $productoId > 0 ? $productoId : null,
                    ':desc' => 'Compra Laptop Demo',
                    ':cant' => number_format($qtyCompraProd, 2, '.', ''),
                    ':costo' => number_format($costoProd, 2, '.', ''),
                    ':sub' => number_format($subProd, 2, '.', ''),
                ]);

                // servicio
                $insD->execute([
                    ':cid' => $compraId,
                    ':pid' => $servicioId > 0 ? $servicioId : null,
                    ':desc' => 'Consultoría incluida (compra)',
                    ':cant' => number_format($qtyCompraSrv, 2, '.', ''),
                    ':costo' => number_format($costoSrv, 2, '.', ''),
                    ':sub' => number_format($subSrv, 2, '.', ''),
                ]);
            }

            // Impacto inventario (solo productos)
            if ($productoId > 0 && tableExists($pdo, 'productos')) {
                $pCols2 = columns($pdo, 'productos');
                if (isset($pCols2['stock_actual'])) {
                    $stP = $pdo->prepare("SELECT stock_actual FROM productos WHERE id = :id FOR UPDATE");
                    $stP->execute([':id' => $productoId]);
                    $cur = $stP->fetchColumn();
                    $stockAnterior = is_numeric($cur) ? (float)$cur : 0.0;
                    $stockNuevo = $stockAnterior + $qtyCompraProd;

                    $upd = $pdo->prepare("UPDATE productos SET stock_actual = :s" . (isset($pCols2['updated_at']) ? ", updated_at = NOW()" : "") . " WHERE id = :id");
                    $upd->execute([':s' => $stockNuevo, ':id' => $productoId]);

                    ensureInventarioMov(
                        $pdo,
                        $productoId,
                        'entrada',
                        $qtyCompraProd,
                        $stockAnterior,
                        $stockNuevo,
                        $comprasId > 0 ? $comprasId : 1,
                        'Entrada por seed compra emitida',
                        'compra',
                        $compraId
                    );
                }
            }

            out("OK: compra demo emitida creada (id={$compraId})");
        }

        // Pago total compra (para cartera=pagado)
        $pagoCompraId = ensurePago(
            $pdo,
            'compra',
            $compraId,
            $proveedorId,
            $fechaCompra,
            $totalCompraStr,
            'transferencia',
            'TRX-C-DEMO-001',
            'Pago total compra demo',
            $comprasId > 0 ? $comprasId : 1
        );
        if ($pagoCompraId > 0) out("OK: pago total compra demo creado/encontrado (pago_id={$pagoCompraId})");
        else warn("WARN: no se pudo crear pago compra (tabla pagos faltante?)");
    } else {
        warn("WARN: No se pudo crear compra demo (faltan tablas o proveedor).");
    }

    // ====== 6) Factura demo emitida + pago parcial ======
    $facturaId = 0;

    if (tableExists($pdo, 'facturas') && $facturaDetalleTable !== null && $clienteId > 0) {
        $fCols = columns($pdo, 'facturas');

        $facturaNumero = 'F-DEMO-001';
        $fechaFactura = date('Y-m-d');

        // líneas factura: producto (qty 2) + servicio (qty 1)
        $qtyFacProd = 2.0;
        $precioProd = 1200.00;
        $subProd2 = round($qtyFacProd * $precioProd, 2);

        $qtyFacSrv = 1.0;
        $precioSrv = 300.00;
        $subSrv2 = round($qtyFacSrv * $precioSrv, 2);

        $totalFactura = round($subProd2 + $subSrv2, 2);
        $totalFacturaStr = number_format($totalFactura, 2, '.', '');

        // Idempotencia por numero
        $st = $pdo->prepare("SELECT id FROM facturas WHERE numero = :n LIMIT 1");
        $st->execute([':n' => $facturaNumero]);
        $found = $st->fetchColumn();

        if (is_numeric($found)) {
            $facturaId = (int)$found;
            out("OK: factura demo encontrada (id={$facturaId})");
        } else {
            $data = [
                'numero' => $facturaNumero,
                'fecha' => $fechaFactura,
                'tercero_id' => $clienteId,
                'estado' => 'emitida',
                'subtotal' => $totalFacturaStr,
                'total' => $totalFacturaStr,
            ];
            if (isset($fCols['created_at'])) $data['created_at'] = new RawExpr('NOW()');
            if (isset($fCols['updated_at'])) $data['updated_at'] = new RawExpr('NOW()');

            $facturaId = ensureRow($pdo, 'facturas', 'numero = :n', [':n' => $facturaNumero], $data);

            // Insert detalles si no existen
            $stD = $pdo->prepare("SELECT 1 FROM {$facturaDetalleTable} WHERE factura_id = :fid LIMIT 1");
            $stD->execute([':fid' => $facturaId]);
            $hasDet = (bool)$stD->fetchColumn();

            if (!$hasDet) {
                $insD = $pdo->prepare("
                    INSERT INTO {$facturaDetalleTable}
                      (factura_id, producto_id, descripcion, cantidad, precio_unitario, subtotal_linea)
                    VALUES
                      (:fid, :pid, :desc, :cant, :precio, :sub)
                ");

                // producto
                $insD->execute([
                    ':fid' => $facturaId,
                    ':pid' => $productoId > 0 ? $productoId : null,
                    ':desc' => 'Venta Laptop Demo',
                    ':cant' => number_format($qtyFacProd, 2, '.', ''),
                    ':precio' => number_format($precioProd, 2, '.', ''),
                    ':sub' => number_format($subProd2, 2, '.', ''),
                ]);

                // servicio
                $insD->execute([
                    ':fid' => $facturaId,
                    ':pid' => $servicioId > 0 ? $servicioId : null,
                    ':desc' => 'Consultoría Demo',
                    ':cant' => number_format($qtyFacSrv, 2, '.', ''),
                    ':precio' => number_format($precioSrv, 2, '.', ''),
                    ':sub' => number_format($subSrv2, 2, '.', ''),
                ]);
            }

            // Impacto inventario (solo productos)
            if ($productoId > 0 && tableExists($pdo, 'productos')) {
                $pCols2 = columns($pdo, 'productos');
                if (isset($pCols2['stock_actual'])) {
                    $stP = $pdo->prepare("SELECT stock_actual FROM productos WHERE id = :id FOR UPDATE");
                    $stP->execute([':id' => $productoId]);
                    $cur = $stP->fetchColumn();
                    $stockAnterior = is_numeric($cur) ? (float)$cur : 0.0;
                    $stockNuevo = $stockAnterior - $qtyFacProd;
                    if ($stockNuevo < 0) {
                        warn("WARN: stock quedaría negativo por factura demo. Se mantienen datos factura/pago, se omite salida inventario.");
                    } else {
                        $upd = $pdo->prepare("UPDATE productos SET stock_actual = :s" . (isset($pCols2['updated_at']) ? ", updated_at = NOW()" : "") . " WHERE id = :id");
                        $upd->execute([':s' => $stockNuevo, ':id' => $productoId]);

                        ensureInventarioMov(
                            $pdo,
                            $productoId,
                            'salida',
                            $qtyFacProd,
                            $stockAnterior,
                            $stockNuevo,
                            $ventasId > 0 ? $ventasId : 1,
                            'Salida por seed factura emitida',
                            'factura',
                            $facturaId
                        );
                    }
                }
            }

            out("OK: factura demo emitida creada (id={$facturaId})");
        }

        // Pago parcial factura (para cartera=parcial)
        $pagoParcial = number_format(round($totalFactura * 0.35, 2), 2, '.', ''); // 35% aprox
        $pagoFacturaId = ensurePago(
            $pdo,
            'factura',
            $facturaId,
            $clienteId,
            $fechaFactura,
            $pagoParcial,
            'efectivo',
            'RCB-F-DEMO-001',
            'Pago parcial factura demo',
            $ventasId > 0 ? $ventasId : 1
        );
        if ($pagoFacturaId > 0) out("OK: factura demo emitida + pago parcial (pago_id={$pagoFacturaId})");
        else warn("WARN: no se pudo crear pago factura (tabla pagos faltante?)");
    } else {
        warn("WARN: No se pudo crear factura demo (faltan tablas o cliente).");
    }

    $pdo->commit();

    out("Listo. Puedes iniciar sesión con ventas@demo.local / Demo1234!");
    out("Listo. Puedes iniciar sesión con compras@demo.local / Demo1234!");
} catch (Throwable $e) {
    try {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
    } catch (Throwable) {
        // no-op
    }

    warn("ERROR: " . $e->getMessage());
    warn("TRACE: " . $e->getFile() . ':' . $e->getLine());
    exit(1);
}