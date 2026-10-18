<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FooterService;
use App\Services\StaffService;

/**
 * Página pública de Miembros del Staff.
 */
final class StaffWebController extends Controller
{
    private StaffService $staffService;
    private FooterService $footerService;

    public function __construct(?StaffService $staffService = null, ?FooterService $footerService = null)
    {
        $this->staffService = $staffService ?? new StaffService();
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
            $data = $this->staffService->getAll(1, 100);
            $staff = $data['items'] ?? [];
        } catch (\Throwable) {
            $staff = [];
        }

        if (empty($staff)) {
            $staff = [
                [
                    'id' => 1,
                    'name' => 'Carlos Méndez',
                    'position' => 'Director del Laboratorio',
                    'photo' => 'staff-1.jpg',
                    'description' => 'Ingeniero en Computación, Magíster en Informática. Líder del Tech Hub ULS.',
                ],
                [
                    'id' => 2,
                    'name' => 'Ana Sofía Riquelme',
                    'position' => 'Coordinadora de Proyectos',
                    'photo' => 'staff-1.jpg',
                    'description' => 'Ingeniera en Computación con experiencia en gestión de desarrollo de software.',
                ],
                [
                    'id' => 3,
                    'name' => 'Pedro Contreras',
                    'position' => 'Desarrollador Full Stack',
                    'photo' => 'staff-1.jpg',
                    'description' => 'Especialista en PHP, JavaScript y bases de datos PostgreSQL.',
                ],
                [
                    'id' => 4,
                    'name' => 'Pedro Rojas',
                    'position' => 'Project Manager Officer',
                    'photo' => 'staff-1.jpg',
                    'description' => 'Coordina la planificación de los proyectos del laboratorio y el vínculo con las contrapartes.',
                ],
                [
                    'id' => 5,
                    'name' => 'Ramiro Hernesto',
                    'position' => 'Analista de Riesgos y Todologo',
                    'photo' => 'staff-1.jpg',
                    'description' => 'Responsable del análisis de riesgos y acompañamiento integral al equipo.',
                ],
                [
                    'id' => 6,
                    'name' => 'Scott Cawthonn',
                    'position' => 'Arquitecto de Software',
                    'photo' => 'staff-1.jpg',
                    'description' => 'Define la arquitectura técnica de las soluciones y acompaña al equipo de desarrollo.',
                ],
            ];
        }

        $this->render('staff', [
            'pageTitle' => 'Miembros del Staff — SFL ULS Lab',
            'metaDescription' => 'Conoce al equipo de profesionales y desarrolladores del Software Factory Lab de la Universidad de La Serena.',
            'staff' => $staff,
            'enlacesFooter' => $footer['links'] ?? [],
            'contacto' => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
        ]);
    }
}
