<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ValidationException;
use App\Services\CreditMemberService;

/**
 * CAL-03: controlador del panel para los integrantes de Créditos.
 */
final class AdminCreditsController extends AdminControllerBase
{
    private CreditMemberService $creditMemberService;

    public function __construct()
    {
        parent::__construct();
        $this->creditMemberService = new CreditMemberService();
    }

    public function saveCreditMember(): void
    {
        $this->checkSuperAdminPermissions();

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $role = trim((string) ($_POST['role'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $orden = isset($_POST['orden']) && is_numeric($_POST['orden']) ? (int) $_POST['orden'] : null;

        $data = [
            'name'  => $name,
            'role'  => $role,
            'email' => $email,
        ];
        if ($orden !== null) {
            $data['orden'] = max(0, $orden);
        }

        try {
            if ($id > 0) {
                $this->creditMemberService->update($id, $data);
                $_SESSION['_flash_success'] = 'Integrante de créditos actualizado exitosamente.';
            } else {
                $this->creditMemberService->create($data);
                $_SESSION['_flash_success'] = 'Integrante de créditos creado exitosamente.';
            }
        } catch (ValidationException $e) {
            $_SESSION['_flash_error'] = implode(' ', $e->getErrors());
            header('Location: /admin?tab=creditos' . ($id > 0 ? '&edit_id=' . $id : ''));
            exit;
        } catch (\Throwable $e) {
            error_log('[AdminCreditsController::saveCreditMember] ' . $e->getMessage());
            $_SESSION['_flash_error'] = 'No se pudo guardar el integrante de créditos. Inténtalo nuevamente.';
            header('Location: /admin?tab=creditos' . ($id > 0 ? '&edit_id=' . $id : ''));
            exit;
        }

        header('Location: /admin?tab=creditos');
        exit;
    }

    public function deleteCreditMember(): void
    {
        $this->checkSuperAdminPermissions();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['_flash_error'] = 'ID de integrante inválido.';
            header('Location: /admin?tab=creditos');
            exit;
        }

        try {
            $this->creditMemberService->delete($id);
            $_SESSION['_flash_success'] = 'Integrante de créditos eliminado exitosamente.';
        } catch (\RuntimeException) {
            $_SESSION['_flash_error'] = 'El integrante no existe o ya fue eliminado.';
        } catch (\Throwable $e) {
            error_log('[AdminCreditsController::deleteCreditMember] ' . $e->getMessage());
            $_SESSION['_flash_error'] = 'No se pudo eliminar el integrante. Inténtalo nuevamente.';
        }

        header('Location: /admin?tab=creditos');
        exit;
    }
}
