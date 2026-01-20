<?php
declare(strict_types=1);

namespace Erp2\Controller;

use Erp2\Core\Auth;
use Erp2\Core\Csrf;
use Erp2\Core\Flash;
use Erp2\Core\View;
use Erp2\Model\Auditoria;
use Erp2\Model\Producto;
use PDOException;

final class ProductosController
{
    private function userId(): int
    {
        $u = Auth::user();
        return (int)($u['id'] ?? 0);
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::can('productos.ver');

        $q = trim((string)($_GET['q'] ?? ''));
        $items = Producto::search($q);

        View::render('productos/index', [
            'title' => 'Productos/Servicios',
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
        Auth::can('productos.crear');

        View::render('productos/form', [
            'title' => 'Crear producto/servicio',
            'mode' => 'create',
            'action' => '/productos/crear',
            'producto' => [
                'tipo' => 'producto',
                'referencia' => '',
                'nombre' => '',
                'descripcion' => '',
                'precio_venta' => '0',
                'costo' => '',
            ],
            'csrf' => Csrf::token(),
            'error' => Flash::get('error'),
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        Auth::can('productos.crear');

        $token = is_string($_POST['_csrf'] ?? null) ? (string)$_POST['_csrf'] : null;
        if (!Csrf::validate($token)) {
            Flash::set('error', 'Solicitud inválida. Intenta nuevamente.');
            header('Location: /productos/crear', true, 303);
            exit;
        }

        $data = $this->readInput();

        $old = [
            'tipo' => (string)($data['tipo'] ?? ''),
            'referencia' => (string)($data['referencia'] ?? ''),
            'nombre' => (string)($data['nombre'] ?? ''),
            'descripcion' => (string)($data['descripcion'] ?? ''),
            'precio_venta' => (string)($data['precio_venta'] ?? ''),
            'costo' => (string)($data['costo'] ?? ''),
        ];

        $errors = [];

        $tipo = trim((string)($data['tipo'] ?? ''));
        if (!in_array($tipo, ['producto', 'servicio'], true)) {
            $errors['tipo'] = 'Tipo inválido.';
        }

        $ref = trim((string)($data['referencia'] ?? ''));
        if ($ref === '') $errors['referencia'] = 'La referencia es obligatoria.';
        elseif (mb_strlen($ref) > 64) $errors['referencia'] = 'Máximo 64 caracteres.';

        $nombre = trim((string)($data['nombre'] ?? ''));
        if ($nombre === '') $errors['nombre'] = 'El nombre es obligatorio.';
        elseif (mb_strlen($nombre) > 160) $errors['nombre'] = 'Máximo 160 caracteres.';

        $pv = trim((string)($data['precio_venta'] ?? ''));
        if ($pv === '' || !is_numeric(str_replace(',', '.', $pv)) || (float)str_replace(',', '.', $pv) <= 0) {
            $errors['precio_venta'] = 'El precio de venta debe ser mayor a 0.';
        }

        $costo = trim((string)($data['costo'] ?? ''));
        if ($costo !== '' && (!is_numeric(str_replace(',', '.', $costo)) || (float)str_replace(',', '.', $costo) < 0)) {
            $errors['costo'] = 'El costo debe ser mayor o igual a 0.';
        }

        $err = $this->validate($data);
        if ($err !== null || !empty($errors)) {
            Flash::setData('old', $old);
            if (!empty($errors)) Flash::setData('errors', $errors);
            Flash::set('error', $err ?? 'Revisa los campos marcados e intenta nuevamente.');
            header('Location: /productos/crear', true, 303);
            exit;
        }

        try {
            $id = Producto::create($data);

            Auditoria::log($this->userId(), 'crear', 'productos', $id, [
                'data' => $data,
            ]);

            Flash::set('success', 'Producto/servicio creado correctamente.');
            header('Location: /productos/' . $id, true, 303);
            exit;

        } catch (PDOException $e) {
            Flash::setData('old', $old);

            if ($this->isDuplicateKey($e)) {
                Flash::setData('errors', ['referencia' => 'La referencia ya existe. Usa una referencia única.']);
                Flash::set('error', 'La referencia ya existe. Usa una referencia única.');
                header('Location: /productos/crear', true, 303);
                exit;
            }

            error_log('[productos.create] error: ' . $e->getMessage() . ' user=' . $this->userId());    
            Flash::set('error', 'No se pudo crear el registro.');
            header('Location: /productos/crear', true, 303);
            exit;
        }
    }

    public function show(int $id): void
    {
        Auth::requireLogin();
        Auth::can('productos.ver');

        $producto = Producto::find($id);
        if (!$producto) {
            http_response_code(404);
            echo "404 Not Found";
            return;
        }

        View::render('productos/show', [
            'title' => 'Detalle producto/servicio',
            'producto' => $producto,
            'csrf' => Csrf::token(),
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
        ]);
    }

    public function editForm(int $id): void
    {
        Auth::requireLogin();
        Auth::can('productos.editar');

        $producto = Producto::find($id);
        if (!$producto) {
            http_response_code(404);
            echo "404 Not Found";
            return;
        }

        View::render('productos/form', [
            'title' => 'Editar producto/servicio',
            'mode' => 'edit',
            'action' => '/productos/' . $id . '/editar',
            'producto' => $producto,
            'csrf' => Csrf::token(),
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
        ]);
    }

    public function update(int $id): void
    {
        Auth::requireLogin();
        Auth::can('productos.editar');

        $token = is_string($_POST['_csrf'] ?? null) ? (string)$_POST['_csrf'] : null;
        if (!Csrf::validate($token)) {
            Flash::set('error', 'Solicitud inválida. Intenta nuevamente.');
            header('Location: /productos/' . $id . '/editar', true, 303);
            exit;
        }

        $exists = Producto::find($id);
        if (!$exists) {
            http_response_code(404);
            echo "404 Not Found";
            return;
        }

        $data = $this->readInput();

        $old = [
            'tipo' => (string)($data['tipo'] ?? ''),
            'referencia' => (string)($data['referencia'] ?? ''),
            'nombre' => (string)($data['nombre'] ?? ''),
            'descripcion' => (string)($data['descripcion'] ?? ''),
            'precio_venta' => (string)($data['precio_venta'] ?? ''),
            'costo' => (string)($data['costo'] ?? ''),
        ];

        $errors = [];

        $tipo = trim((string)($data['tipo'] ?? ''));
        if (!in_array($tipo, ['producto', 'servicio'], true)) {
            $errors['tipo'] = 'Tipo inválido.';
        }

        $ref = trim((string)($data['referencia'] ?? ''));
        if ($ref === '') $errors['referencia'] = 'La referencia es obligatoria.';
        elseif (mb_strlen($ref) > 64) $errors['referencia'] = 'Máximo 64 caracteres.';

        $nombre = trim((string)($data['nombre'] ?? ''));
        if ($nombre === '') $errors['nombre'] = 'El nombre es obligatorio.';
        elseif (mb_strlen($nombre) > 160) $errors['nombre'] = 'Máximo 160 caracteres.';

        $pv = trim((string)($data['precio_venta'] ?? ''));
        if ($pv === '' || !is_numeric(str_replace(',', '.', $pv)) || (float)str_replace(',', '.', $pv) <= 0) {
            $errors['precio_venta'] = 'El precio de venta debe ser mayor a 0.';
        }

        $costo = trim((string)($data['costo'] ?? ''));
        if ($costo !== '' && (!is_numeric(str_replace(',', '.', $costo)) || (float)str_replace(',', '.', $costo) < 0)) {
            $errors['costo'] = 'El costo debe ser mayor o igual a 0.';
        }

        $err = $this->validate($data);
        if ($err !== null || !empty($errors)) {
            Flash::setData('old', $old);
            if (!empty($errors)) Flash::setData('errors', $errors);
            Flash::set('error', $err ?? 'Revisa los campos marcados e intenta nuevamente.');
            header('Location: /productos/' . $id . '/editar', true, 303);
            exit;
        }

        try {
            Producto::update($id, $data);

            Auditoria::log($this->userId(), 'editar', 'productos', $id, [
                'data' => $data,
            ]);

            Flash::set('success', 'Producto/servicio actualizado correctamente.');
            header('Location: /productos/' . $id, true, 303);
            exit;

        } catch (PDOException $e) {
            Flash::setData('old', $old);

            if ($this->isDuplicateKey($e)) {
                Flash::setData('errors', ['referencia' => 'La referencia ya existe. Usa una referencia única.']);
                Flash::set('error', 'La referencia ya existe. Usa una referencia única.');
                header('Location: /productos/' . $id . '/editar', true, 303);
                exit;
            }

            error_log('[productos.update] error: ' . $e->getMessage() . ' user=' . $this->userId());
            Flash::set('error', 'No se pudo actualizar el registro.');
            header('Location: /productos/' . $id . '/editar', true, 303);
            exit;
        }
    }

    public function delete(int $id): void
    {
        Auth::requireLogin();
        Auth::can('productos.eliminar');

        $token = is_string($_POST['_csrf'] ?? null) ? (string)$_POST['_csrf'] : null;
        if (!Csrf::validate($token)) {
            Flash::set('error', 'Solicitud inválida. Intenta nuevamente.');
            header('Location: /productos/' . $id, true, 303);
            exit;
        }

        $exists = Producto::find($id);
        if (!$exists) {
            http_response_code(404);
            echo "404 Not Found";
            return;
        }

        Producto::softDelete($id);

        Auditoria::log($this->userId(), 'eliminar', 'productos', $id, [
            'soft_delete' => true,
        ]);

        Flash::set('success', 'Producto/servicio eliminado (estado=0).');
        header('Location: /productos', true, 303);
        exit;
    }

    /** @return array{tipo:string,referencia:string,nombre:string,descripcion:string,precio_venta:string,costo: (string|null)} */
    private function readInput(): array
    {
        $tipo = trim((string)($_POST['tipo'] ?? ''));
        $referencia = trim((string)($_POST['referencia'] ?? ''));
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));

        $precio = $this->normalizeDecimal((string)($_POST['precio_venta'] ?? '0'), true);
        $costoRaw = trim((string)($_POST['costo'] ?? ''));
        $costo = $costoRaw === '' ? null : $this->normalizeDecimal($costoRaw, false);

        return [
            'tipo' => $tipo,
            'referencia' => $referencia,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'precio_venta' => $precio,
            'costo' => $costo,
        ];
    }

    private function validate(array $data): ?string
    {
        $tipo = (string)($data['tipo'] ?? '');
        if (!in_array($tipo, ['producto', 'servicio'], true)) {
            return 'Tipo inválido. Use: producto o servicio.';
        }

        $ref = (string)($data['referencia'] ?? '');
        $refLen = mb_strlen($ref);
        if ($refLen < 1 || $refLen > 64) {
            return 'Referencia es obligatoria (1..64).';
        }

        $nombre = (string)($data['nombre'] ?? '');
        $nameLen = mb_strlen($nombre);
        if ($nameLen < 1 || $nameLen > 160) {
            return 'Nombre es obligatorio (1..160).';
        }

        $precio = (string)($data['precio_venta'] ?? '0');
        if (!$this->isNonNegativeDecimal($precio)) {
            return 'Precio de venta inválido (decimal >= 0).';
        }

        $costo = $data['costo'] ?? null;
        if ($costo !== null && (!is_string($costo) || !$this->isNonNegativeDecimal($costo))) {
            return 'Costo inválido (decimal >= 0).';
        }

        return null;
    }

    private function normalizeDecimal(string $value, bool $required): string
    {
        $value = trim($value);
        if ($value === '') {
            return $required ? '0' : '0';
        }

        // Normalizar coma a punto
        $value = str_replace(',', '.', $value);

        // Validar formato decimal simple: 123 o 123.45
        if (!preg_match('/^\d+(\.\d+)?$/', $value)) {
            return '0';
        }

        return $value;
    }

    private function isNonNegativeDecimal(string $value): bool
    {
        if (!preg_match('/^\d+(\.\d+)?$/', $value)) {
            return false;
        }

        // Comparación segura para >= 0 (value ya es positivo por regex)
        return true;
    }

    private function isDuplicateKey(PDOException $e): bool
    {
        // MySQL: SQLSTATE 23000, driver error 1062
        $sqlState = $e->getCode();
        if ($sqlState !== '23000') {
            return false;
        }

        $info = $e->errorInfo ?? null;
        if (is_array($info) && isset($info[1]) && (int)$info[1] === 1062) {
            return true;
        }

        return false;
    }
}