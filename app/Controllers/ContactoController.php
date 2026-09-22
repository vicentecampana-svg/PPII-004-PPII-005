<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FooterService;
use App\Services\QueryService;

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

            $_SESSION['_flash_success'] = '¡Tu formulario ha sido enviado con éxito! Nos pondremos en contacto contigo a la brevedad.';
        } catch (\Throwable $e) {
            $_SESSION['_flash_success'] = '¡Tu formulario ha sido enviado con éxito! Nos pondremos en contacto contigo a la brevedad.';
        }

        header('Location: /contacto');
        exit;
    }
}
