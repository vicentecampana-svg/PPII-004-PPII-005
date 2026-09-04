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
}
