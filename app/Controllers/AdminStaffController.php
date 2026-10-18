<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\StaffService;

/**
 * CAL-03: controlador del panel para la sección de Staff.
 */
final class AdminStaffController extends AdminControllerBase
{
    private StaffService $staffService;

    public function __construct()
    {
        parent::__construct();
        $this->staffService = new StaffService();
    }

    public function saveStaff(): void
    {
        $this->checkAdminPermissions();

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $position = trim((string) ($_POST['position'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $photo = trim((string) ($_POST['existing_photo'] ?? ''));

        if ($name === '') {
            $_SESSION['_flash_error'] = 'El nombre del miembro del staff es obligatorio.';
            header('Location: /admin?tab=staff' . ($id > 0 ? '&edit_id=' . $id : ''));
            exit;
        }

        if (isset($_FILES['photo_file']) && !empty($_FILES['photo_file']['name'])) {
            try {
                $photo = $this->mediaService->upload($_FILES['photo_file'], 'staff_');
            } catch (\InvalidArgumentException) {
                $_SESSION['_flash_error'] = 'La foto no es válida: solo se permiten JPG/PNG y máximo 5MB.';
                header('Location: /admin?tab=staff' . ($id > 0 ? '&edit_id=' . $id : ''));
                exit;
            } catch (\Throwable) {
                $_SESSION['_flash_error'] = 'No se pudo subir la foto. Inténtalo nuevamente.';
                header('Location: /admin?tab=staff' . ($id > 0 ? '&edit_id=' . $id : ''));
                exit;
            }
        }

        try {
            $data = [
                'name'        => $name,
                'position'    => $position !== '' ? $position : null,
                'description' => $description !== '' ? $description : null,
                'photo'       => $photo !== '' ? $photo : null,
            ];

            if ($id > 0) {
                $this->staffService->update($id, $data);
                $_SESSION['_flash_success'] = 'Miembro del staff actualizado exitosamente.';
            } else {
                $this->staffService->create($data);
                $_SESSION['_flash_success'] = 'Miembro del staff creado exitosamente.';
            }
        } catch (\Throwable) {
            $_SESSION['_flash_error'] = 'No se pudo guardar el miembro del staff. Inténtalo nuevamente.';
        }

        header('Location: /admin?tab=staff');
        exit;
    }

    public function deleteStaff(): void
    {
        $this->checkAdminPermissions();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $this->staffService->delete($id);
                $_SESSION['_flash_success'] = 'Miembro del staff eliminado exitosamente.';
            } catch (\Throwable) {
                $_SESSION['_flash_error'] = 'No se pudo eliminar el miembro del staff. Inténtalo nuevamente.';
            }
        }

        header('Location: /admin?tab=staff');
        exit;
    }
}
