<?php

declare(strict_types=1);

namespace App\Services;

class RateLimiterService
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

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
                FROM login_attempt
                WHERE ip = :ip
                  AND success = false
                  AND attempted_at > (CURRENT_TIMESTAMP - INTERVAL '" . self::LOCKOUT_MINUTES . " minutes')";

        $row = dbFetchOne($sql, ['ip' => $ip]);
        return (int) ($row['t'] ?? 0);
    }

    public function countFailedAttemptsByEmail(string $email): int
    {
        if ($email === '') {
            return 0;
        }

        $sql = "SELECT COUNT(*) AS t
                FROM login_attempt
                WHERE LOWER(email) = LOWER(:email)
                  AND success = false
                  AND attempted_at > (CURRENT_TIMESTAMP - INTERVAL '" . self::LOCKOUT_MINUTES . " minutes')";

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

    public function recordSuccess(string $ip, string $email): void
    {
        dbQuery(
            "DELETE FROM login_attempt WHERE ip = :ip OR LOWER(email) = LOWER(:email)",
            ['ip' => $ip, 'email' => trim($email)]
        );
    }

    public function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        return (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    }
}
