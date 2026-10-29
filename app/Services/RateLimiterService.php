<?php

declare(strict_types=1);

namespace App\Services;

class RateLimiterService
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    /**
     * Condición para el alias `f`: fallo dentro de la ventana de bloqueo y sin
     * un login exitoso posterior desde la misma IP o con el mismo correo. Es
     * el mismo efecto que antes tenía borrar esos fallos al iniciar sesión.
     */
    private const RECENT_UNCLEARED_FAILURE = "f.success = false
                  AND f.attempted_at > (CURRENT_TIMESTAMP - INTERVAL '" . self::LOCKOUT_MINUTES . " minutes')
                  AND NOT EXISTS (
                      SELECT 1 FROM login_attempt s
                      WHERE s.success = true
                        AND s.attempted_at >= f.attempted_at
                        AND (s.ip = f.ip OR LOWER(s.email) = LOWER(f.email))
                  )";

    public function isBlocked(string $ip, string $email): bool
    {
        $ipBlocked = $this->countFailedAttemptsByIp($ip) >= self::MAX_ATTEMPTS;
        if ($ipBlocked) {
            return true;
        }

        $emailBlocked = $this->countFailedAttemptsByEmail($email) >= self::MAX_ATTEMPTS;
        return $emailBlocked;
    }

    public function countFailedAttemptsByIp(string $ip): int
    {
        $sql = "SELECT COUNT(*) AS t
                FROM login_attempt f
                WHERE f.ip = :ip
                  AND " . self::RECENT_UNCLEARED_FAILURE;

        $row = dbFetchOne($sql, ['ip' => $ip]);
        return (int) ($row['t'] ?? 0);
    }

    public function countFailedAttemptsByEmail(string $email): int
    {
        if ($email === '') {
            return 0;
        }

        $sql = "SELECT COUNT(*) AS t
                FROM login_attempt f
                WHERE LOWER(f.email) = LOWER(:email)
                  AND " . self::RECENT_UNCLEARED_FAILURE;

        $row = dbFetchOne($sql, ['email' => trim($email)]);
        return (int) ($row['t'] ?? 0);
    }

    public function recordFailedAttempt(string $ip, string $email): void
    {
        dbQuery(
            "INSERT INTO login_attempt (ip, email, success, attempted_at) VALUES (:ip, :email, false, CURRENT_TIMESTAMP)",
            ['ip' => $ip, 'email' => trim($email)]
        );
    }

    /**
     * SEC-13: el login exitoso se registra en vez de borrar los intentos
     * fallidos, para conservar el historial con IP y poder analizar ataques.
     * Los fallos anteriores dejan de contar para el bloqueo (ver
     * RECENT_UNCLEARED_FAILURE).
     */
    public function recordSuccess(string $ip, string $email): void
    {
        dbQuery(
            "INSERT INTO login_attempt (ip, email, success, attempted_at) VALUES (:ip, :email, true, CURRENT_TIMESTAMP)",
            ['ip' => $ip, 'email' => trim($email)]
        );
    }

    /**
     * SEC-01: devuelve la IP real del cliente.
     *
     * `X-Forwarded-For` es controlable por el cliente, así que solo se lee
     * cuando la IP directa (REMOTE_ADDR) corresponde a un proxy inverso de
     * confianza configurado en `trusted_proxies`. En cualquier otro caso se
     * usa REMOTE_ADDR, evitando que un atacante evada el bloqueo por IP del
     * login falsificando la cabecera.
     */
    public function getClientIp(): string
    {
        $remote = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($remote === '') {
            return '127.0.0.1';
        }

        if (!$this->isTrustedProxy($remote)) {
            return $remote;
        }

        $forwarded = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($forwarded === '') {
            return $remote;
        }

        // El proxy de confianza agrega la IP que vio al final de la cadena;
        // esa es la IP del cliente desde el punto de vista del proxy.
        $ips = array_values(array_filter(array_map('trim', explode(',', $forwarded))));

        return $ips === [] ? $remote : $ips[count($ips) - 1];
    }

    private function isTrustedProxy(string $ip): bool
    {
        foreach (config('trusted_proxies', []) as $trusted) {
            if ($trusted !== '' && $trusted === $ip) {
                return true;
            }
        }

        return false;
    }
}
