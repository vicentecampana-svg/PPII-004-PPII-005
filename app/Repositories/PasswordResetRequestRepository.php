<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Registro de solicitudes de recuperación de contraseña.
 *
 * Se usa exclusivamente para aplicar un cooldown entre envíos (evitar
 * spam de correos), independientemente de si el correo pertenece a un
 * usuario real o no.
 */
class PasswordResetRequestRepository
{
    /**
     * Registra una nueva solicitud para el correo e IP dados.
     */
    public function log(string $email, string $ip): void
    {
        dbInsert('password_reset_request', [
            'email' => mb_strtolower(trim($email)),
            'ip'    => $ip,
        ]);
    }

    /**
     * Fecha/hora (string, formato de Postgres) de la solicitud más
     * reciente para ese correo, o null si nunca se ha solicitado.
     */
    public function lastRequestAt(string $email): ?string
    {
        $row = dbFetchOne(
            "SELECT requested_at
               FROM password_reset_request
              WHERE LOWER(email) = LOWER(:email)
              ORDER BY requested_at DESC
              LIMIT 1",
            ['email' => trim($email)]
        );

        return $row['requested_at'] ?? null;
    }

    /**
     * Limpieza opcional de registros antiguos (fuera de cualquier
     * ventana de cooldown razonable).
     */
    public function deleteOlderThan(int $hours = 24): void
    {
        dbDelete('password_reset_request', "requested_at <= NOW() - INTERVAL '{$hours} hours'");
    }
}
