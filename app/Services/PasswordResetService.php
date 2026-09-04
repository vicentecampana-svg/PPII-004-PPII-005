<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PasswordResetRepository;
use App\Repositories\UserRepository;

/**
 * Servicio que orquesta el flujo de recuperación de contraseña.
 *
 * Flujo:
 *  1. El usuario pide recuperar su contraseña (requestReset).
 *  2. Se genera un token de un solo uso con vencimiento de 1 hora.
 *  3. Se envía el enlace por correo (o se loguea en dev).
 *  4. El usuario abre el enlace y envía la nueva contraseña (resetPassword).
 *  5. El token queda marcado como usado y la contraseña se actualiza.
 */
final class PasswordResetService
{
    private const TOKEN_TTL_HOURS = 1;

    private UserRepository $userRepo;
    private PasswordResetRepository $resetRepo;
    private MailerService $mailer;

    public function __construct(
        ?UserRepository $userRepo = null,
        ?PasswordResetRepository $resetRepo = null,
        ?MailerService $mailer = null
    ) {
        $this->userRepo  = $userRepo  ?? new UserRepository();
        $this->resetRepo = $resetRepo ?? new PasswordResetRepository();
        $this->mailer    = $mailer    ?? new MailerService();
    }

    /**
     * Solicita el restablecimiento de contraseña para el correo dado.
     *
     * Por seguridad, siempre devuelve true aunque el correo no exista
     * (evita enumerar usuarios válidos).
     *
     * @param  string $email    Correo ingresado por el usuario.
     * @param  string $baseUrl  URL base de la aplicación (ej. https://techhub.uls.cl).
     * @return bool             true en cualquier caso (no revela si el correo existe).
     */
    public function requestReset(string $email, string $baseUrl): bool
    {
        $user = $this->userRepo->findByEmail($email);

        if (!$user || !$user['active']) {
            // Retornar true de todas formas para no filtrar información
            return true;
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

        return true;
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

        // Actualizar contraseña y quitar flag de cambio forzado
        $userService = new UserService($this->userRepo);
        $userService->update($userId, [
            'password'             => $newPassword,
            'must_change_password' => false,
        ]);

        // Invalidar el token
        $this->resetRepo->markUsed((int) $tokenRow['id']);

        return true;
    }
}
