<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\Database;
use PDOException;

/**
 * Acceso a los proyectos/servicios del laboratorio. Usada tanto por el
 * homepage (destacados) como por la página de listado completo.
 *
 * Intenta leer desde PostgreSQL primero; si la tabla todavía no existe
 * o la conexión no está disponible, usa contenido de respaldo para que
 * las páginas nunca se vean vacías o rotas.
 */
final class ProyectoRepository
{
    /** Los primeros N proyectos, para el homepage. */
    public function findFeatured(int $limit): array
    {
        $pdo = Database::connect();

        if ($pdo !== null) {
            try {
                $stmt = $pdo->prepare(
                    'SELECT id, titulo, descripcion, imagen_url FROM proyectos
                     ORDER BY orden ASC, created_at ASC LIMIT :limit'
                );
                $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
                $stmt->execute();
                $rows = $stmt->fetchAll();
                if ($rows !== false && count($rows) > 0) {
                    return $rows;
                }
            } catch (PDOException) {
                // Tabla aún no existe o falló la consulta: usar respaldo.
            }
        }

        return array_slice($this->fallback(), 0, $limit);
    }

    /** Todos los proyectos, para la página de listado. */
    public function findAll(): array
    {
        $pdo = Database::connect();

        if ($pdo !== null) {
            try {
                $stmt = $pdo->query(
                    'SELECT id, titulo, descripcion, imagen_url FROM proyectos
                     ORDER BY orden ASC, created_at ASC'
                );
                $rows = $stmt->fetchAll();
                if ($rows !== false && count($rows) > 0) {
                    return $rows;
                }
            } catch (PDOException) {
                // Tabla aún no existe o falló la consulta: usar respaldo.
            }
        }

        return $this->fallback();
    }

    private function fallback(): array
    {
        return [
            [
                'id' => 'plataforma-academica',
                'titulo' => 'Plataforma Académica',
                'descripcion' => 'Sistema web para la gestión de asignaturas, matrículas y seguimiento académico de estudiantes.',
                'imagen_url' => null,
            ],
            [
                'id' => 'gestion-laboratorios',
                'titulo' => 'Gestión de Laboratorios',
                'descripcion' => 'Reserva y control de uso de laboratorios, con reportes de disponibilidad en tiempo real.',
                'imagen_url' => null,
            ],
            [
                'id' => 'portal-vinculacion',
                'titulo' => 'Portal de Vinculación',
                'descripcion' => 'Espacio digital para conectar proyectos de la universidad con empresas y organizaciones de la región.',
                'imagen_url' => null,
            ],
            [
                'id' => 'app-terreno',
                'titulo' => 'Aplicación de Terreno',
                'descripcion' => 'Aplicación móvil para levantamiento de datos en terreno con sincronización sin conexión.',
                'imagen_url' => null,
            ],
            [
                'id' => 'observatorio-datos',
                'titulo' => 'Observatorio de Datos',
                'descripcion' => 'Tableros de visualización para el análisis de indicadores institucionales y regionales.',
                'imagen_url' => null,
            ],
            [
                'id' => 'sistema-tickets',
                'titulo' => 'Sistema de Soporte',
                'descripcion' => 'Mesa de ayuda interna con flujo de tickets, prioridades y métricas de atención.',
                'imagen_url' => null,
            ],
        ];
    }
}
