<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FooterService;
use App\Services\PasswordResetService;

/**
 * Controlador para el flujo de recuperación de contraseña (desde fuera del login).
 *
 * Rutas:
 *   GET  /recuperar-password              → formulario de solicitud (email)
 *   POST /recuperar-password              → procesa solicitud, envía correo (o loguea en dev)
 *   GET  /restablecer-password/{token}    → formulario de nueva contraseña
 *   POST /restablecer-password/{token}    → procesa la nueva contraseña
 */
final class PasswordRecoveryController extends Controller
{
    private PasswordResetService $resetService;
    private FooterService $footerService;

    public function __construct(
        ?PasswordResetService $resetService = null,
        ?FooterService $footerService = null
    ) {
        $this->resetService  = $resetService  ?? new PasswordResetService();
        $this->footerService = $footerService ?? new FooterService();
    }

    // ──────────────────────────────────────────────
    //  GET /recuperar-password
    // ──────────────────────────────────────────────

    public function showRequest(): void
    {
        $footer = $this->footerService->getAll();

        $this->render('recuperar-password', [
            'pageTitle'       => 'Recuperar contraseña — TechHub ULS',
            'metaDescription' => 'Solicita el restablecimiento de tu contraseña de acceso al panel.',
            'csrfToken'       => csrfToken(),
            'errors'          => $_SESSION['_recovery_errors'] ?? [],
            'success'         => $_SESSION['_recovery_success'] ?? null,
            'email'           => $_SESSION['_recovery_email'] ?? '',
            'enlacesFooter'   => $footer['links'] ?? [],
            'contacto'        => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
        ]);

        unset($_SESSION['_recovery_errors'], $_SESSION['_recovery_success'], $_SESSION['_recovery_email']);
    }

    // ──────────────────────────────────────────────
    //  POST /recuperar-password
    // ──────────────────────────────────────────────

    public function submitRequest(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['_recovery_errors'] = ['email' => 'Ingresa un correo válido.'];
            $_SESSION['_recovery_email']  = $email;
            header('Location: /recuperar-password');
            exit;
        }

        $baseUrl = $this->resolveBaseUrl();
        $this->resetService->requestReset($email, $baseUrl);

        // Siempre mostrar el mismo mensaje, independiente de si el correo existe
        $_SESSION['_recovery_success'] = 'Si el correo está registrado, recibirás un enlace de recuperación en breve.';
        header('Location: /recuperar-password');
        exit;
    }

    // ──────────────────────────────────────────────
    //  GET /restablecer-password/{token}
    // ──────────────────────────────────────────────

    public function showReset(string $token): void
    {
        $tokenRow = $this->resetService->findValidToken($token);

        if (!$tokenRow) {
            $_SESSION['_recovery_errors'] = ['general' => 'El enlace de recuperación es inválido o ha expirado.'];
            header('Location: /recuperar-password');
            exit;
        }

        $footer = $this->footerService->getAll();

        $this->render('restablecer-password', [
            'pageTitle'       => 'Restablecer contraseña — TechHub ULS',
            'metaDescription' => 'Establece una nueva contraseña para tu cuenta.',
            'csrfToken'       => csrfToken(),
            'token'           => $token,
            'errors'          => $_SESSION['_reset_errors'] ?? [],
            'enlacesFooter'   => $footer['links'] ?? [],
            'contacto'        => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
        ]);

        unset($_SESSION['_reset_errors']);
    }

    // ──────────────────────────────────────────────
    //  POST /restablecer-password/{token}
    // ──────────────────────────────────────────────

    public function submitReset(string $token): void
    {
        $newPassword     = (string) ($_POST['new_password']     ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        $errors = [];

        if (strlen($newPassword) < 12) {
            $errors['new_password'] = 'La contraseña debe tener al menos 12 caracteres.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Las contraseñas no coinciden.';
        }

        if ($errors) {
            $_SESSION['_reset_errors'] = $errors;
            header('Location: /restablecer-password/' . urlencode($token));
            exit;
        }

        $ok = $this->resetService->resetPassword($token, $newPassword);

        if (!$ok) {
            $_SESSION['_recovery_errors'] = ['general' => 'El enlace de recuperación es inválido o ha expirado.'];
            header('Location: /recuperar-password');
            exit;
        }

        $_SESSION['_login_flash_success'] = 'Contraseña restablecida correctamente. Ya puedes iniciar sesión.';
        header('Location: /login');
        exit;
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

    /**
     * Construye la URL base de la aplicación a partir de la solicitud actual.
     * Si APP_URL está definida en el entorno se usa directamente.
     */
    private function resolveBaseUrl(): string
    {
        $envUrl = getenv('APP_URL');
        if ($envUrl) {
            return rtrim($envUrl, '/');
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host;
    }
}
