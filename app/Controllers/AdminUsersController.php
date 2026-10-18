<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ValidationException;
use App\Services\PasswordPolicy;
use App\Services\UserService;

/**
 * CAL-03: controlador del panel para la gestión de usuarios.
 */
final class AdminUsersController extends AdminControllerBase
{
    private UserService $userService;

    public function __construct()
    {
        parent::__construct();
        $this->userService = new UserService();
    }

    public function saveUser(): void
    {
        $this->checkSuperAdminPermissions();

        $id       = (int) ($_POST['id'] ?? 0);
        $email    = trim((string) ($_POST['email'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $roleId   = (int) ($_POST['role_id'] ?? 4);
        $active   = !empty($_POST['active']);

        if ($email === '' || $username === '') {
            $_SESSION['_flash_error'] = 'El correo electrónico y nombre de usuario son obligatorios.';
            header('Location: /admin?tab=usuarios' . ($id > 0 ? '&edit_id=' . $id : ''));
            exit;
        }

        if ($id <= 0) {
            $policyErrors = PasswordPolicy::errors($password);
            if ($policyErrors) {
                $_SESSION['_flash_error'] = implode(' ', $policyErrors);
                header('Location: /admin?tab=usuarios');
                exit;
            }
        }

        $data = [
            'email'    => $email,
            'username' => $username,
            'role_id'  => $roleId,
            'active'   => $active,
        ];

        if ($password !== '') {
            $policyErrors = PasswordPolicy::errors($password);
            if ($policyErrors) {
                $_SESSION['_flash_error'] = implode(' ', $policyErrors);
                header('Location: /admin?tab=usuarios' . ($id > 0 ? '&edit_id=' . $id : ''));
                exit;
            }
            $data['password'] = $password;
        }

        try {
            if ($id > 0) {
                $this->userService->update($id, $data);
                $_SESSION['_flash_success'] = 'Usuario actualizado exitosamente.';
            } else {
                $this->userService->create($data);
                $_SESSION['_flash_success'] = 'Usuario creado exitosamente.';
            }
        } catch (ValidationException $e) {
            $_SESSION['_flash_error'] = implode(' ', $e->getErrors());
        } catch (\InvalidArgumentException) {
            $_SESSION['_flash_error'] = 'No se pudo guardar el usuario. Verifica los datos ingresados.';
        } catch (\Throwable) {
            $_SESSION['_flash_error'] = 'No se pudo guardar el usuario. Inténtalo nuevamente.';
        }

        header('Location: /admin?tab=usuarios');
        exit;
    }

    public function deleteUser(): void
    {
        $this->checkSuperAdminPermissions();

        $id = (int) ($_POST['id'] ?? 0);
        $currentUser = authUser();

        if ($id <= 0) {
            $_SESSION['_flash_error'] = 'ID de usuario inválido.';
            header('Location: /admin?tab=usuarios');
            exit;
        }

        if ($id === (int) ($currentUser['id'] ?? 0)) {
            $_SESSION['_flash_error'] = 'No puedes eliminar tu propia cuenta en sesión.';
            header('Location: /admin?tab=usuarios');
            exit;
        }

        try {
            $this->userService->delete($id);
            $_SESSION['_flash_success'] = 'Usuario eliminado exitosamente.';
        } catch (\PDOException $e) {
            // PDOException hereda de RuntimeException: debe ir antes para no exponer el SQL.
            error_log('[AdminUsersController::deleteUser] ' . $e->getMessage());
            $_SESSION['_flash_error'] = 'No se pudo eliminar el usuario. Inténtalo nuevamente o contacta al administrador del sitio.';
        } catch (\RuntimeException) {
            // UserService::delete() lanza RuntimeException si el usuario no existe (#93: sin getMessage()).
            $_SESSION['_flash_error'] = 'El usuario no existe o ya fue eliminado.';
        } catch (\Throwable $e) {
            error_log('[AdminUsersController::deleteUser] ' . $e->getMessage());
            $_SESSION['_flash_error'] = 'No se pudo eliminar el usuario. Inténtalo nuevamente o contacta al administrador del sitio.';
        }

        header('Location: /admin?tab=usuarios');
        exit;
    }

    public function updateUserRole(): void
    {
        $this->checkSuperAdminPermissions();

        $userId = (int) ($_POST['user_id'] ?? 0);
        $roleKey = strtolower(trim((string) ($_POST['role_key'] ?? '')));

        if ($userId <= 0 || $roleKey === '') {
            $_SESSION['_flash_error'] = 'Parámetros inválidos para actualización de rol.';
            header('Location: /admin?tab=usuarios');
            exit;
        }

        try {
            $roles = $this->userService->getRoles();
            $roleId = null;

            foreach ($roles as $r) {
                $rName = strtolower((string) ($r['name'] ?? ''));
                if ($roleKey === 'admin' && ($rName === 'admin' || $rName === 'superadmin')) {
                    $roleId = (int) $r['id'];
                    break;
                }
                if ($roleKey === $rName) {
                    $roleId = (int) $r['id'];
                    break;
                }
            }

            if ($roleId === null) {
                $roleId = 4;
            }

            $this->userService->update($userId, ['role_id' => $roleId]);
            $_SESSION['_flash_success'] = 'Rol de usuario actualizado exitosamente.';
        } catch (\Throwable) {
            $_SESSION['_flash_error'] = 'No se pudo actualizar el rol del usuario. Inténtalo nuevamente.';
        }

        header('Location: /admin?tab=usuarios');
        exit;
    }
}
