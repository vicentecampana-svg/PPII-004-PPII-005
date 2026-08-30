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
 * Panel de administración principal.
 */
final class AdminController extends Controller
{
    public function index(): void
    {
        $footer = (new FooterService())->getAll();
        $user = authUser();

        $stats = [
            'noticias'   => (new NewsService())->getAll(1, 1)['total'] ?? 0,
            'proyectos'  => (new ProjectService())->getAll(1, 1, true)['total'] ?? 0,
            'servicios'  => (new ServiceService())->getAll(1, 1, true)['total'] ?? 0,
            'staff'      => (new StaffService())->getAll(1, 1)['total'] ?? 0,
            'consultas'  => (new QueryService())->getAll(1, 1)['total'] ?? 0,
            'usuarios'   => (new UserService())->getAll(1, 1)['total'] ?? 0,
        ];

        $this->render('admin', [
            'pageTitle'       => 'Panel de Administración — SFL ULS Lab',
            'metaDescription' => 'Panel de control y administración del Software Factory Lab.',
            'user'            => $user,
            'stats'           => $stats,
            'enlacesFooter'   => $footer['links'],
            'contacto'        => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
        ]);
    }
}
