<?php
declare(strict_types=1);

namespace Erp2\Controller;

use Erp2\Core\Auth;
use Erp2\Core\Csrf;
use Erp2\Core\Flash;
use Erp2\Core\View;

final class AuthController
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            header('Location: /', true, 302);
            exit;
        }

        $csrf = Csrf::token();
        $error = Flash::get('error');

        View::render('auth/login', [
            'title' => 'Iniciar sesión',
            'csrf' => $csrf,
            'error' => $error,
        ]);
    }

    public function login(): void
    {
        $csrfToken = $_POST['_csrf'] ?? null;
        $csrfToken = is_string($csrfToken) ? $csrfToken : null;

        if (!Csrf::validate($csrfToken)) {
            Flash::set('error', 'Solicitud inválida. Intenta nuevamente.');
            header('Location: /login', true, 303);
            exit;
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (!Auth::loginByEmailPassword($email, $password)) {
            Flash::set('error', 'Credenciales incorrectas.');
            header('Location: /login', true, 303);
            exit;
        }

        // Mitigar session fixation
        session_regenerate_id(true);

        header('Location: /', true, 303);
        exit;
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /login', true, 303);
        exit;
    }
}