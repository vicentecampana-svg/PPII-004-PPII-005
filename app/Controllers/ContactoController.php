<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FooterService;
use App\Services\FormGuardService;
use App\Services\QueryService;
use App\Services\RateLimiterService;

/**
 * Página pública de Contacto y envío de formulario de consultas.
 */
final class ContactoController extends Controller
{
    private QueryService $queryService;
    private FooterService $footerService;

    public function __construct(?QueryService $queryService = null, ?FooterService $footerService = null)
    {
        $this->queryService = $queryService ?? new QueryService();
        $this->footerService = $footerService ?? new FooterService();
    }

    public function index(): void
    {
        try {
            $footer = $this->footerService->getAll();
        } catch (\Throwable) {
            $footer = ['links' => [], 'info' => null];
        }

        $flashSuccess = $_SESSION['_flash_success'] ?? null;
        $flashError = $_SESSION['_flash_error'] ?? null;
        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        $this->render('contacto', [
            'pageTitle' => 'Contáctenos — SFL ULS Lab',
            'metaDescription' => 'Envía tu consulta o requerimiento al Software Factory Lab de la Universidad de La Serena.',
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
            'enlacesFooter' => $footer['links'] ?? [],
            'contacto' => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
        ]);
    }

    public function submit(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        // SEC-03: honeypot + límite por IP y correo para evitar spam.
        $guard = new FormGuardService();
        $ip = (new RateLimiterService())->getClientIp();

        try {
            $guard->assertNotHoneypot($_POST);
        } catch (\InvalidArgumentException) {
            // Un bot: se simula éxito para no revelar el honeypot.
            $_SESSION['_flash_success'] = 'Gracias, tu consulta fue enviada.';
            header('Location: /contacto');
            exit;
        }

        try {
            $guard->assertAllowed('contacto', $ip, $email);
        } catch (\RuntimeException) {
            $_SESSION['_flash_error'] = 'Demasiados envíos en poco tiempo. Inténtalo más tarde.';
            header('Location: /contacto');
            exit;
        }

        $guard->record('contacto', $ip, $email);

        if ($name === '' || $email === '' || $message === '') {
            $_SESSION['_flash_error'] = 'Por favor complete todos los campos requeridos (nombre, correo y motivo).';
            header('Location: /contacto');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['_flash_error'] = 'El correo electrónico ingresado no es válido.';
            header('Location: /contacto');
            exit;
        }

        try {
            $this->queryService->create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone ?: null,
                'subject' => $subject ?: 'Consulta general',
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            $_SESSION['_flash_error'] = 'Ocurrió un error al enviar tu formulario. Por favor, intenta nuevamente.';
            header('Location: /contacto');
            exit;
        }

        $ticketUrl = trim((string) getenv('TICKET_PLATFORM_URL'));
        $redirect = ($ticketUrl !== '' && filter_var($ticketUrl, FILTER_VALIDATE_URL)) ? $ticketUrl : 'about:blank';
        header('Location: ' . $redirect);
        exit;
    }
}
