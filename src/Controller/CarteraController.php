<?php
declare(strict_types=1);

namespace Erp2\Controller;

use Erp2\Core\Auth;
use Erp2\Core\Database;
use Erp2\Core\View;
use Erp2\Model\Cartera;
use Throwable;

final class CarteraController
{
    public function index(): void
    {
        Auth::requireLogin();
        Auth::can('cartera.ver');

        $tipo = trim((string)($_GET['tipo'] ?? ''));
        if (!in_array($tipo, ['factura', 'compra', ''], true)) {
            $tipo = '';
        }

        $q = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($q) > 120) {
            $q = mb_substr($q, 0, 120);
        }

        $terceroId = null;
        $terceroRaw = trim((string)($_GET['tercero_id'] ?? ''));
        if ($terceroRaw !== '') {
            $tmp = (int)$terceroRaw;
            $terceroId = $tmp > 0 ? $tmp : null;
        }

        $desde = trim((string)($_GET['desde'] ?? ''));
        $hasta = trim((string)($_GET['hasta'] ?? ''));

        $errors = [];

        if ($desde !== '' && !$this->isValidDate($desde)) {
            $errors[] = 'Fecha "desde" inválida. Use YYYY-MM-DD.';
            $desde = '';
        }
        if ($hasta !== '' && !$this->isValidDate($hasta)) {
            $errors[] = 'Fecha "hasta" inválida. Use YYYY-MM-DD.';
            $hasta = '';
        }

        $estadoPago = trim((string)($_GET['estado_pago'] ?? ''));
        if ($estadoPago !== '' && !in_array($estadoPago, ['pendiente', 'parcial', 'pagado'], true)) {
            $errors[] = 'Estado de pago inválido.';
            $estadoPago = '';
        }

        $filters = [
            'q' => $q,
            'tercero_id' => $terceroId,
            'desde' => ($desde !== '') ? $desde : null,
            'hasta' => ($hasta !== '') ? $hasta : null,
            'estado_pago' => ($estadoPago !== '') ? $estadoPago : null,
            'limit' => 300,
        ];

        $cxc = [];
        $cxp = [];

        // DEBUG controlado: /cartera?debug=1 (o APP_DEBUG=1 en .env)
        $debug = $this->isDebug();

        // (Opcional pero útil) pre-chequeo para diagnosticar rápido tablas requeridas
        $this->checkRequiredTables($errors, $debug);

        // === BLOQUE CRÍTICO: evitar HTTP 500 capturando errores del Model/SQL ===
        if ($tipo === '' || $tipo === 'factura') {
            try {
                $cxc = Cartera::cxcFacturas($filters);
            } catch (Throwable $e) {
                $errors[] = $this->formatException('CXC (Facturas)', $e, $debug);
                $cxc = [];
            }
        }

        if ($tipo === '' || $tipo === 'compra') {
            try {
                $cxp = Cartera::cxpCompras($filters);
            } catch (Throwable $e) {
                $errors[] = $this->formatException('CXP (Compras)', $e, $debug);
                $cxp = [];
            }
        }
        // === FIN BLOQUE CRÍTICO ===

        View::render('cartera/index', [
            'title' => 'Cartera',
            'tipo' => $tipo,
            'q' => $q,
            'tercero_id' => $terceroId,
            'desde' => $desde,
            'hasta' => $hasta,
            'estado_pago' => $estadoPago,
            'errors' => $errors,
            'cxc' => $cxc,
            'cxp' => $cxp,
        ]);
    }

    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
        $p = explode('-', $date);
        return checkdate((int)$p[1], (int)$p[2], (int)$p[0]);
    }

    private function isDebug(): bool
    {
        $q = (string)($_GET['debug'] ?? '');
        if ($q === '1' || strtolower($q) === 'true') {
            return true;
        }

        $env = (string)($_ENV['APP_DEBUG'] ?? (getenv('APP_DEBUG') ?: ''));
        $env = strtolower(trim($env));

        return in_array($env, ['1', 'true', 'yes', 'on'], true);
    }

    private function formatException(string $context, Throwable $e, bool $debug): string
    {
        $base = 'No se pudo cargar ' . $context . '.';

        if (!$debug) {
            return $base . ' Active debug (?debug=1) para ver el detalle.';
        }

        $loc = basename($e->getFile()) . ':' . $e->getLine();
        return $base . ' Detalle: ' . $e->getMessage() . ' (' . $loc . ')';
    }

    /**
     * Chequeo rápido de tablas para detectar de inmediato faltantes típicos que causan PDOException.
     */
    private function checkRequiredTables(array &$errors, bool $debug): void
    {
        try {
            $pdo = Database::pdo();
            $required = ['facturas', 'compras', 'terceros', 'pagos'];

            $st = $pdo->prepare("
                SELECT 1
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name = :t
                LIMIT 1
            ");

            foreach ($required as $t) {
                $st->execute([':t' => $t]);
                $ok = $st->fetchColumn();
                if (!$ok) {
                    $errors[] = 'Falta la tabla requerida: ' . $t . '.';
                }
            }
        } catch (Throwable $e) {
            // Si no hay permisos para information_schema, no rompemos la vista.
            if ($debug) {
                $errors[] = 'Debug(check tablas): ' . $e->getMessage();
            }
        }
    }
}