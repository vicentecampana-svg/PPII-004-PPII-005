<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\FooterService;
use App\Services\PasswordPolicy;
use App\Services\UserService;

/**
 * Controlador para cambio de contraseña de usuarios.
 */
final class PasswordController extends Controller
{
    private UserService $userService;
    private UserRepository $userRepo;
    private AuditService $audit;

    public function __construct()
    {
        $this->userService = new UserService();
        $this->userRepo = new UserRepository();
        $this->audit = new AuditService();
    }

    public function show(): void
    {
        $footer = (new FooterService())->getAll();

        $this->render('cambiar-password', [
            'pageTitle'       => 'Cambiar contraseña — SFL ULS Lab',
            'metaDescription' => 'Cambio de contraseña para usuarios de SFL ULS Lab.',
            'csrfToken'       => csrfToken(),
            'errors'          => $_SESSION['_password_errors'] ?? [],
            'user'            => authUser(),
            'forced'          => authMustChangePassword(),
            'enlacesFooter'   => $footer['links'],
            'contacto'        => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
        ]);

        unset($_SESSION['_password_errors']);
    }

    public function submit(): void
    {
        $userId = (int) (authUser()['id'] ?? 0);
        // findById() omite el hash a propósito; para verificar la contraseña
        // actual se necesita la variante que sí lo incluye.
        $user = $this->userRepo->findWithPasswordById($userId);

        if (!$user) {
            authLogout();
            header('Location: /login');
            exit;
        }

        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        $errors = [];

        if ($currentPassword === '') {
            $errors['current_password'] = 'Debes ingresar tu contraseña actual.';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $errors['current_password'] = 'La contraseña actual es incorrecta.';
        }

        $policyErrors = PasswordPolicy::errors($newPassword);
        if ($policyErrors) {
            $errors['new_password'] = implode(' ', $policyErrors);
        }

        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Las contraseñas no coinciden.';
        }

        if ($errors) {
            $_SESSION['_password_errors'] = $errors;
            header('Location: /cambiar-password');
            exit;
        }

        $this->userService->update($userId, [
            'password'             => $newPassword,
            'must_change_password' => false,
        ]);

        $_SESSION['must_change_password'] = false;
        $this->audit->log($userId, 'cambiar_password', 'user', $userId, 'Contraseña cambiada por el usuario');

        // Se redirige al panel, que solo muestra los mensajes de _flash_success.
        $_SESSION['_flash_success'] = 'Contraseña actualizada correctamente.';
        header('Location: /admin');
        exit;
    }
}
