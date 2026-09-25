<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Error de validación: transporta los errores por campo sin depender de
 * getMessage(), de modo que su contenido nunca se expone tal cual al usuario.
 */
final class ValidationException extends \InvalidArgumentException
{
    private array $errors;

    /**
     * @param array<string, string> $errors Errores de validación campo => mensaje.
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct('Error de validación.');
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}