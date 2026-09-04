<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Repositorio para los tokens de recuperación de contraseña.
 *
 * La tabla `password_reset_token` almacena tokens de un solo uso
 * con vencimiento. Cada solicitud invalida las anteriores del mismo usuario.
 */
class PasswordResetRepository
{
    /**
     * Elimina todos los tokens existentes del usuario y crea uno nuevo.
     *
     * @param  int    $userId     ID del usuario dueño del token.
     * @param  string $token      Hash seguro del token (no el token plano).
     * @param  string $expiresAt  Fecha/hora de vencimiento en formato ISO-8601.
     */
    public function create(int $userId, string $token, string $expiresAt): void
    {
        // Invalidar tokens anteriores del mismo usuario
        dbDelete('password_reset_token', 'user_id = :uid', ['uid' => $userId]);

        dbInsert('password_reset_token', [
            'user_id'    => $userId,
            'token'      => $token,
            'expires_at' => $expiresAt,
            'used'       => false,
        ]);
    }

    /**
     * Busca un token por su hash, sólo si no está usado ni vencido.
     *
     * @param  string $token  Hash del token a buscar.
     * @return array|null     Fila con user_id, token, expires_at, used; o null.
     */
    public function findValid(string $token): ?array
    {
        return dbFetchOne(
            "SELECT id, user_id, token, expires_at, used
               FROM password_reset_token
              WHERE token = :token
                AND used = false
                AND expires_at > NOW()",
            ['token' => $token]
        );
    }

    /**
     * Marca el token como usado para que no pueda reutilizarse.
     *
     * @param  int $id  ID primario del registro.
     */
    public function markUsed(int $id): void
    {
        dbUpdate('password_reset_token', ['used' => true], 'id = :id', ['id' => $id]);
    }

    /**
     * Elimina tokens vencidos de la tabla (limpieza periódica opcional).
     */
    public function deleteExpired(): void
    {
        dbDelete('password_reset_token', 'expires_at <= NOW()');
    }
}
