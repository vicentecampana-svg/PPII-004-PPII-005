<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CaptchaService;
use App\Services\RateLimiterService;
use App\Services\UserService;

class AuthController
{
    private UserService $userService;
    private CaptchaService $captchaService;
    private RateLimiterService $rateLimiter;

    public function __construct(
        ?UserService $userService = null,
        ?CaptchaService $captchaService = null,
        ?RateLimiterService $rateLimiter = null
    ) {
        $this->userService = $userService ?? new UserService();
        $this->captchaService = $captchaService ?? new CaptchaService();
        $this->rateLimiter = $rateLimiter ?? new RateLimiterService();
    }

    public function login(): void
    {
        $data = getJsonInput();
        $ip = $this->rateLimiter->getClientIp();

        if (empty($data['email']) || empty($data['password'])) {
            respBadRequest(['email' => 'Email y contraseña son obligatorios.']);
            return;
        }

        $email = trim((string) $data['email']);
        $password = (string) $data['password'];
        $captcha = (string) ($data['captcha'] ?? '');

        // 1. Validar CAPTCHA si fue enviado o si la sesión tiene CAPTCHA pendiente
        if (isset($data['captcha']) || isset($_SESSION['_captcha_code'])) {
            if (!$this->captchaService->validate($captcha)) {
                respUnprocessable(['captcha' => 'El código de seguridad (CAPTCHA) es incorrecto o ha expirado.']);
                return;
            }
        }

        // 2. Verificar bloqueo por límite de intentos
        if ($this->rateLimiter->isBlocked($ip, $email)) {
            respError('TOO_MANY_REQUESTS', 'Demasiados intentos fallidos. Su cuenta o IP ha sido bloqueada temporalmente por 15 minutos.', 429);
            return;
        }

        $user = $this->userService->getByEmail($email);

        if (!$user || !password_verify($password, (string) ($user['password'] ?? ''))) {
            $this->rateLimiter->recordFailedAttempt($ip, $email);
            respUnauthorized(['general' => 'Credenciales inválidas.']);
            return;
        }

        if (!$user['active']) {
            respForbidden(['general' => 'La cuenta está desactivada.']);
            return;
        }

        $this->rateLimiter->recordSuccess($ip, $email);

        authLogin(
            (int) $user['id'],
            $user['username'],
            (int) $user['role_id'],
            $user['role_name'],
            (bool) $user['must_change_password']
        );

        respSuccess([
            'user' => [
                'id'                   => $user['id'],
                'username'             => $user['username'],
                'email'                => $user['email'],
                'must_change_password' => (bool) $user['must_change_password'],
                'role'                 => ['id' => $user['role_id'], 'name' => $user['role_name']],
            ],
            'csrf_token' => csrfToken(),
        ]);
    }

    public function logout(): void
    {
        authLogout();
        respNoContent();
    }

    public function me(): void
    {
        if (!authCheck()) {
            respUnauthorized();
            return;
        }

        $user = authUser();
        $repo = new \App\Repositories\UserRepository();
        $full = $repo->findById((int) $user['id']);

        if (!$full) {
            respNotFound();
            return;
        }

        respSuccess([
            'user'       => $full,
            'csrf_token' => csrfToken(),
        ]);
    }
}
