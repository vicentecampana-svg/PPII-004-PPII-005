<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FooterService;

/**
 * CAL-03: controlador del panel para el pie de página.
 */
final class AdminFooterController extends AdminControllerBase
{
    private FooterService $footerService;

    public function __construct()
    {
        parent::__construct();
        $this->footerService = new FooterService();
    }

    public function saveFooterLink(): void
    {
        $this->checkSuperAdminPermissions();

        $id = (int) ($_POST['id'] ?? 0);
        $grupo = trim((string) ($_POST['grupo'] ?? 'Sitio'));
        $etiqueta = trim((string) ($_POST['etiqueta'] ?? ''));
        $url = trim((string) ($_POST['url'] ?? '/'));
        $orden = (int) ($_POST['orden'] ?? 0);

        if ($etiqueta === '' || $url === '') {
            $_SESSION['_flash_error'] = 'La etiqueta y URL del enlace son obligatorios.';
            header('Location: /admin?tab=footer' . ($id > 0 ? '&edit_id=' . $id : ''));
            exit;
        }

        try {
            $data = [
                'grupo'    => $grupo !== '' ? $grupo : 'Sitio',
                'etiqueta' => $etiqueta,
                'url'      => $url,
                'orden'    => $orden,
            ];

            if ($id > 0) {
                $this->footerService->updateLink($id, $data);
                $_SESSION['_flash_success'] = 'Enlace del footer actualizado exitosamente.';
            } else {
                $this->footerService->createLink($data);
                $_SESSION['_flash_success'] = 'Enlace del footer creado exitosamente.';
            }
        } catch (\Throwable) {
            $_SESSION['_flash_error'] = 'No se pudo guardar el enlace del footer. Inténtalo nuevamente.';
        }

        header('Location: /admin?tab=footer');
        exit;
    }

    public function deleteFooterLink(): void
    {
        $this->checkSuperAdminPermissions();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $this->footerService->deleteLink($id);
                $_SESSION['_flash_success'] = 'Enlace del footer eliminado exitosamente.';
            } catch (\Throwable) {
                $_SESSION['_flash_error'] = 'No se pudo eliminar el enlace del footer. Inténtalo nuevamente.';
            }
        }

        header('Location: /admin?tab=footer');
        exit;
    }

    public function saveFooterSocial(): void
    {
        $this->checkSuperAdminPermissions();

        $linkedin = trim((string) ($_POST['social_linkedin'] ?? ''));
        $twitter = trim((string) ($_POST['social_twitter'] ?? ''));
        $instagram = trim((string) ($_POST['social_instagram'] ?? ''));

        try {
            $data = [
                'social_linkedin'  => $linkedin !== '' ? $linkedin : null,
                'social_twitter'   => $twitter !== '' ? $twitter : null,
                'social_instagram' => $instagram !== '' ? $instagram : null,
            ];

            $this->footerService->updateInfo($data);
            $_SESSION['_flash_success'] = 'Redes sociales del footer actualizadas exitosamente.';
        } catch (\Throwable) {
            $_SESSION['_flash_error'] = 'No se pudieron actualizar las redes sociales. Inténtalo nuevamente.';
        }

        header('Location: /admin?tab=footer');
        exit;
    }
}
