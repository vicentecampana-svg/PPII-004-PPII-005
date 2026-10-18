<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ProjectService;

/**
 * CAL-03: controlador del panel para la sección de Proyectos.
 */
final class AdminProjectsController extends AdminControllerBase
{
    private ProjectService $projectService;

    public function __construct()
    {
        parent::__construct();
        $this->projectService = new ProjectService();
    }

    public function saveProject(): void
    {
        $this->checkAdminPermissions();

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $link = trim((string) ($_POST['link'] ?? ''));
        $active = isset($_POST['active']) ? (bool) $_POST['active'] : false;
        $image = trim((string) ($_POST['existing_image'] ?? ''));

        if ($name === '') {
            $_SESSION['_flash_error'] = 'El título del proyecto es obligatorio.';
            header('Location: /admin?tab=proyectos' . ($id > 0 ? '&edit_id=' . $id : ''));
            exit;
        }

        if (isset($_FILES['image_file']) && !empty($_FILES['image_file']['name'])) {
            try {
                $image = $this->mediaService->upload($_FILES['image_file'], 'project_');
            } catch (\InvalidArgumentException) {
                $_SESSION['_flash_error'] = 'La imagen no es válida: solo se permiten JPG/PNG y máximo 5MB.';
                header('Location: /admin?tab=proyectos' . ($id > 0 ? '&edit_id=' . $id : ''));
                exit;
            } catch (\Throwable) {
                $_SESSION['_flash_error'] = 'No se pudo subir la imagen. Inténtalo nuevamente.';
                header('Location: /admin?tab=proyectos' . ($id > 0 ? '&edit_id=' . $id : ''));
                exit;
            }
        }

        try {
            $data = [
                'name'        => $name,
                'description' => $description !== '' ? $description : null,
                'link'        => $link !== '' ? $link : null,
                'active'      => $active,
                'image'       => $image !== '' ? $image : null,
            ];

            if ($id > 0) {
                $this->projectService->update($id, $data);
                $_SESSION['_flash_success'] = 'Proyecto actualizado exitosamente.';
            } else {
                $this->projectService->create($data);
                $_SESSION['_flash_success'] = 'Proyecto creado exitosamente.';
            }
        } catch (\Throwable) {
            $_SESSION['_flash_error'] = 'No se pudo guardar el proyecto. Inténtalo nuevamente.';
        }

        header('Location: /admin?tab=proyectos');
        exit;
    }

    public function deleteProject(): void
    {
        $this->checkAdminPermissions();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $this->projectService->delete($id);
                $_SESSION['_flash_success'] = 'Proyecto eliminado exitosamente.';
            } catch (\Throwable) {
                $_SESSION['_flash_error'] = 'No se pudo eliminar el proyecto. Inténtalo nuevamente.';
            }
        }

        header('Location: /admin?tab=proyectos');
        exit;
    }
}
