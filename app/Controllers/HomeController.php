<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Database;
use PDOException;

/**
 * Página de inicio: presentación del laboratorio, proyectos destacados,
 * staff y noticias recientes.
 *
 * Todas las secciones intentan leer desde PostgreSQL primero; si las
 * tablas todavía no existen (base de datos recién creada) o la conexión
 * no está disponible, se usa contenido de respaldo para que la página
 * nunca se vea vacía o rota.
 */
final class HomeController
{
    public function index(): void
    {
        $this->render('home', [
            'contenido' => $this->fetchContenido(),
            'proyectos' => $this->fetchProyectos(),
            'staff' => $this->fetchStaff(),
            'noticias' => $this->fetchNoticias(),
            'enlacesFooter' => $this->fetchEnlacesFooter(),
        ]);
    }

    private function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewsPath = dirname(__DIR__) . '/Views/';

        require $viewsPath . 'layout/header.php';
        require $viewsPath . $view . '.php';
        require $viewsPath . 'layout/footer.php';
    }

    private function fetchContenido(): array
    {
        $pdo = Database::connect();

        if ($pdo !== null) {
            try {
                $stmt = $pdo->prepare(
                    'SELECT sobre_titulo, sobre_texto, mision_titulo, mision_texto
                     FROM contenido_sitio WHERE clave = :clave LIMIT 1'
                );
                $stmt->execute(['clave' => 'home']);
                $row = $stmt->fetch();
                if ($row !== false) {
                    return $row;
                }
            } catch (PDOException) {
                // Tabla aún no existe o falló la consulta: usar respaldo.
            }
        }

        return [
            'sobre_titulo' => 'Sobre nosotros',
            'sobre_texto' => "Software Factory Lab (SFL) es el laboratorio de desarrollo de software de la "
                . "Universidad de La Serena, donde estudiantes y académicos trabajan junto a organizaciones "
                . "públicas y privadas en proyectos tecnológicos reales, combinando formación práctica con "
                . "estándares profesionales de la industria del software.",
            'mision_titulo' => 'Misión, visión y objetivos',
            'mision_texto' => "Nuestra misión es formar profesionales capaces de resolver problemas reales de "
                . "la industria y la comunidad mediante metodologías ágiles y ciclos de entrega cortos.\n"
                . "Nuestra visión es ser un referente regional en desarrollo de software con impacto social, "
                . "fortaleciendo la vinculación entre la universidad y su entorno.",
        ];
    }

    private function fetchProyectos(): array
    {
        $pdo = Database::connect();

        if ($pdo !== null) {
            try {
                $stmt = $pdo->query(
                    'SELECT id, titulo, descripcion, imagen_url FROM proyectos
                     ORDER BY orden ASC, created_at ASC LIMIT 4'
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
        ];
    }

    private function fetchStaff(): array
    {
        $pdo = Database::connect();

        if ($pdo !== null) {
            try {
                $stmt = $pdo->query(
                    'SELECT id, nombre, cargo, descripcion, imagen_url FROM staff
                     ORDER BY orden ASC, created_at ASC LIMIT 4'
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
            [
                'id' => 'fernando-flores',
                'nombre' => 'Fernando Flores Cortijo',
                'cargo' => 'Project Manager Officer',
                'descripcion' => 'Coordina la planificación de los proyectos del laboratorio y el vínculo con las contrapartes.',
                'imagen_url' => null,
            ],
            [
                'id' => 'luis-hernandez',
                'nombre' => 'Luis Hernández Comunez',
                'cargo' => 'Analista de Riesgos',
                'descripcion' => 'Responsable del análisis de riesgos, calidad y aseguramiento de los entregables de cada proyecto.',
                'imagen_url' => null,
            ],
            [
                'id' => 'bernardo-llanos',
                'nombre' => 'Bernardo Llanos',
                'cargo' => 'Arquitecto de Software',
                'descripcion' => 'Define la arquitectura técnica de las soluciones y acompaña al equipo de desarrollo.',
                'imagen_url' => null,
            ],
            [
                'id' => 'camila-rojas',
                'nombre' => 'Camila Rojas',
                'cargo' => 'Diseñadora UX/UI',
                'descripcion' => 'Diseña la experiencia de uso y los sistemas visuales de los productos digitales.',
                'imagen_url' => null,
            ],
        ];
    }

    private function fetchNoticias(): array
    {
        $pdo = Database::connect();

        if ($pdo !== null) {
            try {
                $stmt = $pdo->query(
                    'SELECT id, slug, titulo, resumen, imagen_url FROM noticias
                     WHERE publicada = true ORDER BY created_at DESC LIMIT 3'
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
            [
                'id' => 'nueva-ia-escaneo',
                'slug' => 'nueva-ia-escaneo',
                'titulo' => 'Estudiantes crean nueva IA de escaneo de animales',
                'resumen' => 'Un equipo del laboratorio presentó un modelo de visión computacional para el reconocimiento de fauna local.',
                'imagen_url' => null,
            ],
            [
                'id' => 'convenio-regional',
                'slug' => 'convenio-regional',
                'titulo' => 'Nuevo convenio de vinculación regional',
                'resumen' => 'La universidad firmó un acuerdo para desarrollar plataformas digitales junto a municipios de la región.',
                'imagen_url' => null,
            ],
            [
                'id' => 'practicas-profesionales',
                'slug' => 'practicas-profesionales',
                'titulo' => 'Se abren postulaciones a prácticas profesionales',
                'resumen' => 'El laboratorio ofrece cupos de práctica en desarrollo de software, datos y diseño de experiencia.',
                'imagen_url' => null,
            ],
        ];
    }

    private function fetchEnlacesFooter(): array
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
            ['id' => '1', 'grupo' => 'Sitio', 'etiqueta' => 'Inicio', 'url' => '/'],
            ['id' => '2', 'grupo' => 'Sitio', 'etiqueta' => 'Proyectos', 'url' => '/#proyectos'],
            ['id' => '3', 'grupo' => 'Sitio', 'etiqueta' => 'Staff', 'url' => '/#staff'],
            ['id' => '4', 'grupo' => 'Contenido', 'etiqueta' => 'Noticias', 'url' => '/#noticias'],
            ['id' => '5', 'grupo' => 'Contenido', 'etiqueta' => 'Contacto', 'url' => '#'],
            ['id' => '6', 'grupo' => 'Contenido', 'etiqueta' => 'Iniciar sesión', 'url' => '#'],
        ];
    }
}
