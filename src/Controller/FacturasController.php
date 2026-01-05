<?php
declare(strict_types=1);

namespace Erp2\Controller;

use Erp2\Core\Auth;
use Erp2\Core\Csrf;
use Erp2\Core\Flash;
use Erp2\Core\View;
use Erp2\Model\Auditoria;
use Erp2\Model\Factura;
use Erp2\Model\FacturaDetalle;
use Erp2\Model\Producto;
use Erp2\Model\Tercero;
use PDOException;

final class FacturasController
{
    private function userId(): int
    {
        $u = Auth::user();
        return (int)($u['id'] ?? 0);
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::can('facturas.ver');

        $q = trim((string)($_GET['q'] ?? ''));
        $items = Factura::search($q);

        View::render('facturas/index', [
            'title' =>'Facturas',
            'q' => $q,
            'items' => $items,
            'csrf' => Csrf::token(),
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
        ]);
    }

    public function createForm(): void
    {
        Auth::requireLogin();
        Auth::can('facturas.crear');

        // Catálogos simples
        $terceros = Tercero::search('');
        $productos = Producto::search('');

        View::render('facturas/form', [
            'title' => 'Crear factura',
            'action' => '/facturas/crear',
            'csrf' => Csrf::token(),
            'error' => Flash::get('error'),
            'terceros' => $terceros,
            'productos' => $productos,
            'today' => date('Y-m-d'),
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        Auth::can('facturas.crear');

        $token = is_string($_POST['_csrf'] ?? null) ? (string)$_POST['_csrf'] : null;
        if (!Csrf::validate($token)) {
            Flash::set('error', 'Solicitud inválida. Intenta nuevamente.');
            header('Location: /facturas/crear', true, 303);
            exit;
        }

        $fecha = trim((string)($_POST['fecha'] ?? date('Y-m-d')));
        if (!$this->isValidDate($fecha)) {
            $fecha = date('Y-m-d');
        }

        $terceroId = (int)($_POST['tercero_id'] ?? 0);
        if ($terceroId <= 0 || !Tercero::find($terceroId)) {
            Flash::set('error', 'Tercero inválido o no existe.');
            header('Location: /facturas/crear', true, 303);
            exit;
        }

        $lines = $this->readLines();
        if (count($lines) < 1) {
            Flash::set('error', 'Debe ingresar al menos 1 línea válida.');
            header('Location: /facturas/crear', true, 303);
            exit;
        }

        // Calcular totales
        $subtotal = 0.0;
        foreach ($lines as &$ln) {
            $qty = (float)$ln['cantidad'];
            $price = (float)$ln['precio_unitario'];
            $lineSub = round($qty * $price, 2);
            $ln['subtotal_linea'] = $this->formatDecimal($lineSub);
            $subtotal += $lineSub;
        }
        unset($ln);

        $subtotal = round($subtotal, 2);
        $total = $subtotal;

        $header = [
            'fecha' => $fecha,
            'tercero_id' => $terceroId,
            // Sin flujo de emisión por ahora: dejamos borrador para futuro "emitir"
            'estado' => 'borrador',
            'subtotal' => $this->formatDecimal($subtotal),
            'total' => $this->formatDecimal($total),
        ];

        try {
            $facturaId = Factura::createWithDetails($header, $lines);

            Auditoria::log($this->userId(), 'crear', 'facturas', $facturaId, [
                'tercero_id' => $terceroId,
                'fecha' => $fecha,
                'lines' => count($lines),
                'subtotal' => $header['subtotal'],
                'total' => $header['total'],
            ]);

            Flash::set('success', 'Factura creada correctamente.');
            header('Location: /facturas/' . $facturaId, true, 303);
            exit;
        } catch (PDOException) {
            Flash::set('error', 'No se pudo crear la factura.');
            header('Location: /facturas/crear', true, 303);
            exit;
        }
    }

    public function show(int $id): void
    {
        Auth::requireLogin();
        Auth::can('facturas.ver');

        $factura = Factura::find($id);
        if (!$factura) {
            http_response_code(404);
            echo "404 Not Found";
            return;
        }

        $detalles = FacturaDetalle::listByFactura($id);

        View::render('facturas/show', [
            'title' => 'Detalle factura',
            'factura' => $factura,
            'detalles' => $detalles,
            'csrf' => Csrf::token(),
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
        ]);
    }

    public function anular(int $id): void
    {
        Auth::requireLogin();
        Auth::can('facturas.anular');

        $token = is_string($_POST['_csrf'] ?? null) ? (string)$_POST['_csrf'] : null;
        if (!Csrf::validate($token)) {
            Flash::set('error', 'Solicitud inválida. Intenta nuevamente.');
            header('Location: /facturas/' . $id, true, 303);
            exit;
        }

        $factura = Factura::find($id);
        if (!$factura) {
            http_response_code(404);
            echo "404 Not Found";
            return;
        }

        $ok = Factura::anular($id);

        Auditoria::log($this->userId(), 'anular', 'facturas', $id, [
            'changed' => $ok,
            'prev_estado' => (string)($factura['estado'] ?? ''),
        ]);

        Flash::set('success', $ok ? 'Factura anulada.' : 'La factura ya estaba anulada.');
        header('Location: /facturas/' . $id, true, 303);
        exit;
    }

    /**
     * Lee líneas desde arrays POST: producto_id[], descripcion[], cantidad[], precio_unitario[]
     * @return array<int, array{producto_id:?int,descripcion:string,cantidad:string,precio_unitario:string,subtotal_linea:string}>
     */
    private function readLines(): array
    {
        $pids = $_POST['line_producto_id'] ?? [];
        $descs = $_POST['line_descripcion'] ?? [];
        $qtys = $_POST['line_cantidad'] ?? [];
        $prices = $_POST['line_precio_unitario'] ?? [];

        if (!is_array($pids) || !is_array($descs) || !is_array($qtys) || !is_array($prices)) {
            return [];
        }

        $lines = [];
        $max = max(count($pids), count($descs), count($qtys), count($prices));
        $max = min($max, 20); // límite razonable

        for ($i = 0; $i < $max; $i++) {
            $pidRaw = $pids[$i] ?? '';
            $desc = trim((string)($descs[$i] ?? ''));
            $qtyRaw = trim((string)($qtys[$i] ?? ''));
            $priceRaw = trim((string)($prices[$i] ?? ''));

            $pid = (int)$pidRaw;
            $productoId = $pid > 0 ? $pid : null;

            // Normalizar decimales
            $qty = $this->normalizeDecimal($qtyRaw);
            $price = $this->normalizeDecimal($priceRaw);

            if ($qty === null || $price === null) {
                continue;
            }

            $qtyF = (float)$qty;
            $priceF = (float)$price;

            if ($qtyF <= 0) {
                continue;
            }
            if ($priceF < 0) {
                continue;
            }

            // Validar producto si se envía
            if ($productoId !== null) {
                $prod = Producto::find($productoId);
                if (!$prod) {
                    continue; // línea inválida si apunta a producto inexistente
                }
                if ($desc === '') {
                    $desc = (string)($prod['nombre'] ?? '');
                }
            }

            if ($desc === '' || mb_strlen($desc) > 255) {
                continue;
            }

            $lines[] = [
                'producto_id' => $productoId,
                'descripcion' => $desc,
                'cantidad' => $qty,
                'precio_unitario' => $price,
                'subtotal_linea' => '0.00', // se calcula luego
            ];
        }

        return $lines;
    }

    private function normalizeDecimal(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $value = str_replace(',', '.', $value);
        if (!preg_match('/^\d+(\.\d+)?$/', $value)) {
            return null;
        }

        return $value;
    }

    private function formatDecimal(float $value): string
    {
        // Mantener punto decimal, 2 decimales
        return number_format($value, 2, '.', '');
    }

    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        $parts = explode('-', $date);
        return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
    }
}