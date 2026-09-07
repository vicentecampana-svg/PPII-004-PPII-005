<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FooterService;
use App\Services\ProjectService;

/**
 * Listado completo de proyectos y servicios del laboratorio.
 *
 * Usa el mismo ProjectService que la API, en modo visitante anónimo
 * (solo proyectos activos).
 */
final class ProyectosController extends Controller
{
    public function index(): void
    {
        try {
            $footer = (new FooterService())->getAll();
        } catch (\Throwable) {
            $footer = ['links' => [], 'info' => null];
        }

        try {
            $items = (new ProjectService())->getAll(1, 500, false)['items'];
        } catch (\Throwable) {
            $items = [];
        }

        if (empty($items)) {
            $items = [
                [
                    'name' => 'Sistema de Gestión Académica',
                    'description' => 'Plataforma para gestión de notas y asistencia universitaria.',
                    'image' => 'proyecto-1.jpg',
                ],
                [
                    'name' => 'App de Seguimiento de Salud',
                    'description' => 'Aplicación móvil para monitoreo de signos vitales.',
                    'image' => 'proyecto-2.jpg',
                ],
                [
                    'name' => 'Portal de Vinculación con el Medio',
                    'description' => 'Sitio web que conecta proyectos estudiantiles con la comunidad.',
                    'image' => 'proyecto-1.jpg',
                ],
            ];
        }

        $proyectos = array_map(static fn(array $p): array => [
            'id' => $p['id'] ?? null,
            'titulo' => $p['name'],
            'descripcion' => $p['description'] ?? '',
            'imagen_url' => $p['image'] ?? null,
        ], $items);

        $this->render('proyectos', [
            'pageTitle' => 'Proyectos — SFL ULS Lab',
            'metaDescription' => 'Conoce los proyectos desarrollados por el Software Factory Lab de la Universidad de La Serena.',
            'proyectos' => $proyectos,
            'enlacesFooter' => $footer['links'] ?? [],
            'contacto' => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
        ]);
    }

    /**
     * Página de detalle de un proyecto individual.
     * GET /proyectos/{id}
     */
    public function show(int $id): void
    {
        try {
            $footer = (new FooterService())->getAll();
        } catch (\Throwable) {
            $footer = ['links' => [], 'info' => null];
        }

        $service = new ProjectService();

        try {
            $item = $service->getById($id);
        } catch (\Throwable) {
            $item = null;
        }

        // Los visitantes anónimos solo pueden ver proyectos activos.
        if (!$item || !$item['active']) {
            http_response_code(404);
            header('Location: /proyectos');
            exit;
        }

        $proyecto = [
            'id' => $item['id'],
            'titulo' => $item['name'],
            'descripcion' => $item['description'] ?? '',
            'imagen_url' => $item['image'] ?? null,
            'link' => $item['link'] ?? null,
        ];

        try {
            $otros = $service->getAll(1, 6, false)['items'];
        } catch (\Throwable) {
            $otros = [];
        }

        $otrosProyectos = array_values(array_filter($otros, static fn(array $p): bool => (int) $p['id'] !== $id));
        $otrosProyectos = array_slice(array_map(static fn(array $p): array => [
            'id' => $p['id'],
            'titulo' => $p['name'],
            'descripcion' => $p['description'] ?? '',
            'imagen_url' => $p['image'] ?? null,
        ], $otrosProyectos), 0, 3);

        $this->render('proyecto', [
            'pageTitle' => $proyecto['titulo'] . ' — SFL ULS Lab',
            'metaDescription' => mb_strimwidth(strip_tags((string) $proyecto['descripcion']), 0, 160, '…'),
            'proyecto' => $proyecto,
            'otrosProyectos' => $otrosProyectos,
            'enlacesFooter' => $footer['links'] ?? [],
            'contacto' => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
        ]);
    }
}
