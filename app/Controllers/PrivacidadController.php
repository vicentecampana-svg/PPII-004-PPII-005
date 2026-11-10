<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FooterService;

/**
 * RF-FTR-001 / RF-LEG-001: política de privacidad publicada en el footer.
 */
final class PrivacidadController extends Controller
{
    private FooterService $footerService;

    public function __construct(?FooterService $footerService = null)
    {
        $this->footerService = $footerService ?? new FooterService();
    }

    public function index(): void
    {
        try {
            $footer = $this->footerService->getAll();
        } catch (\Throwable) {
            $footer = ['links' => [], 'info' => null];
        }

        $this->render('privacidad', [
            'pageTitle'       => 'Política de privacidad — SFL ULS Lab',
            'metaDescription' => 'Cómo trata el Software Factory Lab los datos personales recibidos a través de este sitio.',
            'enlacesFooter'   => $footer['links'] ?? [],
            'contacto'        => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
        ]);
    }
}
