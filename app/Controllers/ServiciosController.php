<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FooterService;
use App\Services\ServiceService;

/**
 * Página pública de Servicios del laboratorio.
 */
final class ServiciosController extends Controller
{
    private ServiceService $serviceService;
    private FooterService $footerService;

    public function __construct(?ServiceService $serviceService = null, ?FooterService $footerService = null)
    {
        $this->serviceService = $serviceService ?? new ServiceService();
        $this->footerService = $footerService ?? new FooterService();
    }

    public function index(): void
    {
        try {
            $footer = $this->footerService->getAll();
        } catch (\Throwable) {
            $footer = ['links' => [], 'info' => null];
        }

        try {
            $data = $this->serviceService->getAll(1, 100, false);
            $servicios = $data['items'] ?? [];
        } catch (\Throwable) {
            $servicios = [];
        }

        if (empty($servicios)) {
            $servicios = [
                [
                    'id' => 1,
                    'name' => 'Desarrollo de Software a la Medida',
                    'description' => 'Creamos soluciones digitales adaptadas a las necesidades específicas de tu organización, aplicando metodologías ágiles y altos estándares de calidad.',
                    'image' => 'proyecto-1.jpg',
                    'link' => '/contacto',
                    'active' => true,
                ],
                [
                    'id' => 2,
                    'name' => 'Consultoría en Tecnología',
                    'description' => 'Asesoría técnica estratégica para la transformación digital, arquitectura de sistemas y modernización tecnológica de empresas y organizaciones.',
                    'image' => 'proyecto-2.jpg',
                    'link' => '/contacto',
                    'active' => true,
                ],
                [
                    'id' => 3,
                    'name' => 'Capacitación en Programación',
                    'description' => 'Cursos, talleres y programas de formación técnica en desarrollo de software para estudiantes, profesionales y equipos de trabajo.',
                    'image' => 'proyecto-1.jpg',
                    'link' => '/contacto',
                    'active' => true,
                ],
            ];
        }

        $this->render('servicios', [
            'pageTitle' => 'Servicios — SFL ULS Lab',
            'metaDescription' => 'Conoce los servicios de desarrollo de software, consultoría tecnológica y capacitación que ofrece el Software Factory Lab de la Universidad de La Serena.',
            'servicios' => $servicios,
            'enlacesFooter' => $footer['links'] ?? [],
            'contacto' => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
        ]);
    }
}
