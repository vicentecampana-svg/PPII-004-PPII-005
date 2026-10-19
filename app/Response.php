<?php

declare(strict_types=1);

namespace App;

/**
 * CAL-04: respuestas HTTP/JSON en una clase pequeña.
 *
 * Reemplaza las funciones globales respJson()/respSuccess()/respError()...
 * de helpers.php.
 */
final class Response
{
    public static function json(mixed $data, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(mixed $data = null, int $statusCode = 200): never
    {
        self::json(['success' => true, 'data' => $data], $statusCode);
    }

    public static function created(mixed $data = null): never
    {
        self::success($data, 201);
    }

    public static function noContent(): never
    {
        http_response_code(204);
        exit;
    }

    public static function error(string $code, string $message, int $statusCode = 400, ?array $details = null): never
    {
        $payload = ['success' => false, 'error' => ['code' => $code, 'message' => $message]];
        if ($details !== null) {
            $payload['error']['details'] = $details;
        }
        self::json($payload, $statusCode);
    }

    public static function badRequest(string|array $message = 'Solicitud inválida'): never
    {
        self::error('BAD_REQUEST', is_string($message) ? $message : 'Solicitud inválida', 400, is_array($message) ? $message : null);
    }

    public static function unauthorized(string|array $message = 'No autenticado'): never
    {
        self::error('UNAUTHORIZED', is_string($message) ? $message : 'No autenticado', 401, is_array($message) ? $message : null);
    }

    public static function forbidden(string|array $message = 'Acceso denegado'): never
    {
        self::error('FORBIDDEN', is_string($message) ? $message : 'Acceso denegado', 403, is_array($message) ? $message : null);
    }

    public static function notFound(string|array $message = 'Recurso no encontrado'): never
    {
        self::error('NOT_FOUND', is_string($message) ? $message : 'Recurso no encontrado', 404, is_array($message) ? $message : null);
    }

    public static function unprocessable(array $errors): never
    {
        self::json(['success' => false, 'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Error de validación', 'details' => $errors]], 422);
    }

    public static function serverError(string $message = 'Error interno del servidor'): never
    {
        self::error('SERVER_ERROR', $message, 500);
    }
}
