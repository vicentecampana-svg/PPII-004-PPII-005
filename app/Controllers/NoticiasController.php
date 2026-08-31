<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FooterService;
use App\Services\NewsService;
use App\Services\TagService;

/**
 * Página pública de noticias y buscador.
 */
final class NoticiasController extends Controller
{
    private NewsService $newsService;
    private TagService $tagService;
    private FooterService $footerService;

    public function __construct(?NewsService $newsService = null, ?TagService $tagService = null, ?FooterService $footerService = null)
    {
        $this->newsService = $newsService ?? new NewsService();
        $this->tagService = $tagService ?? new TagService();
        $this->footerService = $footerService ?? new FooterService();
    }

    public function index(): void
    {
        try {
            $footer = $this->footerService->getAll();
        } catch (\Throwable) {
            $footer = ['links' => [], 'info' => null];
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 12;
        $query = trim((string) ($_GET['q'] ?? $_GET['search'] ?? ''));
        $tagId = isset($_GET['tag_id']) && is_numeric($_GET['tag_id']) ? (int) $_GET['tag_id'] : null;

        try {
            $data = $this->newsService->getPublished($page, $perPage, $query, $tagId);
        } catch (\Throwable) {
            $data = ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage, 'total_pages' => 0];
        }

        try {
            $tags = $this->tagService->getAll();
        } catch (\Throwable) {
            $tags = [];
        }

        $noticias = $data['items'];

        if (empty($noticias) && $query === '' && $tagId === null) {
            $noticias = $this->sampleNews();
            $data['total'] = count($noticias);
            $data['total_pages'] = 1;
        } elseif ($query !== '' && empty($noticias)) {
            // Si la base de datos está offline y se busca en datos de muestra
            $sample = $this->sampleNews();
            $filtered = array_filter($sample, function ($item) use ($query) {
                return stripos($item['title'], $query) !== false
                    || stripos($item['subtitle'] ?? '', $query) !== false
                    || stripos($item['content'] ?? '', $query) !== false;
            });
            if (!empty($filtered)) {
                $noticias = array_values($filtered);
                $data['total'] = count($noticias);
            }
        }

        $this->render('noticias', [
            'pageTitle'       => 'Noticias — SFL ULS Lab',
            'metaDescription' => 'Noticias, eventos y novedades del Software Factory Lab de la Universidad de La Serena.',
            'noticias'        => $noticias,
            'pagination'      => [
                'total'       => $data['total'] ?? 0,
                'page'        => $data['page'] ?? $page,
                'perPage'     => $data['per_page'] ?? $perPage,
                'totalPages'  => $data['total_pages'] ?? 0,
            ],
            'tags'            => $tags,
            'selectedTagId'   => $tagId,
            'query'           => $query,
            'enlacesFooter'   => $footer['links'] ?? [],
            'contacto'        => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
        ]);
    }

    public function show(string|int $id): void
    {
        try {
            $footer = $this->footerService->getAll();
        } catch (\Throwable) {
            $footer = ['links' => [], 'info' => null];
        }

        $newsId = (int) $id;

        try {
            $noticia = $this->newsService->getPublishedById($newsId);
        } catch (\Throwable) {
            $noticia = null;
        }

        if (!$noticia) {
            http_response_code(404);
            header('Location: /noticias');
            exit;
        }

        // Obtener otras noticias relevantes
        try {
            $allPublished = $this->newsService->getPublished(1, 6)['items'];
        } catch (\Throwable) {
            $allPublished = $this->sampleNews();
        }

        $otrasNoticias = array_values(array_filter($allPublished, static function ($item) use ($newsId) {
            return (int) ($item['id'] ?? 0) !== $newsId;
        }));
        $otrasNoticias = array_slice($otrasNoticias, 0, 3);

        $this->render('noticia', [
            'pageTitle'       => ($noticia['title'] ?? 'Noticia') . ' — SFL ULS Lab',
            'metaDescription' => ($noticia['subtitle'] ?? '') ?: mb_strimwidth(strip_tags((string) ($noticia['content'] ?? '')), 0, 160, '…'),
            'noticia'         => $noticia,
            'otrasNoticias'   => $otrasNoticias,
            'enlacesFooter'   => $footer['links'] ?? [],
            'contacto'        => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
        ]);
    }

    private function sampleNews(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Estudiantes crean nueva IA de escaneo de animales',
                'subtitle' => 'Un equipo del laboratorio presentó un modelo de visión computacional para el reconocimiento de fauna local.',
                'content' => "Un equipo de estudiantes de la carrera de Ingeniería en Computación del Software Factory Lab desarrolló un innovador modelo de inteligencia artificial y visión computacional enfocado en el escaneo y reconocimiento automático de especies de fauna autóctona de la región de Coquimbo.\n\nEl proyecto, liderado por estudiantes de último año, utiliza redes neuronales convolucionales de última generación y fue entrenado con miles de registros fotográficos de la fauna local. Esta herramienta permitirá a investigadores, guardaparques y entidades ambientales registrar avistamientos y monitorear la biodiversidad con mayor precisión.\n\nLos estudiantes destacaron que la experiencia en el laboratorio les permitió aplicar conocimientos teóricos en un proyecto con impacto directo en la conservación del medio ambiente.",
                'image' => 'noticia-1.jpg',
                'publication_date' => '2026-08-20 10:00:00',
                'author' => 'Periodista 1',
                'tag' => 'Inteligencia Artificial',
                'tags' => [['id' => 2, 'name' => 'Inteligencia Artificial']],
            ],
            [
                'id' => 2,
                'title' => 'Nuevo convenio de vinculación regional',
                'subtitle' => 'La universidad firmó un acuerdo para desarrollar plataformas digitales junto a municipios de la región.',
                'content' => "La Universidad de La Serena, a través de su Software Factory Lab (SFL), concretó un importante acuerdo de colaboración y vinculación con diversas municipalidades de la Región de Coquimbo para el diseño, desarrollo e implementación de soluciones tecnológicas y plataformas ciudadanas.\n\nEl convenio permitirá que los equipos de estudiantes y docentes del laboratorio participen activamente en cada etapa de los proyectos de transformación digital comunal, abordando desafíos como la digitalización de trámites, portales de atención ciudadana y sistemas de gestión de emergencias locales.\n\nAutoridades universitarias y comunales valoraron esta alianza estratégica, que fortalece la formación profesional de excelencia y genera valor público para la comunidad.",
                'image' => 'noticia-1.jpg',
                'publication_date' => '2026-08-22 11:30:00',
                'author' => 'Periodista 2',
                'tag' => 'Vinculación',
                'tags' => [['id' => 1, 'name' => 'Desarrollo Web']],
            ],
            [
                'id' => 3,
                'title' => 'Se abren postulaciones a prácticas profesionales',
                'subtitle' => 'El laboratorio ofrece cupos de práctica en desarrollo de software, datos y diseño de experiencia.',
                'content' => "El Software Factory Lab (SFL) de la Universidad de La Serena dio inicio a su convocatoria de postulaciones para prácticas profesionales del presente semestre académico.\n\nSe ofrecen cupos en áreas de desarrollo Frontend, Backend, ingeniería de datos, control de calidad (QA) y diseño UX/UI. Los practicantes integrarán células de trabajo interdisciplinarias con mentorías técnicas continuas.\n\nLas postulaciones estarán abiertas a través del portal institucional para todos los estudiantes de carreras afines que cumplan con los requisitos de práctica.",
                'image' => 'noticia-1.jpg',
                'publication_date' => '2026-08-25 09:15:00',
                'author' => 'Periodista 1',
                'tag' => 'Oportunidades',
                'tags' => [['id' => 5, 'name' => 'DevOps']],
            ],
            [
                'id' => 4,
                'title' => 'Jornada abierta de proyectos del laboratorio',
                'subtitle' => 'Demostración presencial y virtual de los principales prototipos de software desarrollados durante el año.',
                'content' => "Se llevó a cabo con gran éxito la jornada anual de demostración de proyectos de SFL ULS, donde equipos multidisciplinarios expusieron sus avances y prototipos ante la comunidad académica y representantes de la industria tecnológica regional.",
                'image' => 'noticia-1.jpg',
                'publication_date' => '2026-08-26 15:00:00',
                'author' => 'Periodista 2',
                'tag' => 'Eventos',
                'tags' => [['id' => 1, 'name' => 'Desarrollo Web']],
            ],
            [
                'id' => 5,
                'title' => 'Taller de calidad de software para estudiantes',
                'subtitle' => 'Capacitación intensiva en pruebas automatizadas, integración continua y buenas prácticas de código.',
                'content' => "Más de 40 estudiantes participaron en el taller práctico de pruebas automatizadas y aseguramiento de la calidad de software organizado por los ingenieros y redactoras del laboratorio.",
                'image' => 'noticia-1.jpg',
                'publication_date' => '2026-08-27 16:30:00',
                'author' => 'Periodista 1',
                'tag' => 'Capacitación',
                'tags' => [['id' => 5, 'name' => 'DevOps']],
            ],
            [
                'id' => 6,
                'title' => 'Se habilita un nuevo espacio de trabajo',
                'subtitle' => 'Infraestructura tecnológica renovada con nuevas estaciones de trabajo y servidores dedicados.',
                'content' => "El laboratorio inauguró nuevas instalaciones equipadas con conectividad de alta velocidad y servidores de prueba para acelerar el desarrollo y despliegue de soluciones tecnológicas.",
                'image' => 'noticia-1.jpg',
                'publication_date' => '2026-08-28 12:00:00',
                'author' => 'Periodista 2',
                'tag' => 'Infraestructura',
                'tags' => [['id' => 4, 'name' => 'Cloud Computing']],
            ],
        ];
    }
}
