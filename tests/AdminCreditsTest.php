<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class AdminCreditsTest extends TestCase
{
    protected function setUp(): void
    {
        sessionStart();
        $_SESSION = [];
    }

    public function testCreditsTabRendersListAndForm(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role_id'] = 1;
        $_SESSION['role_name'] = 'SuperAdmin';

        $creditsList = [
            [
                'id'    => 1,
                'name'  => 'Pedro Rojas',
                'role'  => 'Project Manager Officer',
                'email' => 'pedro.rojas@userena.cl',
                'orden' => 1,
            ],
            [
                'id'    => 2,
                'name'  => 'Vicente Campaña',
                'role'  => 'Desarrollador Backend',
                'email' => 'vicente.campana@userena.cl',
                'orden' => 2,
            ],
        ];
        $editingCredit = null;
        $activeTab = 'creditos';

        ob_start();
        require dirname(__DIR__) . '/app/Views/admin/creditos.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Integrantes de créditos', $output);
        $this->assertStringContainsString('Pedro Rojas', $output);
        $this->assertStringContainsString('Project Manager Officer', $output);
        $this->assertStringContainsString('pedro.rojas@userena.cl', $output);
        $this->assertStringContainsString('Vicente Campaña', $output);
        $this->assertStringContainsString('Nuevo integrante', $output);
        $this->assertStringContainsString('Nombre', $output);
        $this->assertStringContainsString('Cargo / Rol', $output);
        $this->assertStringContainsString('Correo de contacto', $output);
        $this->assertStringContainsString('action="/admin/creditos"', $output);
        $this->assertStringContainsString('action="/admin/creditos/delete"', $output);
    }

    public function testEditingCreditPopulatesForm(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role_id'] = 1;
        $_SESSION['role_name'] = 'SuperAdmin';

        $creditsList = [];
        $editingCredit = [
            'id'    => 5,
            'name'  => 'Scott Cawthon',
            'role'  => 'Arquitecto de Software',
            'email' => 'scott@example.com',
            'orden' => 3,
        ];
        $activeTab = 'creditos';

        ob_start();
        require dirname(__DIR__) . '/app/Views/admin/creditos.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Editar integrante', $output);
        $this->assertStringContainsString('Scott Cawthon', $output);
        $this->assertStringContainsString('Arquitecto de Software', $output);
        $this->assertStringContainsString('scott@example.com', $output);
        $this->assertStringContainsString('value="5"', $output);
        $this->assertStringContainsString('Guardar cambios', $output);
    }
}
