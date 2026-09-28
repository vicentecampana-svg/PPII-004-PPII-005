<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PasswordResetRepository;
use App\Repositories\PasswordResetRequestRepository;
use App\Repositories\UserRepository;

/**
 * Servicio que orquesta el flujo de recuperación de contraseña.
 *
 * Flujo:
 *  1. El usuario pide recuperar su contraseña (requestReset).
 *  2. Se aplica un cooldown por correo para evitar spam de reenvíos.
 *  3. Se genera un token de un solo uso con vencimiento de 1 hora.
 *  4. Se envía el enlace por correo (o se loguea en dev).
 *  5. El usuario abre el enlace y envía la nueva contraseña (resetPassword).
 *  6. El token queda marcado como usado y la contraseña se actualiza.
 */
final class PasswordResetService
{
    private const TOKEN_TTL_HOURS = 1;

    /** Segundos que deben pasar entre dos solicitudes para el mismo correo. */
    public const RESEND_COOLDOWN_SECONDS = 60;

    private UserRepository $userRepo;
    private PasswordResetRepository $resetRepo;
    private PasswordResetRequestRepository $requestRepo;
    private MailerService $mailer;

    public function __construct(
        ?UserRepository $userRepo = null,
        ?PasswordResetRepository $resetRepo = null,
        ?PasswordResetRequestRepository $requestRepo = null,
        ?MailerService $mailer = null
    ) {
        $this->userRepo    = $userRepo    ?? new UserRepository();
        $this->resetRepo   = $resetRepo   ?? new PasswordResetRepository();
        $this->requestRepo = $requestRepo ?? new PasswordResetRequestRepository();
        $this->mailer      = $mailer      ?? new MailerService();
    }

    /**
     * Segundos que faltan para poder volver a solicitar un enlace para
     * ese correo. 0 si ya se puede solicitar.
     */
    public function secondsUntilNextAllowed(string $email): int
    {
        $lastRequestAt = $this->requestRepo->lastRequestAt($email);
        if ($lastRequestAt === null) {
            return 0;
        }

        $elapsed   = time() - strtotime($lastRequestAt);
        $remaining = self::RESEND_COOLDOWN_SECONDS - $elapsed;

        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Solicita el restablecimiento de contraseña para el correo dado.
     *
     * Por seguridad, el comportamiento (incluido el cooldown) es idéntico
     * exista o no el correo, para no permitir enumerar usuarios válidos.
     *
     * @param  string $email    Correo ingresado por el usuario.
     * @param  string $baseUrl  URL base de la aplicación (ej. https://techhub.uls.cl).
     * @param  string $ip       IP del solicitante, para el registro de intentos.
     * @return int              Segundos restantes de cooldown (0 si se procesó la solicitud).
     */
    public function requestReset(string $email, string $baseUrl, string $ip = ''): int
    {
        $remaining = $this->secondsUntilNextAllowed($email);
        if ($remaining > 0) {
            return $remaining;
        }

        $this->requestRepo->log($email, $ip);

        $user = $this->userRepo->findByEmail($email);

        if (!$user || !$user['active']) {
            // No generar token ni enviar correo, pero sin filtrar información
            return 0;
        }

        // Token plano: 32 bytes aleatorios en hex (64 caracteres URL-safe)
        $plainToken = bin2hex(random_bytes(32));
        // Guardar sólo el hash para que si la BD es comprometida el token no sirva
        $hashedToken = hash('sha256', $plainToken);

        $expiresAt = date('Y-m-d H:i:s', time() + self::TOKEN_TTL_HOURS * 3600);

        $this->resetRepo->create((int) $user['id'], $hashedToken, $expiresAt);

        $resetLink = rtrim($baseUrl, '/') . '/restablecer-password/' . urlencode($plainToken);

        $this->mailer->sendPasswordReset(
            $user['email'],
            $user['username'],
            $resetLink
        );

        return 0;
    }

    /**
     * Verifica si el token plano es válido (no usado, no vencido).
     *
     * @param  string $plainToken  Token tal como viene de la URL.
     * @return array|null          Fila del token si válido, null si no.
     */
    public function findValidToken(string $plainToken): ?array
    {
        $hashedToken = hash('sha256', $plainToken);
        return $this->resetRepo->findValid($hashedToken);
    }

    /**
     * Restablece la contraseña usando el token.
     *
     * @param  string $plainToken   Token plano de la URL.
     * @param  string $newPassword  Nueva contraseña en claro (será hasheada aquí).
     * @return bool                 true si se pudo restablecer, false si el token no es válido.
     */
    public function resetPassword(string $plainToken, string $newPassword): bool
    {
        $tokenRow = $this->findValidToken($plainToken);

        if (!$tokenRow) {
            return false;
        }

        $userId = (int) $tokenRow['user_id'];

        // Actualizar contraseña y quitar flag de cambio forzado. El flujo corre
        // sin sesión, así que el autor de la auditoría es el propio usuario.
        $userService = new UserService($this->userRepo);
        $userService->update($userId, [
            'password'             => $newPassword,
            'must_change_password' => false,
        ], $userId);

        // Invalidar el token
        $this->resetRepo->markUsed((int) $tokenRow['id']);

        return true;
    }
}
