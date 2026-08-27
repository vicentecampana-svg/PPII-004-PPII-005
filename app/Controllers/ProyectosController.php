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
        $footer = (new FooterService())->getAll();
        // ProjectService no tiene un método "sin paginar"; 500 cubre
        // cualquier cantidad realista de proyectos para este listado.
        $items = (new ProjectService())->getAll(1, 500, false)['items'];

        $proyectos = array_map(static fn(array $p): array => [
            'titulo' => $p['name'],
            'descripcion' => $p['description'] ?? '',
            'imagen_url' => $p['image'] ?? null,
        ], $items);

        $this->render('proyectos', [
            'pageTitle' => 'Proyectos y Servicios — SFL ULS Lab',
            'metaDescription' => 'Conoce los proyectos y servicios desarrollados por el Software Factory Lab de la Universidad de La Serena.',
            'proyectos' => $proyectos,
            'enlacesFooter' => $footer['links'],
            'contacto' => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
        ]);
    }
}
