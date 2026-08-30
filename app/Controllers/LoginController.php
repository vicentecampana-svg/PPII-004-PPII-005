<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CaptchaService;
use App\Services\FooterService;
use App\Services\RateLimiterService;
use App\Services\UserService;

final class LoginController extends Controller
{
    private UserService $userService;
    private CaptchaService $captchaService;
    private RateLimiterService $rateLimiter;
    private FooterService $footerService;

    public function __construct(
        ?UserService $userService = null,
        ?CaptchaService $captchaService = null,
        ?RateLimiterService $rateLimiter = null,
        ?FooterService $footerService = null
    ) {
        $this->userService = $userService ?? new UserService();
        $this->captchaService = $captchaService ?? new CaptchaService();
        $this->rateLimiter = $rateLimiter ?? new RateLimiterService();
        $this->footerService = $footerService ?? new FooterService();
    }

    public function show(): void
    {
        $footer = $this->footerService->getAll();

        $this->render('login', [
            'pageTitle'       => 'Iniciar sesión — SFL ULS Lab',
            'metaDescription' => 'Acceso al panel interno del Software Factory Lab de la Universidad de La Serena.',
            'csrfToken'       => csrfToken(),
            'errors'          => $_SESSION['_login_errors'] ?? [],
            'email'           => $_SESSION['_login_email'] ?? '',
            'enlacesFooter'   => $footer['links'],
            'contacto'        => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
        ]);

        unset($_SESSION['_login_errors'], $_SESSION['_login_email']);
    }

    public function submit(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $captcha = (string) ($_POST['captcha'] ?? '');
        $ip = $this->rateLimiter->getClientIp();

        $errors = $this->validateInput($email, $password, $captcha);

        // 1. Validar CAPTCHA antes de cualquier consulta a base de datos o contraseña
        if (!$errors && !$this->captchaService->validate($captcha)) {
            $errors['captcha'] = 'El código de seguridad (CAPTCHA) es incorrecto o ha expirado.';
        }

        // 2. Verificar bloqueo por límite de intentos
        if (!$errors && $this->rateLimiter->isBlocked($ip, $email)) {
            $errors['general'] = 'Demasiados intentos fallidos. Su cuenta o dirección IP ha sido bloqueada temporalmente por 15 minutos.';
        }

        $user = null;

        // 3. Comprobar credenciales solo si pasó CAPTCHA y no está bloqueado
        if (!$errors) {
            $user = $this->userService->getByEmail($email);

            if (!$user || !password_verify($password, (string) ($user['password'] ?? ''))) {
                $this->rateLimiter->recordFailedAttempt($ip, $email);
                $errors['general'] = 'Correo o contraseña incorrectos.';
            } elseif (!$user['active']) {
                $errors['general'] = 'La cuenta está desactivada.';
            }
        }

        if ($errors) {
            $_SESSION['_login_errors'] = $errors;
            $_SESSION['_login_email'] = $email;
            header('Location: /login');
            exit;
        }

        // Login exitoso: limpiar intentos fallidos y crear sesión
        $this->rateLimiter->recordSuccess($ip, $email);

        authLogin(
            (int) $user['id'],
            $user['username'],
            (int) $user['role_id'],
            $user['role_name'],
            (bool) $user['must_change_password']
        );

        header('Location: /admin');
        exit;
    }

    private function validateInput(string $email, string $password, string $captcha): array
    {
        $errors = [];
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ingresa un correo válido.';
        }
        if ($password === '') {
            $errors['password'] = 'La contraseña es obligatoria.';
        }
        if ($captcha === '') {
            $errors['captcha'] = 'Ingresa el código de seguridad.';
        }
        return $errors;
    }
}
