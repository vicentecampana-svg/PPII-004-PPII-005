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
    public const MISMATCH_MESSAGE = 'Las contraseñas no coinciden.';

    /**
     * Reglas de composición: [patrón, mensaje]. Los patrones usan una sintaxis
     * válida tanto en PCRE como en RegExp de JavaScript (ambos con flag "u"),
     * para que la validación en el navegador sea la misma que en el servidor.
     */
    private const COMPOSITION_RULES = [
        ['\p{Lu}', 'La contraseña debe contener al menos una letra mayúscula.'],
        ['\p{Ll}', 'La contraseña debe contener al menos una letra minúscula.'],
        ['[0-9]', 'La contraseña debe contener al menos un número.'],
        ['[^\p{L}\p{N}]', 'La contraseña debe contener al menos un carácter especial.'],
    ];

    /**
     * @return string[] Mensajes de incumplimiento; vacío si cumple la política.
     */
    public static function errors(string $password): array
    {
        $errors = [];

        $length = mb_strlen($password);
        if ($length < self::MIN_LENGTH) {
            $errors[] = self::minLengthMessage();
        }
        if ($length > self::MAX_LENGTH) {
            $errors[] = self::maxLengthMessage();
        }
        foreach (self::COMPOSITION_RULES as [$pattern, $message]) {
            if (!preg_match('/' . $pattern . '/u', $password)) {
                $errors[] = $message;
            }
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

    /**
     * Configuración que consume public/assets/js/password-policy.js para
     * validar en el navegador con las mismas reglas y mensajes que errors().
     *
     * @return array{minLength: int, maxLength: int, minLengthMessage: string, maxLengthMessage: string,
     *               rules: list<array{pattern: string, message: string}>, mismatchMessage: string}
     */
    public static function clientConfig(): array
    {
        return [
            'minLength'        => self::MIN_LENGTH,
            'maxLength'        => self::MAX_LENGTH,
            'minLengthMessage' => self::minLengthMessage(),
            'maxLengthMessage' => self::maxLengthMessage(),
            'rules'            => array_map(
                static fn(array $rule): array => ['pattern' => $rule[0], 'message' => $rule[1]],
                self::COMPOSITION_RULES
            ),
            'mismatchMessage'  => self::MISMATCH_MESSAGE,
        ];
    }

    /**
     * Atributo data-* listo para imprimir en el <form> que usa la validación
     * en el navegador.
     */
    public static function clientConfigAttribute(): string
    {
        return 'data-password-policy="'
            . htmlspecialchars((string) json_encode(self::clientConfig(), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8')
            . '"';
    }

    private static function minLengthMessage(): string
    {
        return 'La contraseña debe tener al menos ' . self::MIN_LENGTH . ' caracteres.';
    }

    private static function maxLengthMessage(): string
    {
        return 'La contraseña no puede superar ' . self::MAX_LENGTH . ' caracteres.';
    }
}
