<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ValidationException;
use App\Services\CaptchaService;
use App\Services\FooterService;
use App\Services\FormGuardService;
use App\Services\QueryService;
use App\Services\RateLimiterService;
use App\Services\ServiceService;

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
        $old = $_SESSION['_contact_old'] ?? [];
        $ticketUrl = $_SESSION['_contact_ticket_url'] ?? null;
        unset($_SESSION['_flash_success'], $_SESSION['_flash_error'], $_SESSION['_contact_old'], $_SESSION['_contact_ticket_url']);

        $this->render('contacto', [
            'pageTitle' => 'Contáctenos — SFL ULS Lab',
            'metaDescription' => 'Envía tu consulta o requerimiento al Software Factory Lab de la Universidad de La Serena.',
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
            'old' => $old,
            'ticketUrl' => $ticketUrl,
            'types' => QueryService::TYPES,
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
            $guard->recordRejection('contacto', FormGuardService::REJECT_HONEYPOT, $ip, $email);
            // Un bot: se simula éxito para no revelar el honeypot.
            $_SESSION['_flash_success'] = 'Gracias, tu consulta fue enviada.';
            header('Location: /contacto');
            exit;
        }

        // Si el envío falla, se conserva lo escrito para no obligar a repetirlo.
        $_SESSION['_contact_old'] = compact('name', 'email', 'phone', 'subject', 'message');

        // SEC-10: CAPTCHA antes de consumir el límite de envíos.
        if (!(new CaptchaService())->validate((string) ($_POST['captcha'] ?? ''))) {
            $guard->recordRejection('contacto', FormGuardService::REJECT_CAPTCHA, $ip, $email);
            $this->fail('El código de seguridad (CAPTCHA) es incorrecto o ha expirado.');
        }

        try {
            $guard->assertAllowed('contacto', $ip, $email);
        } catch (\RuntimeException) {
            $guard->recordRejection('contacto', FormGuardService::REJECT_LIMIT, $ip, $email);
            $this->fail('Demasiados envíos en poco tiempo. Inténtalo más tarde.');
        }

        $guard->record('contacto', $ip, $email);

        // RF-LEG-001: sin consentimiento explícito no se almacena ningún dato.
        if (empty($_POST['consent'])) {
            $this->fail('Debes dar tu consentimiento para el tratamiento de tus datos personales; sin él no podemos registrar tu solicitud.');
        }

        try {
            $this->queryService->create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone ?: null,
                'subject' => $subject,
                'message' => $message,
            ]);
        } catch (ValidationException $e) {
            // RF-CON-001: se indica cada campo inválido y el motivo.
            $this->fail(implode(' ', $e->getErrors()));
        } catch (\Throwable $e) {
            $this->fail('Ocurrió un error al enviar tu formulario. Por favor, intenta nuevamente.');
        }

        unset($_SESSION['_contact_old']);

        // RF-CON-001: el usuario ve la confirmación en el sitio. Si existe una
        // plataforma de tickets, se ofrece como enlace en la confirmación.
        $_SESSION['_flash_success'] = 'Gracias, tu solicitud fue enviada. El equipo la revisará y te responderá por correo.';
        $ticketUrl = trim((string) getenv('TICKET_PLATFORM_URL'));
        if ($ticketUrl !== '' && ServiceService::isHttpUrl($ticketUrl)) {
            $_SESSION['_contact_ticket_url'] = $ticketUrl;
        }
        header('Location: /contacto');
        exit;
    }

    private function fail(string $message): never
    {
        $_SESSION['_flash_error'] = $message;
        header('Location: /contacto');
        exit;
    }
}
