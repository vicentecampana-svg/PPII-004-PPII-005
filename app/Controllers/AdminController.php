<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FooterService;
use App\Services\NewsService;
use App\Services\ProjectService;
use App\Services\QueryService;
use App\Services\ServiceService;
use App\Services\StaffService;
use App\Services\UserService;

/**
 * Controlador principal del panel de administración.
 */
final class AdminController extends Controller
{
    private FooterService $footerService;
    private ProjectService $projectService;
    private ServiceService $serviceService;
    private StaffService $staffService;
    private NewsService $newsService;
    private QueryService $queryService;
    private UserService $userService;

    public function __construct(
        ?FooterService $footerService = null,
        ?ProjectService $projectService = null,
        ?ServiceService $serviceService = null,
        ?StaffService $staffService = null,
        ?NewsService $newsService = null,
        ?QueryService $queryService = null,
        ?UserService $userService = null
    ) {
        $this->footerService = $footerService ?? new FooterService();
        $this->projectService = $projectService ?? new ProjectService();
        $this->serviceService = $serviceService ?? new ServiceService();
        $this->staffService = $staffService ?? new StaffService();
        $this->newsService = $newsService ?? new NewsService();
        $this->queryService = $queryService ?? new QueryService();
        $this->userService = $userService ?? new UserService();
    }

    public function index(): void
    {
        $user = authUser();
        $tab = (string) ($_GET['tab'] ?? 'inicio');

        $stats = [
            'proyectos' => 0,
            'servicios' => 0,
            'staff'     => 0,
            'noticias'  => 0,
            'consultas' => 0,
            'usuarios'  => 0,
        ];

        try {
            $stats['proyectos'] = (int) (($this->projectService->getAll(1, 1, true))['total'] ?? 0);
            $stats['servicios'] = (int) (($this->serviceService->getAll(1, 1, true))['total'] ?? 0);
            $stats['staff']     = (int) (($this->staffService->getAll(1, 1))['total'] ?? 0);
            $stats['noticias']  = (int) (($this->newsService->getAll(1, 1))['total'] ?? 0);
            $stats['consultas'] = (int) (($this->queryService->getAll(1, 1))['total'] ?? 0);
            $stats['usuarios']  = (int) (($this->userService->getAll(1, 1))['total'] ?? 0);
        } catch (\Throwable) {
            // Degradar graciosamente si la base de datos o conexión no está disponible
        }

        $footer = ['links' => [], 'info' => ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl']];
        try {
            $footer = $this->footerService->getAll();
        } catch (\Throwable) {
            // Degradar graciosamente
        }

        $this->render('admin', [
            'pageTitle'       => 'Panel de Administración — SFL ULS Lab',
            'metaDescription' => 'Panel de administración y gestión de contenidos del Software Factory Lab.',
            'extraCss'        => ['/assets/css/admin.css'],
            'user'            => $user,
            'stats'           => $stats,
            'activeTab'       => $tab,
            'enlacesFooter'   => $footer['links'] ?? [],
            'contacto'        => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
        ]);
    }
}
