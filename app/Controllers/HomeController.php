<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FooterService;
use App\Services\NewsService;
use App\Services\ProjectService;
use App\Services\StaffService;

/**
 * Página de inicio: presentación del laboratorio, proyectos destacados,
 * staff y noticias recientes.
 *
 * Los datos vienen de los mismos Services que usa la API (Backend): las
 * páginas públicas se comportan como un visitante anónimo del API
 * (sin sesión ⇒ solo se listan proyectos/noticias activos y publicados).
 * Solo la sección "Sobre nosotros" tiene contenido de respaldo hardcodeado
 * (si `contenido_sitio` aún no tiene una fila 'home'); las demás secciones
 * simplemente no muestran tarjetas si la tabla está vacía.
 */
final class HomeController extends Controller
{
    public function index(): void
    {
        try {
            $footer = (new FooterService())->getAll();
        } catch (\Throwable) {
            $footer = ['links' => [], 'info' => null, 'contenido' => null];
        }

        try {
            $proyectos = $this->proyectos();
        } catch (\Throwable) {
            $proyectos = [];
        }

        try {
            $staff = $this->staff();
        } catch (\Throwable) {
            $staff = [];
        }

        try {
            $noticias = $this->noticias();
        } catch (\Throwable) {
            $noticias = [];
        }

        $this->render('home', [
            'contenido'     => $this->contenido($footer['contenido']),
            'proyectos'     => $proyectos,
            'staff'         => $staff,
            'noticias'      => $noticias,
            'enlacesFooter' => $footer['links'],
            'contacto'      => $footer['info'] ?? [
                'address'          => 'La Serena, Chile',
                'email'            => 'contacto@sfl.uls.cl',
                'copyright_text'   => '© SFL. Todos los derechos reservados',
                'social_linkedin'  => '#',
                'social_twitter'   => '#',
                'social_instagram' => '#',
            ],
        ]);
    }

    private function contenido(?array $row): array
    {
        if ($row !== null) {
            return $row;
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

    /** Los primeros 4 proyectos activos, para el homepage. */
    private function proyectos(): array
    {
        try {
            $items = (new ProjectService())->getAll(1, 4, false)['items'];
        } catch (\Throwable) {
            $items = [];
        }

        if ($items === []) {
            $items = [
                ['name' => 'Sistema de Gestión Académica', 'description' => 'Plataforma para gestión de notas y asistencia universitaria.', 'image' => 'proyecto-1.jpg'],
                ['name' => 'App de Seguimiento de Salud', 'description' => 'Aplicación móvil para monitoreo de signos vitales.', 'image' => 'proyecto-2.jpg'],
                ['name' => 'Portal de Vinculación con el Medio', 'description' => 'Sitio web que conecta proyectos estudiantiles con la comunidad.', 'image' => 'proyecto-1.jpg'],
            ];
        }

        return array_map(static fn(array $p): array => [
            'titulo' => $p['name'],
            'descripcion' => $p['description'] ?? '',
            'imagen_url' => $p['image'] ?? null,
        ], $items);
    }

    /** Los primeros 4 miembros del staff. */
    private function staff(): array
    {
        try {
            $items = (new StaffService())->getAll(1, 4)['items'];
        } catch (\Throwable) {
            $items = [];
        }

        if ($items === []) {
            $items = [
                ['name' => 'Carlos Méndez', 'position' => 'Director del Laboratorio', 'description' => 'Ingeniero en Computación, Magíster en Informática. Líder del Tech Hub ULS.', 'photo' => 'staff-1.jpg'],
                ['name' => 'Ana Sofía Riquelme', 'position' => 'Coordinadora de Proyectos', 'description' => 'Ingeniera en Computación con experiencia en gestión de desarrollo.', 'photo' => 'staff-1.jpg'],
                ['name' => 'Pedro Contreras', 'position' => 'Desarrollador Full Stack', 'description' => 'Especialista en PHP, JavaScript y bases de datos PostgreSQL.', 'photo' => 'staff-1.jpg'],
                ['name' => 'Pedro Rojas', 'position' => 'Project Manager Officer', 'description' => 'Coordina la planificación de los proyectos del laboratorio.', 'photo' => 'staff-1.jpg'],
            ];
        }

        return array_map(static fn(array $m): array => [
            'nombre' => $m['name'],
            'cargo' => $m['position'] ?? '',
            'descripcion' => $m['description'] ?? '',
            'imagen_url' => $m['photo'] ?? null,
        ], $items);
    }

    /** Las últimas 3 noticias publicadas. */
    private function noticias(): array
    {
        try {
            $items = (new NewsService())->getPublished(1, 3)['items'];
        } catch (\Throwable) {
            $items = [];
        }

        if ($items === []) {
            $items = [
                ['id' => 1, 'title' => 'Estudiantes crean nueva IA de escaneo de animales', 'subtitle' => 'Un equipo del laboratorio presentó un modelo de visión computacional para el reconocimiento de fauna local.', 'content' => '', 'image' => 'noticia-1.jpg'],
                ['id' => 2, 'title' => 'Nuevo convenio de vinculación regional', 'subtitle' => 'La universidad firmó un acuerdo para desarrollar plataformas digitales junto a municipios de la región.', 'content' => '', 'image' => 'noticia-1.jpg'],
                ['id' => 3, 'title' => 'Se abren postulaciones a prácticas profesionales', 'subtitle' => 'El laboratorio ofrece cupos de práctica en desarrollo de software, datos y diseño de experiencia.', 'content' => '', 'image' => 'noticia-1.jpg'],
            ];
        }

        return array_map(static fn(array $n): array => [
            'id' => $n['id'],
            'titulo' => $n['title'],
            // La tabla `news` no tiene un campo de resumen dedicado: se usa
            // el subtítulo, y si no hay, se recorta el contenido.
            'resumen' => $n['subtitle'] ?: mb_strimwidth(strip_tags($n['content'] ?? ''), 0, 160, '…'),
            'imagen_url' => $n['image'] ?? null,
        ], $items);
    }
}
