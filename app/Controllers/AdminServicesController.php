<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ValidationException;
use App\Services\ServiceService;

/**
 * RF-SRV-001: controlador del panel para la sección de Servicios.
 */
final class AdminServicesController extends AdminControllerBase
{
    private ServiceService $serviceService;

    public function __construct()
    {
        parent::__construct();
        $this->serviceService = new ServiceService();
    }

    public function saveService(): void
    {
        $this->checkSuperAdminPermissions();

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $link = trim((string) ($_POST['link'] ?? ''));
        $image = trim((string) ($_POST['existing_image'] ?? ''));
        $backUrl = '/admin?tab=servicios' . ($id > 0 ? '&edit_id=' . $id : '');

        if (isset($_FILES['image_file']) && !empty($_FILES['image_file']['name'])) {
            try {
                $image = $this->mediaService->upload($_FILES['image_file'], 'service_');
            } catch (\InvalidArgumentException) {
                $_SESSION['_flash_error'] = 'La imagen no es válida: solo se permiten JPG/PNG y máximo 5MB.';
                header('Location: ' . $backUrl);
                exit;
            } catch (\Throwable) {
                $_SESSION['_flash_error'] = 'No se pudo subir la imagen. Inténtalo nuevamente.';
                header('Location: ' . $backUrl);
                exit;
            }
        }

        $data = [
            'name'        => $name,
            'description' => $description,
            'link'        => $link !== '' ? $link : null,
            'image'       => $image !== '' ? $image : null,
        ];

        try {
            if ($id > 0) {
                $this->serviceService->update($id, $data);
                $_SESSION['_flash_success'] = 'Servicio actualizado exitosamente.';
            } else {
                $this->serviceService->create($data);
                $_SESSION['_flash_success'] = 'Servicio creado exitosamente.';
            }
        } catch (ValidationException $e) {
            $_SESSION['_flash_error'] = implode(' ', $e->getErrors());
            header('Location: ' . $backUrl);
            exit;
        } catch (\Throwable) {
            $_SESSION['_flash_error'] = 'No se pudo guardar el servicio. Inténtalo nuevamente.';
        }

        header('Location: /admin?tab=servicios');
        exit;
    }

    public function deleteService(): void
    {
        $this->checkSuperAdminPermissions();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $this->serviceService->delete($id);
                $_SESSION['_flash_success'] = 'Servicio eliminado exitosamente.';
            } catch (\Throwable) {
                $_SESSION['_flash_error'] = 'No se pudo eliminar el servicio. Inténtalo nuevamente.';
            }
        }

        header('Location: /admin?tab=servicios');
        exit;
    }
}
