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
        $err = $this->validateTercero($data);
        if ($err !== null) {
            Flash::set('error', $err);
            header('Location: /terceros/crear', true, 303);
            exit;
        }

        $id = Tercero::create($data);

        Auditoria::log($this->userId(), 'crear', 'terceros', $id, [
            'data' => $data,
        ]);

        Flash::set('success', 'Tercero creado correctamente.');
        header('Location: /terceros/' . $id, true, 303);
        exit;
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
        // Ajusta el permiso al que uses para editar terceros:
        \Erp2\Core\Auth::can('terceros.editar');

        if (!\Erp2\Core\Csrf::validate($_POST['_csrf'] ?? null)) {
            \Erp2\Core\Flash::set('error', 'Solicitud inválida (CSRF).');
            header('Location: /terceros/' . $id, true, 303);
            exit;
        }

        $nombres  = trim((string)($_POST['nombres'] ?? ($_POST['nombre'] ?? ''))); // soporta ambos
        $email    = trim((string)($_POST['email'] ?? ''));
        $telefono = trim((string)($_POST['telefono'] ?? ''));
        $cargo    = trim((string)($_POST['cargo'] ?? ''));
        $notas    = trim((string)($_POST['notas'] ?? ''));

        if ($nombres === '' || mb_strlen($nombres) > 160) {
            \Erp2\Core\Flash::set('error', 'El nombre del contacto es obligatorio (máx. 160).');
            header('Location: /terceros/' . $id, true, 303);
            exit;
        }
        if ($email !== '' && mb_strlen($email) > 190) {
            \Erp2\Core\Flash::set('error', 'Email demasiado largo (máx. 190).');
            header('Location: /terceros/' . $id, true, 303);
            exit;
        }

        $pdo = \Erp2\Core\Database::pdo();

        try {
            // Validar tercero existe
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
            \Erp2\Core\Flash::set('error', 'Error al crear contacto: ' . $e->getMessage());
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