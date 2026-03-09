<?php
declare(strict_types=1);

namespace Erp2\Controller;

use Erp2\Core\Auth;
use Erp2\Core\Csrf;
use Erp2\Core\Database;
use Erp2\Core\Flash;
use Erp2\Core\View;
use Throwable;

/**
 * Seguridad (RBAC) - Permisos (admin-only + CRUD)
 */
final class PermisosController
{
    // =========================
    // LISTADO (admin-only)
    // =========================
    public function index(): void
    {
        Auth::requireLogin();
        $this->adminOnlyOr403();

        $q = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($q) > 120) {
            $q = mb_substr($q, 0, 120);
        }

        $limit = (int)($_GET['limit'] ?? 200);
        if ($limit <= 0) $limit = 200;
        if ($limit > 500) $limit = 500;

        $items = [];

        try {
            $pdo = Database::pdo();

            if ($q === '') {
                $sql = "SELECT * FROM permisos ORDER BY codigo ASC LIMIT {$limit}";
                $st = $pdo->query($sql);
                $items = $st ? $st->fetchAll() : [];
            } else {
                $like = '%' . $q . '%';

                // Evitamos HY093 usando placeholders distintos.
                $sql = "
                    SELECT *
                    FROM permisos
                    WHERE codigo LIKE :q1
                       OR CAST(id AS CHAR) = :q2
                    ORDER BY codigo ASC
                    LIMIT {$limit}
                ";
                $st = $pdo->prepare($sql);
                $st->execute([
                    ':q1' => $like,
                    ':q2' => $q,
                ]);
                $items = $st->fetchAll();
            }
        } catch (Throwable $e) {
            error_log('[permisos.index] ' . $e->getMessage());
            Flash::set('error', 'No se pudo cargar el listado de permisos.');
            $items = [];
        }

        View::render('permisos/index', [
            'title' => 'Permisos',
            'q' => $q,
            'limit' => $limit,
            'items' => $items,
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
        ]);
    }

    // =========================
    // CREAR (admin-only)
    // =========================
    public function createForm(): void
    {
        Auth::requireLogin();
        $this->adminOnlyOr403();

        View::render('permisos/create', [
            'title' => 'Crear permiso',
            'csrf' => Csrf::token(),
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        $this->adminOnlyOr403();

        if (!Csrf::validate($_POST['csrf'] ?? null)) {
            Flash::set('error', 'CSRF inválido.');
            $this->redirect303('/permisos/crear');
        }

        $codigo = trim((string)($_POST['codigo'] ?? ''));

        $errors = $this->validateCodigo($codigo);

        if (empty($errors)) {
            try {
                $pdo = Database::pdo();
                $st = $pdo->prepare("SELECT 1 FROM permisos WHERE codigo = :c LIMIT 1");
                $st->execute([':c' => $codigo]);
                if ($st->fetchColumn()) {
                    $errors['codigo'] = 'Este código ya existe.';
                }
            } catch (Throwable $e) {
                error_log('[permisos.create.check] ' . $e->getMessage());
                Flash::set('error', 'No se pudo validar el permiso.');
                $this->redirect303('/permisos/crear');
            }
        }

        if (!empty($errors)) {
            Flash::setData('old', ['codigo' => $codigo]);
            Flash::setData('errors', $errors);
            Flash::set('error', 'Revisa los campos marcados.');
            $this->redirect303('/permisos/crear');
        }

        try {
            $pdo = Database::pdo();
            $st = $pdo->prepare("INSERT INTO permisos (codigo) VALUES (:c)");
            $st->execute([':c' => $codigo]);

            Flash::set('success', 'Permiso creado correctamente.');
            $this->redirect303('/permisos');
        } catch (Throwable $e) {
            error_log('[permisos.create] ' . $e->getMessage());
            Flash::setData('old', ['codigo' => $codigo]);
            Flash::set('error', 'No se pudo crear el permiso.');
            $this->redirect303('/permisos/crear');
        }
    }

    // =========================
    // EDITAR (admin-only)
    // Regla: bloquear si está asignado a roles
    // =========================
    public function editForm(int $id): void
    {
        Auth::requireLogin();
        $this->adminOnlyOr403();

        try {
            $pdo = Database::pdo();
            $st = $pdo->prepare("SELECT * FROM permisos WHERE id = :id LIMIT 1");
            $st->execute([':id' => $id]);
            $permiso = $st->fetch();

            if (!$permiso) {
                Flash::set('error', 'Permiso no encontrado.');
                $this->redirect303('/permisos');
            }

            if ($this->isAssignedToRoles($id)) {
                Flash::set('error', 'No se puede editar: el permiso está asignado a uno o más roles.');
                $this->redirect303('/permisos');
            }

            View::render('permisos/edit', [
                'title' => 'Editar permiso',
                'permiso' => $permiso,
                'csrf' => Csrf::token(),
                'error' => Flash::get('error'),
                'success' => Flash::get('success'),
            ]);
        } catch (Throwable $e) {
            error_log('[permisos.editForm] ' . $e->getMessage());
            Flash::set('error', 'No se pudo cargar el permiso.');
            $this->redirect303('/permisos');
        }
    }

    public function update(int $id): void
    {
        Auth::requireLogin();
        $this->adminOnlyOr403();

        if (!Csrf::validate($_POST['csrf'] ?? null)) {
            Flash::set('error', 'CSRF inválido.');
            $this->redirect303('/permisos/' . $id . '/editar');
        }

        // Bloqueo recomendado: no tocar permisos ya asignados para no romper RBAC
        if ($this->isAssignedToRoles($id)) {
            Flash::set('error', 'No se puede editar: el permiso está asignado a uno o más roles.');
            $this->redirect303('/permisos');
        }

        $codigo = trim((string)($_POST['codigo'] ?? ''));

        $errors = $this->validateCodigo($codigo);

        if (empty($errors)) {
            try {
                $pdo = Database::pdo();
                $st = $pdo->prepare("SELECT 1 FROM permisos WHERE codigo = :c AND id <> :id LIMIT 1");
                $st->execute([':c' => $codigo, ':id' => $id]);
                if ($st->fetchColumn()) {
                    $errors['codigo'] = 'Este código ya existe.';
                }
            } catch (Throwable $e) {
                error_log('[permisos.update.check] ' . $e->getMessage());
                Flash::set('error', 'No se pudo validar el permiso.');
                $this->redirect303('/permisos/' . $id . '/editar');
            }
        }

        if (!empty($errors)) {
            Flash::setData('old', ['codigo' => $codigo]);
            Flash::setData('errors', $errors);
            Flash::set('error', 'Revisa los campos marcados.');
            $this->redirect303('/permisos/' . $id . '/editar');
        }

        try {
            $pdo = Database::pdo();
            $st = $pdo->prepare("UPDATE permisos SET codigo = :c WHERE id = :id");
            $st->execute([':c' => $codigo, ':id' => $id]);

            Flash::set('success', 'Permiso actualizado.');
            $this->redirect303('/permisos');
        } catch (Throwable $e) {
            error_log('[permisos.update] ' . $e->getMessage());
            Flash::setData('old', ['codigo' => $codigo]);
            Flash::set('error', 'No se pudo actualizar el permiso.');
            $this->redirect303('/permisos/' . $id . '/editar');
        }
    }

    // =========================
    // ELIMINAR (admin-only)
    // Bloquear si está asignado a roles
    // =========================
    public function delete(int $id): void
    {
        Auth::requireLogin();
        $this->adminOnlyOr403();

        if (!Csrf::validate($_POST['csrf'] ?? null)) {
            Flash::set('error', 'CSRF inválido.');
            $this->redirect303('/permisos');
        }

        if ($this->isAssignedToRoles($id)) {
            Flash::set('error', 'No se puede eliminar: el permiso está asignado a uno o más roles.');
            $this->redirect303('/permisos');
        }

        try {
            $pdo = Database::pdo();
            $st = $pdo->prepare("DELETE FROM permisos WHERE id = :id");
            $st->execute([':id' => $id]);

            Flash::set('success', 'Permiso eliminado.');
            $this->redirect303('/permisos');
        } catch (Throwable $e) {
            error_log('[permisos.delete] ' . $e->getMessage());
            Flash::set('error', 'No se pudo eliminar el permiso.');
            $this->redirect303('/permisos');
        }
    }

    // =========================
    // Helpers
    // =========================
    private function adminOnlyOr403(): void
    {
        $u = Auth::user();
        if ((int)($u['id'] ?? 0) !== 1) {
            http_response_code(403);
            echo '403 Forbidden';
            exit;
        }
    }

    private function redirect303(string $to): void
    {
        header('Location: ' . $to, true, 303);
        exit;
    }

    /** @return array<string,string> */
    private function validateCodigo(string $codigo): array
    {
        $errors = [];

        if ($codigo === '') {
            $errors['codigo'] = 'Requerido.';
            return $errors;
        }

        if (mb_strlen($codigo) > 120) {
            $errors['codigo'] = 'Máximo 120 caracteres.';
            return $errors;
        }

        if (!preg_match('/^[a-z0-9_.-]+$/', $codigo)) {
            $errors['codigo'] = 'Formato inválido. Use solo a-z, 0-9, _ . - (sin espacios).';
        }

        return $errors;
    }

    private function isAssignedToRoles(int $permisoId): bool
    {
        try {
            $pdo = Database::pdo();
            $st = $pdo->prepare("SELECT 1 FROM rol_permisos WHERE permiso_id = :pid LIMIT 1");
            $st->execute([':pid' => $permisoId]);
            return (bool)$st->fetchColumn();
        } catch (Throwable $e) {
            error_log('[permisos.isAssignedToRoles] ' . $e->getMessage());
            // Por seguridad, si no podemos verificar, bloqueamos edición/eliminación
            return true;
        }
    }
}