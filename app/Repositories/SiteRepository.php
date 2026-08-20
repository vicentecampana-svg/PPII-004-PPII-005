<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\Database;
use PDOException;

/**
 * Contenido compartido entre todas las páginas del sitio (footer):
 * enlaces de navegación y datos de contacto.
 */
final class SiteRepository
{
    public function enlacesFooter(): array
    {
        $pdo = Database::connect();

        if ($pdo !== null) {
            try {
                $stmt = $pdo->query(
                    'SELECT id, grupo, etiqueta, url FROM enlaces_footer ORDER BY grupo ASC, orden ASC'
                );
                $rows = $stmt->fetchAll();
                if ($rows !== false && count($rows) > 0) {
                    return $rows;
                }
            } catch (PDOException) {
                // Tabla aún no existe o falló la consulta: usar respaldo.
            }
        }

        return [
            ['id' => '4', 'grupo' => 'Contenido', 'etiqueta' => 'Noticias', 'url' => '/#noticias'],
            ['id' => '5', 'grupo' => 'Contenido', 'etiqueta' => 'Contacto', 'url' => '/#contacto'],
            ['id' => '6', 'grupo' => 'Contenido', 'etiqueta' => 'Iniciar sesión', 'url' => '#'],
            ['id' => '1', 'grupo' => 'Sitio', 'etiqueta' => 'Inicio', 'url' => '/'],
            ['id' => '2', 'grupo' => 'Sitio', 'etiqueta' => 'Proyectos', 'url' => '/proyectos'],
            ['id' => '3', 'grupo' => 'Sitio', 'etiqueta' => 'Staff', 'url' => '/#staff'],
        ];
    }

    public function contactoInfo(): array
    {
        $pdo = Database::connect();

        if ($pdo !== null) {
            try {
                $stmt = $pdo->query('SELECT address, email FROM footer_info LIMIT 1');
                $row = $stmt->fetch();
                if ($row !== false) {
                    return $row;
                }
            } catch (PDOException) {
                // Tabla aún no existe o falló la consulta: usar respaldo.
            }
        }

        return [
            'address' => 'La Serena, Chile',
            'email' => 'contacto@sfl.uls.cl',
        ];
    }
}
