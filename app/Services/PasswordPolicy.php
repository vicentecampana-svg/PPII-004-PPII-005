<?php

declare(strict_types=1);

namespace App\Services;

/**
 * RF-AUT-003 / RF-AUT-004 / RNF-SEC-004: política de contraseñas aplicable
 * a todos los puntos donde se define o cambia una contraseña (usuario nuevo,
 * cambio obligatorio, restablecimiento desde el panel y recuperación).
 *
 * Reglas: mínimo 12 caracteres y al menos una letra mayúscula, una minúscula,
 * un número y un carácter especial.
 */
final class PasswordPolicy
{
    public const MIN_LENGTH = 12;
    public const MAX_LENGTH = 120;

    /**
     * @return string[] Mensajes de incumplimiento; vacío si cumple la política.
     */
    public static function errors(string $password): array
    {
        $errors = [];

        $length = mb_strlen($password);
        if ($length < self::MIN_LENGTH) {
            $errors[] = 'La contraseña debe tener al menos ' . self::MIN_LENGTH . ' caracteres.';
        }
        if ($length > self::MAX_LENGTH) {
            $errors[] = 'La contraseña no puede superar ' . self::MAX_LENGTH . ' caracteres.';
        }
        if (!preg_match('/\p{Lu}/u', $password)) {
            $errors[] = 'La contraseña debe contener al menos una letra mayúscula.';
        }
        if (!preg_match('/\p{Ll}/u', $password)) {
            $errors[] = 'La contraseña debe contener al menos una letra minúscula.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un número.';
        }
        if (!preg_match('/[^\p{L}\p{N}]/u', $password)) {
            $errors[] = 'La contraseña debe contener al menos un carácter especial.';
        }

        return $errors;
    }

    public static function isValid(string $password): bool
    {
        return self::errors($password) === [];
    }

    /**
     * Texto de ayuda para los formularios (una sola fuente de verdad).
     */
    public static function hint(): string
    {
        return 'Mínimo ' . self::MIN_LENGTH
            . ' caracteres, con una mayúscula, una minúscula, un número y un carácter especial.';
    }
}
