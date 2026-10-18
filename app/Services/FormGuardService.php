<?php

declare(strict_types=1);

namespace App\Services;

/**
 * SEC-03: protección anti-spam para los formularios públicos.
 *
 * Combina dos defensas:
 *  - Honeypot: un campo oculto (`website`) que las personas no ven pero los
 *    bots completan. Si trae valor, la solicitud se descarta.
 *  - Límite por IP y por correo: como máximo MAX_SUBMISSIONS envíos por
 *    ventana de WINDOW_MINUTES, persistido en la tabla `form_submission`.
 *
 * Si la base de datos no está disponible, el límite falla de forma segura
 * (se permite el envío) para no romper el formulario; el error se registra.
 */
class FormGuardService
{
    public const MAX_SUBMISSIONS = 5;
    public const WINDOW_MINUTES = 15;

    /**
     * Verifica el honeypot del formulario.
     *
     * @param  array<string,mixed> $input Datos recibidos (POST o JSON).
     * @throws \InvalidArgumentException Si el honeypot fue completado.
     */
    public function assertNotHoneypot(array $input): void
    {
        if (trim((string) ($input['website'] ?? '')) !== '') {
            throw new \InvalidArgumentException('Solicitud rechazada.');
        }
    }

    /**
     * Verifica que el cliente no haya superado el límite de envíos.
     *
     * @throws \RuntimeException Si se superó el límite por IP o por correo.
     */
    public function assertAllowed(string $form, string $ip, string $email = ''): void
    {
        if ($this->isLimited($form, $ip, $email)) {
            throw new \RuntimeException('Demasiados envíos en poco tiempo. Inténtalo más tarde.');
        }
    }

    /** Registra un envío para el control de límite. */
    public function record(string $form, string $ip, string $email = ''): void
    {
        try {
            dbInsert('form_submission', [
                'form'  => $form,
                'ip'    => $ip,
                'email' => trim($email) !== '' ? mb_substr(trim($email), 0, 255) : null,
            ]);
        } catch (\Throwable $e) {
            error_log('[SFL ULS] No se pudo registrar el envío de formulario: ' . $e->getMessage());
        }
    }

    public function isLimited(string $form, string $ip, string $email = ''): bool
    {
        try {
            $ipCount = (int) (dbFetchOne(
                "SELECT COUNT(*) AS t FROM form_submission
                  WHERE form = :form AND ip = :ip
                    AND created_at > (NOW() - INTERVAL '" . self::WINDOW_MINUTES . " minutes')",
                ['form' => $form, 'ip' => $ip]
            )['t'] ?? 0);

            if ($ipCount >= self::MAX_SUBMISSIONS) {
                return true;
            }

            if (trim($email) === '') {
                return false;
            }

            $emailCount = (int) (dbFetchOne(
                "SELECT COUNT(*) AS t FROM form_submission
                  WHERE form = :form AND LOWER(email) = LOWER(:email)
                    AND created_at > (NOW() - INTERVAL '" . self::WINDOW_MINUTES . " minutes')",
                ['form' => $form, 'email' => trim($email)]
            )['t'] ?? 0);

            return $emailCount >= self::MAX_SUBMISSIONS;
        } catch (\Throwable $e) {
            error_log('[SFL ULS] No se pudo evaluar el límite de envíos: ' . $e->getMessage());
            return false;
        }
    }
}
