<?php
declare(strict_types=1);

namespace Erp2\Controller;

use Erp2\Core\Auth;
use Erp2\Core\Csrf;
use Erp2\Core\Flash;
use Erp2\Core\View;
use Erp2\Model\Auditoria;
use Erp2\Model\Contacto;
use Erp2\Model\Tercero;

final class TercerosController
{
    private function userId(): int
    {
        $u = Auth::user();
        return (int) ($u['id'] ?? 0);
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::can('terceros.ver');

        $q = trim((string) ($_GET['q'] ?? ''));
        $items = Tercero::search($q);

        View::render('terceros/index', [
            'title' => 'Terceros',
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
        Auth::can('terceros.crear');

        View::render('terceros/form', [
            'title' => 'Crear tercero',
            'mode' => 'create',
            'action' => '/terceros/crear',
            'tercero' => [
                'tipo' => 'cliente',
                'nombre_comercial' => '',
                'identificacion' => '',
                'email' => '',
            ],
            'csrf' => Csrf::token(),
            'error' => Flash::get('error'),
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        Auth::can('terceros.crear');

        $token = is_string($_POST['_csrf'] ?? null) ? (string) $_POST['_csrf'] : null;
        if (!Csrf::validate($token)) {
            Flash::set('error', 'Solicitud inválida. Intenta nuevamente.');
            header('Location: /terceros/crear', true, 303);
            exit;
        }

        $data = $this->readTerceroInput();

        // Old input (para repoblar)
        $old = [
            'tipo' => (string)($data['tipo'] ?? ''),
            'nombre_comercial' => (string)($data['nombre_comercial'] ?? ''),
            'identificacion' => (string)($data['identificacion'] ?? ''),
            'email' => (string)($data['email'] ?? ''),
        ];

        // Errores por campo (mínimos, sin reescribir validateTercero())
        $errors = [];

        $tipo = trim((string)($data['tipo'] ?? ''));
        if (!in_array($tipo, ['cliente', 'proveedor', 'ambos'], true)) {
            $errors['tipo'] = 'Tipo inválido.';
        }

        $nombre = trim((string)($data['nombre_comercial'] ?? ''));
        if ($nombre === '') {
            $errors['nombre_comercial'] = 'El nombre comercial es obligatorio.';
        } elseif (mb_strlen($nombre) > 160) {
            $errors['nombre_comercial'] = 'Máximo 160 caracteres.';
        }

        $ident = trim((string)($data['identificacion'] ?? ''));
        if ($ident !== '' && mb_strlen($ident) > 30) {
            $errors['identificacion'] = 'Máximo 30 caracteres.';
        }

        $email = trim((string)($data['email'] ?? ''));
        if ($email !== '' && mb_strlen($email) > 190) {
            $errors['email'] = 'Máximo 190 caracteres.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido.';
        }

        // Validación existente (mantener reglas previas)
        $err = $this->validateTercero($data);
        if ($err !== null || !empty($errors)) {
            Flash::setData('old', $old);
            if (!empty($errors)) {
                Flash::setData('errors', $errors);
            }
            Flash::set('error', $err ?? 'Revisa los campos marcados e intenta nuevamente.');
            header('Location: /terceros/crear', true, 303);
            exit;
        }

        try {
            $id = Tercero::create($data);

            Auditoria::log($this->userId(), 'crear', 'terceros', $id, [
                'data' => $data,
            ]);

            Flash::set('success', 'Tercero creado correctamente.');
            header('Location: /terceros/' . $id, true, 303);
            exit;
        } catch (\Throwable $e) {
            error_log('[terceros.create] error: ' . $e->getMessage() . ' user=' . $this->userId());
            Flash::setData('old', $old);
            Flash::set('error', 'No se pudo crear el tercero.');
            header('Location: /terceros/crear', true, 303);
            exit;
        }
    }

    public function show(int $id): void
    {
        Auth::requireLogin();
        Auth::can('terceros.ver');

        $tercero = Tercero::find($id);
        if (!$tercero) {
            http_response_code(404);
            echo "404 Not Found";
            return;
        }

        $contactos = Contacto::listByTercero($id);

        View::render('terceros/show', [
            'title' => 'Detalle del tercero',
            'tercero' => $tercero,
            'contactos' => $contactos,
            'csrf' => Csrf::token(),
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
        ]);
    }

    public function editForm(int $id): void
    {
        Auth::requireLogin();
        Auth::can('terceros.editar');

        $tercero = Tercero::find($id);
        if (!$tercero) {
            http_response_code(404);
            echo "404 Not Found";
            return;
        }

        View::render('terceros/form', [
            'title' => 'Editar tercero',
            'mode' => 'edit',
            'action' => '/terceros/' . $id . '/editar',
            'tercero' => $tercero,
            'csrf' => Csrf::token(),
            'error' => Flash::get('error'),
        ]);
    }

    public function update(int $id): void
    {
        Auth::requireLogin();
        Auth::can('terceros.editar');

        $token = is_string($_POST['_csrf'] ?? null) ? (string) $_POST['_csrf'] : null;
        if (!Csrf::validate($token)) {
            Flash::set('error', 'Solicitud inválida. Intenta nuevamente.');
            header('Location: /terceros/' . $id . '/editar', true, 303);
            exit;
        }

        $exists = Tercero::find($id);
        if (!$exists) {
            http_response_code(404);
            echo "404 Not Found";
            return;
        }

        $data = $this->readTerceroInput();
        $err = $this->validateTercero($data);
        if ($err !== null) {
            Flash::set('error', $err);
            header('Location: /terceros/' . $id . '/editar', true, 303);
            exit;
        }

        Tercero::update($id, $data);

        Auditoria::log($this->userId(), 'editar', 'terceros', $id, [
            'data' => $data,
        ]);

        Flash::set('success', 'Tercero actualizado correctamente.');
        header('Location: /terceros/' . $id, true, 303);
        exit;
    }

    public function delete(int $id): void
    {
        Auth::requireLogin();
        Auth::can('terceros.eliminar');

        $token = is_string($_POST['_csrf'] ?? null) ? (string) $_POST['_csrf'] : null;
        if (!Csrf::validate($token)) {
            Flash::set('error', 'Solicitud inválida. Intenta nuevamente.');
            header('Location: /terceros/' . $id, true, 303);
            exit;
        }

        $exists = Tercero::find($id);
        if (!$exists) {
            http_response_code(404);
            echo "404 Not Found";
            return;
        }

        Tercero::softDelete($id);

        Auditoria::log($this->userId(), 'eliminar', 'terceros', $id, [
            'soft_delete' => true,
        ]);

        Flash::set('success', 'Tercero eliminado (estado=0).');
        header('Location: /terceros', true, 303);
        exit;
    }

    public function createContacto(int $id): void
    {
        \Erp2\Core\Auth::requireLogin();
        \Erp2\Core\Auth::can('terceros.editar');

        $nombres  = trim((string)($_POST['nombres'] ?? ($_POST['nombre'] ?? '')));
        $email    = trim((string)($_POST['email'] ?? ''));
        $telefono = trim((string)($_POST['telefono'] ?? ''));
        $cargo    = trim((string)($_POST['cargo'] ?? ''));
        $notas    = trim((string)($_POST['notas'] ?? ''));

        $old = [
            'nombres' => $nombres,
            'nombre' => $nombres, // alias retro
            'email' => $email,
            'telefono' => $telefono,
            'cargo' => $cargo,
            'notas' => $notas,
        ];

        if (!\Erp2\Core\Csrf::validate($_POST['_csrf'] ?? null)) {
            \Erp2\Core\Flash::setData('old', $old);
            \Erp2\Core\Flash::set('error', 'Solicitud inválida (CSRF).');
            header('Location: /terceros/' . $id, true, 303);
            exit;
        }

        $errors = [];
        if ($nombres === '' || mb_strlen($nombres) > 160) {
            $errors['nombres'] = 'El nombre del contacto es obligatorio (máx. 160).';
            $errors['nombre'] = $errors['nombres']; // fallback
        }
        if ($email !== '' && mb_strlen($email) > 190) {
            $errors['email'] = 'Email demasiado largo (máx. 190).';
        }
        if ($telefono !== '' && mb_strlen($telefono) > 30) {
            $errors['telefono'] = 'Teléfono demasiado largo (máx. 30).';
        }
        if ($cargo !== '' && mb_strlen($cargo) > 80) {
            $errors['cargo'] = 'Cargo demasiado largo (máx. 80).';
        }
        if ($notas !== '' && mb_strlen($notas) > 255) {
            $errors['notas'] = 'Notas demasiado largas (máx. 255).';
        }

        if (!empty($errors)) {
            \Erp2\Core\Flash::setData('old', $old);
            \Erp2\Core\Flash::setData('errors', $errors);
            \Erp2\Core\Flash::set('error', 'Revisa los campos marcados e intenta nuevamente.');
            header('Location: /terceros/' . $id, true, 303);
            exit;
        }

        $pdo = \Erp2\Core\Database::pdo();

        try {
            $stT = $pdo->prepare("SELECT id FROM terceros WHERE id = :id LIMIT 1");
            $stT->execute([':id' => $id]);
            $ter = $stT->fetch();
            if (!$ter) {
                \Erp2\Core\Flash::set('error', 'El tercero no existe.');
                header('Location: /terceros', true, 303);
                exit;
            }

            $st = $pdo->prepare("
                INSERT INTO contactos (tercero_id, nombres, email, telefono, cargo, notas, created_at)
                VALUES (:tercero_id, :nombres, :email, :telefono, :cargo, :notas, NOW())
            ");
            $st->execute([
                ':tercero_id' => $id,
                ':nombres' => $nombres,
                ':email' => ($email !== '' ? $email : null),
                ':telefono' => ($telefono !== '' ? $telefono : null),
                ':cargo' => ($cargo !== '' ? $cargo : null),
                ':notas' => ($notas !== '' ? $notas : null),
            ]);

            $contactoId = (int)$pdo->lastInsertId();

            \Erp2\Model\Auditoria::log(
                (int)(\Erp2\Core\Auth::user()['id'] ?? 0),
                'crear',
                'contactos',
                $contactoId,
                ['tercero_id' => $id]
            );

            \Erp2\Core\Flash::set('success', 'Contacto agregado.');
            header('Location: /terceros/' . $id, true, 303);
            exit;

        } catch (\Throwable $e) {
            error_log('[terceros.contactos.create] error: ' . $e->getMessage() . ' tercero_id=' . $id);
            \Erp2\Core\Flash::setData('old', $old);
            \Erp2\Core\Flash::set('error', 'Error al crear contacto.');
            header('Location: /terceros/' . $id, true, 303);
            exit;
        }
    }

    public function deleteContacto(int $id, int $cid): void
    {
        Auth::requireLogin();
        Auth::can('terceros.editar');

        $token = is_string($_POST['_csrf'] ?? null) ? (string) $_POST['_csrf'] : null;
        if (!Csrf::validate($token)) {
            Flash::set('error', 'Solicitud inválida. Intenta nuevamente.');
            header('Location: /terceros/' . $id, true, 303);
            exit;
        }

        $tercero = Tercero::find($id);
        if (!$tercero) {
            http_response_code(404);
            echo "404 Not Found";
            return;
        }

        $ok = Contacto::delete($cid, $id);

        Auditoria::log($this->userId(), 'eliminar', 'contactos', $cid, [
            'tercero_id' => $id,
            'deleted' => $ok,
        ]);

        Flash::set('success', 'Contacto eliminado.');
        header('Location: /terceros/' . $id, true, 303);
        exit;
    }

    /** @return array{tipo:string,nombre_comercial:string,identificacion:string,email:string} */
    private function readTerceroInput(): array
    {
        return [
            'tipo' => trim((string) ($_POST['tipo'] ?? '')),
            'nombre_comercial' => trim((string) ($_POST['nombre_comercial'] ?? '')),
            'identificacion' => trim((string) ($_POST['identificacion'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
        ];
    }

    private function validateTercero(array $data): ?string
    {
        $tipo = $data['tipo'] ?? '';
        $nombre = $data['nombre_comercial'] ?? '';
        $ident = $data['identificacion'] ?? '';
        $email = $data['email'] ?? '';

        $allowed = ['cliente', 'proveedor', 'ambos'];
        if (!in_array($tipo, $allowed, true)) {
            return 'Tipo inválido. Use: cliente, proveedor o ambos.';
        }

        $len = mb_strlen($nombre);
        if ($nombre === '' || $len < 1 || $len > 160) {
            return 'Nombre comercial es obligatorio (1..160).';
        }

        if (mb_strlen($ident) > 30) {
            return 'Identificación excede 30 caracteres.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Email no es válido.';
        }

        return null;
    }
}