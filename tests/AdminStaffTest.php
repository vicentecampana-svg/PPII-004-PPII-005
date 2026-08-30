<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class AdminStaffTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    public function testStaffTabRendersListAndForm(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role_id'] = 1;
        $_SESSION['role_name'] = 'SuperAdmin';

        $staffList = [
            [
                'id'          => 1,
                'name'        => 'Pedro Rojas',
                'position'    => 'Project Manager Officer',
                'description' => 'Líder de proyectos.',
                'photo'       => null,
            ],
            [
                'id'          => 2,
                'name'        => 'Ramiro Hernesto',
                'position'    => 'Analista de Riesgos',
                'description' => 'Gestión y control de riesgos.',
                'photo'       => 'ramiro.jpg',
            ],
        ];
        $editingStaff = null;
        $activeTab = 'staff';

        ob_start();
        require dirname(__DIR__) . '/app/Views/admin/staff.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Miembros del staff', $output);
        $this->assertStringContainsString('Pedro Rojas', $output);
        $this->assertStringContainsString('Project Manager Officer', $output);
        $this->assertStringContainsString('Ramiro Hernesto', $output);
        $this->assertStringContainsString('Nuevo registro', $output);
        $this->assertStringContainsString('Nombre', $output);
        $this->assertStringContainsString('Cargo', $output);
        $this->assertStringContainsString('action="/admin/staff"', $output);
        $this->assertStringContainsString('action="/admin/staff/delete"', $output);
    }

    public function testEditingStaffPopulatesForm(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role_id'] = 1;
        $_SESSION['role_name'] = 'SuperAdmin';

        $staffList = [];
        $editingStaff = [
            'id'          => 5,
            'name'        => 'Scott Cawthonn',
            'position'    => 'Arquitecto de Software',
            'description' => 'Diseño de arquitectura de sistemas.',
            'photo'       => 'scott.jpg',
            'order'       => 2,
        ];
        $activeTab = 'staff';

        ob_start();
        require dirname(__DIR__) . '/app/Views/admin/staff.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Editar registro', $output);
        $this->assertStringContainsString('Scott Cawthonn', $output);
        $this->assertStringContainsString('Arquitecto de Software', $output);
        $this->assertStringContainsString('value="5"', $output);
        $this->assertStringContainsString('Guardar cambios', $output);
    }
}
