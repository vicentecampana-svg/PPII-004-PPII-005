<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ProyectoRepository;
use App\Repositories\SiteRepository;

/**
 * Listado completo de proyectos y servicios del laboratorio.
 */
final class ProyectosController extends Controller
{
    public function index(): void
    {
        $this->render('proyectos', [
            'pageTitle' => 'Proyectos y Servicios — SFL ULS Lab',
            'metaDescription' => 'Conoce los proyectos y servicios desarrollados por el Software Factory Lab de la Universidad de La Serena.',
            'proyectos' => (new ProyectoRepository())->findAll(),
            'enlacesFooter' => (new SiteRepository())->enlacesFooter(),
            'contacto' => (new SiteRepository())->contactoInfo(),
        ]);
    }
}
