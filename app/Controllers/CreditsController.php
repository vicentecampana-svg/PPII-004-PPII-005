<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CreditsService;
use App\Services\FooterService;
use App\Services\FormGuardService;
use App\Services\RateLimiterService;

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

        // SEC-03: honeypot + límite por IP y correo para evitar spam.
        $guard = new FormGuardService();
        $ip = (new RateLimiterService())->getClientIp();
        $email = trim((string) ($_POST['email'] ?? ''));

        try {
            $guard->assertNotHoneypot($_POST);
            $guard->assertAllowed('credits_contact', $ip, $email);
        } catch (\InvalidArgumentException) {
            // Un bot: se simula éxito para no revelar el honeypot.
            $_SESSION['flash_success'] = 'Tu mensaje ha sido enviado exitosamente.';
            header('Location: /credits');
            exit;
        } catch (\RuntimeException) {
            $_SESSION['flash_error'] = 'Demasiados envíos en poco tiempo. Inténtalo más tarde.';
            header('Location: /credits');
            exit;
        }

        $guard->record('credits_contact', $ip, $email);

        try {
            $result = $this->creditsService->sendContactMessage($_POST);
            $_SESSION['flash_success'] = $result['message'];
        } catch (\InvalidArgumentException) {
            $_SESSION['flash_error'] = 'No se pudo enviar el mensaje. Verifica los datos ingresados.';
        } catch (\Throwable) {
            $_SESSION['flash_error'] = 'Ocurrió un error al procesar el envío del mensaje.';
        }

        header('Location: /credits');
        exit;
    }
}
