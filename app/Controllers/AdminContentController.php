<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FooterService;

/**
 * CAL-03: controlador del panel para el contenido institucional.
 */
final class AdminContentController extends AdminControllerBase
{
    private FooterService $footerService;

    public function __construct()
    {
        parent::__construct();
        $this->footerService = new FooterService();
    }

    public function saveSobreNosotros(): void
    {
        $this->checkAdminPermissions();

        $sobreTitulo = trim((string) ($_POST['sobre_titulo'] ?? ''));
        $sobreTexto = trim((string) ($_POST['sobre_texto'] ?? ''));
        $misionTitulo = trim((string) ($_POST['mision_titulo'] ?? ''));
        $misionTexto = trim((string) ($_POST['mision_texto'] ?? ''));

        if ($sobreTitulo === '' || $sobreTexto === '') {
            $_SESSION['_flash_error'] = 'El título y texto de Sobre Nosotros son obligatorios.';
            header('Location: /admin?tab=sobre-nosotros');
            exit;
        }

        try {
            $data = [
                'sobre_titulo'  => $sobreTitulo,
                'sobre_texto'   => $sobreTexto,
                'mision_titulo' => $misionTitulo !== '' ? $misionTitulo : null,
                'mision_texto'  => $misionTexto !== '' ? $misionTexto : null,
            ];

            $this->footerService->updateContenido($data);
            $_SESSION['_flash_success'] = 'Contenido de Sobre Nosotros guardado exitosamente.';
        } catch (\Throwable $e) {
            error_log('[AdminContentController::saveSobreNosotros] ' . $e->getMessage());
            $_SESSION['_flash_error'] = 'No se pudo guardar el contenido. Inténtalo nuevamente o contacta al administrador del sitio.';
        }

        header('Location: /admin?tab=sobre-nosotros');
        exit;
    }
}
