<?php

declare(strict_types=1);

namespace Tests;

use App\Controllers\AdminController;
use App\Services\ProjectService;
use PHPUnit\Framework\TestCase;

final class AdminProjectsTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    public function testProyectosTabRendersProjectsListAndForm(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role_id'] = 1;
        $_SESSION['role_name'] = 'SuperAdmin';

        $user = authUser();
        $projects = [
            [
                'id'          => 10,
                'name'        => 'Plataforma Académica',
                'description' => 'Sistema web para la gestión de asignaturas.',
                'image'       => 'proyecto-1.jpg',
                'link'        => 'https://example.com',
                'active'      => true,
            ],
            [
                'id'          => 11,
                'name'        => 'Gestión de Laboratorios',
                'description' => 'Reserva y control de uso de laboratorios.',
                'image'       => null,
                'link'        => null,
                'active'      => false,
            ],
        ];
        $editingProject = null;
        $activeTab = 'proyectos';

        ob_start();
        require dirname(__DIR__) . '/app/Views/admin/proyectos.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Proyectos y servicios', $output);
        $this->assertStringContainsString('Plataforma Académica', $output);
        $this->assertStringContainsString('Gestión de Laboratorios', $output);
        $this->assertStringContainsString('Nuevo registro', $output);
        $this->assertStringContainsString('Título', $output);
        $this->assertStringContainsString('Descripción', $output);
        $this->assertStringContainsString('Imagen', $output);
        $this->assertStringContainsString('action="/admin/proyectos"', $output);
        $this->assertStringContainsString('action="/admin/proyectos/delete"', $output);
    }

    public function testEditingProjectPopulatesForm(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role_id'] = 1;
        $_SESSION['role_name'] = 'SuperAdmin';

        $projects = [];
        $editingProject = [
            'id'          => 42,
            'name'        => 'Proyecto de Prueba en Edición',
            'description' => 'Descripción editable.',
            'image'       => 'test.png',
            'link'        => 'https://test.cl',
            'active'      => true,
        ];
        $activeTab = 'proyectos';

        ob_start();
        require dirname(__DIR__) . '/app/Views/admin/proyectos.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Editar registro', $output);
        $this->assertStringContainsString('Proyecto de Prueba en Edición', $output);
        $this->assertStringContainsString('Descripción editable.', $output);
        $this->assertStringContainsString('value="42"', $output);
        $this->assertStringContainsString('Guardar cambios', $output);
    }
}
