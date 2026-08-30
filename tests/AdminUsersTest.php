<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class AdminUsersTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    public function testUsuariosTabRendersUsersListAndRoleButtons(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'superadmin';
        $_SESSION['role_id'] = 1;
        $_SESSION['role_name'] = 'SuperAdmin';

        $usersList = [
            [
                'id'        => 1,
                'email'     => 'admin@gmail.com',
                'username'  => 'admin',
                'role_id'   => 1,
                'role_name' => 'superadmin',
            ],
            [
                'id'        => 2,
                'email'     => 'editor@gmail.com',
                'username'  => 'editor',
                'role_id'   => 3,
                'role_name' => 'editor',
            ],
            [
                'id'        => 3,
                'email'     => 'guest@gmail.com',
                'username'  => 'guest',
                'role_id'   => 4,
                'role_name' => 'invitado',
            ],
            [
                'id'        => 4,
                'email'     => 'redactor@gmail.com',
                'username'  => 'redactor',
                'role_id'   => 4,
                'role_name' => 'redactor',
            ],
        ];
        $rolesList = [
            ['id' => 1, 'name' => 'superadmin'],
            ['id' => 2, 'name' => 'admin'],
            ['id' => 3, 'name' => 'editor'],
            ['id' => 4, 'name' => 'redactor'],
        ];
        $activeTab = 'usuarios';

        ob_start();
        require dirname(__DIR__) . '/app/Views/admin/usuarios.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Usuarios y roles', $output);
        $this->assertStringContainsString('admin@gmail.com', $output);
        $this->assertStringContainsString('editor@gmail.com', $output);
        $this->assertStringContainsString('guest@gmail.com', $output);
        $this->assertStringContainsString('redactor@gmail.com', $output);
        $this->assertStringContainsString('Administrador', $output);
        $this->assertStringContainsString('Editor', $output);
        $this->assertStringContainsString('Redactor', $output);
        $this->assertStringContainsString('Invitado', $output);
        $this->assertStringContainsString('action="/admin/usuarios/role"', $output);
    }
}
