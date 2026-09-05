<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CreditsService;
use App\Services\FooterService;

/**
 * Controlador de la vista pública de Créditos (/credits).
 * Presenta a los integrantes del equipo Charlie y procesa el envío de
 * mensajes de contacto de forma segura sin exponer correos en el cliente.
 */
final class CreditsController extends Controller
{
    private CreditsService $creditsService;
    private FooterService $footerService;

    public function __construct()
    {
        $this->creditsService = new CreditsService();
        $this->footerService = new FooterService();
    }

    public function index(): void
    {
        sessionStart();
        try {
            $footer = $this->footerService->getAll();
        } catch (\Throwable) {
            $footer = ['links' => [], 'info' => null];
        }
        $miembros = $this->creditsService->getPublicMembers();

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $this->render('credits', [
            'pageTitle'       => 'Créditos — Equipo Charlie — SFL ULS Lab',
            'metaDescription' => 'Créditos y equipo de desarrollo y diseño UI/UX del software Software Factory Lab.',
            'miembros'        => $miembros,
            'enlacesFooter'   => $footer['links'] ?? [],
            'contacto'        => $footer['info'] ?? [
                'address'          => 'La Serena, Chile',
                'email'            => 'contacto@sfl.uls.cl',
                'copyright_text'   => '© SFL. Todos los derechos reservados',
                'social_linkedin'  => '#',
                'social_twitter'   => '#',
                'social_instagram' => '#',
            ],
            'flashSuccess'    => $flashSuccess,
            'flashError'      => $flashError,
        ]);
    }

    public function submit(): void
    {
        sessionStart();
        try {
            $result = $this->creditsService->sendContactMessage($_POST);
            $_SESSION['flash_success'] = $result['message'];
        } catch (\InvalidArgumentException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        } catch (\Throwable) {
            $_SESSION['flash_error'] = 'Ocurrió un error al procesar el envío del mensaje.';
        }

        header('Location: /credits');
        exit;
    }
}
