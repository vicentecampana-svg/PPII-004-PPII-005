<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Resuelve la URL pública de una imagen de contenido (proyecto, staff o
 * noticia). Si no hay ruta guardada en la base de datos, cae a una imagen
 * de respaldo local para que las tarjetas nunca se vean rotas.
 */
final class Media
{
    public static function url(?string $path, string $tipo): string
    {
        if ($path === null || $path === '') {
            return self::fallback($tipo);
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return '/assets/media/' . $path;
    }

    private static function fallback(string $tipo): string
    {
        return match ($tipo) {
            'proyecto' => '/assets/images/proyecto-1.jpg',
            'staff' => '/assets/images/staff-1.jpg',
            'noticia' => '/assets/images/noticia-1.jpg',
            default => '/assets/images/proyecto-1.jpg',
        };
    }
}
