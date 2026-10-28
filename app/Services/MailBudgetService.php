<?php

declare(strict_types=1);

namespace App\Services;

/**
 * SEC-09: tope de correos por ámbito para proteger la cuota del proveedor
 * (p. ej. el plan de Resend).
 *
 * MAIL_DAILY_LIMIT es el máximo diario del plan y cada ámbito puede usar solo
 * un porcentaje de él (MAIL_SHARE_*). Así, aunque alguien automatice el envío
 * de un formulario público, nunca agota la cuota completa ni bloquea los
 * correos de otro ámbito, como la recuperación de contraseña.
 *
 * La ventana es móvil (últimas 24 horas), así que el tope se respeta en
 * cualquier periodo de un día, sin importar a qué hora reinicie el proveedor.
 * Cada envío se reserva en la tabla `mail_log` antes de salir; si la base de
 * datos no responde, no se envía (falla de forma segura para la cuota).
 */
class MailBudgetService
{
    public const SCOPE_CONTACT = 'contacto';
    public const SCOPE_CREDITS = 'creditos';
    public const SCOPE_PASSWORD_RESET = 'recuperacion';

    public const DEFAULT_DAILY_LIMIT = 100;
    private const WINDOW_HOURS = 24;

    /** Variable de entorno y porcentaje por defecto de cada ámbito. */
    private const SHARES = [
        self::SCOPE_CONTACT        => ['env' => 'MAIL_SHARE_CONTACT', 'default' => 20],
        self::SCOPE_CREDITS        => ['env' => 'MAIL_SHARE_CREDITS', 'default' => 20],
        self::SCOPE_PASSWORD_RESET => ['env' => 'MAIL_SHARE_PASSWORD_RESET', 'default' => 15],
    ];

    public function dailyLimit(): int
    {
        return $this->envInt('MAIL_DAILY_LIMIT', self::DEFAULT_DAILY_LIMIT);
    }

    /** Cantidad máxima de correos del ámbito en la ventana de 24 horas. */
    public function limitFor(string $scope): int
    {
        if (!isset(self::SHARES[$scope])) {
            throw new \InvalidArgumentException("Ámbito de correo desconocido: {$scope}");
        }

        $share = min(100, $this->envInt(self::SHARES[$scope]['env'], self::SHARES[$scope]['default']));

        return intdiv($this->dailyLimit() * $share, 100);
    }

    /**
     * Reserva un envío del ámbito. Devuelve el id de la reserva, o null si
     * el ámbito ya alcanzó su tope o no se pudo consultar la base de datos.
     */
    public function reserve(string $scope): ?int
    {
        $limit = $this->limitFor($scope);
        if ($limit <= 0) {
            return null;
        }

        $pdo = db();
        try {
            $pdo->beginTransaction();
            // Serializa las reservas del ámbito para que dos envíos simultáneos
            // no superen el tope.
            dbQuery('SELECT pg_advisory_xact_lock(hashtext(:key))', ['key' => 'mail_budget:' . $scope]);

            if ($this->countInWindow($scope) >= $limit) {
                $pdo->commit();
                return null;
            }

            $id = dbInsert('mail_log', ['scope' => $scope]);
            $pdo->commit();

            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[SFL ULS] No se pudo reservar el envío de correo: ' . $e->getMessage());
            return null;
        }
    }

    public function markDelivered(int $reservationId, bool $delivered): void
    {
        try {
            dbUpdate('mail_log', ['delivered' => $delivered], 'id = ?', [$reservationId]);
        } catch (\Throwable $e) {
            error_log('[SFL ULS] No se pudo actualizar el registro de correo: ' . $e->getMessage());
        }
    }

    public function countInWindow(string $scope): int
    {
        $row = dbFetchOne(
            "SELECT COUNT(*) AS t FROM mail_log
              WHERE scope = :scope
                AND sent_at > (NOW() - INTERVAL '" . self::WINDOW_HOURS . " hours')",
            ['scope' => $scope]
        );

        return (int) ($row['t'] ?? 0);
    }

    /** Fecha del último envío reservado del ámbito, o null si no hay. */
    public function lastReservationAt(string $scope): ?string
    {
        $row = dbFetchOne('SELECT MAX(sent_at) AS last FROM mail_log WHERE scope = :scope', ['scope' => $scope]);

        return $row['last'] ?? null;
    }

    private function envInt(string $name, int $default): int
    {
        $value = getenv($name);
        if ($value === false || trim($value) === '' || !ctype_digit(trim($value))) {
            return $default;
        }

        return (int) trim($value);
    }
}
