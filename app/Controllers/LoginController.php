<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\SiteRepository;
use App\Services\UserService;

/**
 * Página pública de login (formulario HTML, sesión PHP).
 *
 * Es la contraparte "no-SPA" de AuthController::login(): el mockup en
 * TypeScript (lovable-project/src/routes/login.tsx) resuelve el login
 * contra Supabase Auth desde el cliente; aquí se resuelve en el servidor
 * con el mismo UserService que usa /api/auth/login, reutilizando la
 * sesión PHP y el CSRF ya definidos en helpers.php.
 *
 * El toggle "Crear cuenta" del mockup no tiene equivalente: el backend
 * no expone un registro público (POST /api/users requiere sesión de
 * admin), así que esta página solo cubre el login.
 */
final class LoginController extends Controller
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function show(): void
    {
        $site = new SiteRepository();

        $this->render('login', [
            'pageTitle'       => 'Iniciar sesión — SFL ULS Lab',
            'metaDescription' => 'Acceso al panel interno del Software Factory Lab de la Universidad de La Serena.',
            'csrfToken'       => csrfToken(),
            'errors'          => $_SESSION['_login_errors'] ?? [],
            'email'           => $_SESSION['_login_email'] ?? '',
            'enlacesFooter'   => $site->enlacesFooter(),
            'contacto'        => $site->contactoInfo(),
        ]);

        unset($_SESSION['_login_errors'], $_SESSION['_login_email']);
    }

    public function submit(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $errors = $this->validate($email, $password);
        $user = null;

        if (!$errors) {
            $user = $this->userService->getByEmail($email);

            if (!$user || !password_verify($password, $user['password'])) {
                $errors['general'] = 'Correo o contraseña incorrectos.';
            } elseif (!$user['active']) {
                $errors['general'] = 'La cuenta está desactivada.';
            }
        }

        if ($errors) {
            $_SESSION['_login_errors'] = $errors;
            $_SESSION['_login_email'] = $email;
            header('Location: /login');
            exit;
        }

        authLogin(
            (int) $user['id'],
            $user['username'],
            (int) $user['role_id'],
            $user['role_name'],
            (bool) $user['must_change_password']
        );

        header('Location: /admin');
        exit;
    }

    private function validate(string $email, string $password): array
    {
        $errors = [];
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ingresa un correo válido.';
        }
        if (strlen($password) < 6) {
            $errors['password'] = 'La contraseña debe tener al menos 6 caracteres.';
        }
        return $errors;
    }
}
