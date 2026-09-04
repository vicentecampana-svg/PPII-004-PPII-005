<?php

declare(strict_types=1);

namespace Tests;

use App\Controllers\AdminController;
use App\Controllers\LogoutController;
use App\Services\FooterService;
use App\Services\NewsService;
use App\Services\ProjectService;
use App\Services\QueryService;
use App\Services\ServiceService;
use App\Services\StaffService;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;

final class AdminControllerTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    public function testAdminControllerCanBeInstantiatedWithDependencies(): void
    {
        $footerService = $this->createMock(FooterService::class);
        $projectService = $this->createMock(ProjectService::class);
        $serviceService = $this->createMock(ServiceService::class);
        $staffService = $this->createMock(StaffService::class);
        $newsService = $this->createMock(NewsService::class);
        $queryService = $this->createMock(QueryService::class);
        $userService = $this->createMock(UserService::class);

        $controller = new AdminController(
            $footerService,
            $projectService,
            $serviceService,
            $staffService,
            $newsService,
            $queryService,
            $userService
        );

        $this->assertInstanceOf(AdminController::class, $controller);
    }

    public function testAdminViewRendersTabsAndUserInfoCorrectly(): void
    {
        // Configurar usuario simulado en sesión
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin_tester';
        $_SESSION['role_id'] = 1;
        $_SESSION['role_name'] = 'SuperAdmin';

        $user = authUser();
        $this->assertNotNull($user);
        $this->assertSame('admin_tester', $user['username']);
        $this->assertSame('SuperAdmin', $user['role_name']);

        $activeTab = 'proyectos';

        ob_start();
        require dirname(__DIR__) . '/app/Views/admin.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Panel de administración', $output);
        $this->assertStringContainsString('admin_tester', $output);
        $this->assertStringContainsString('SuperAdmin', $output);
        $this->assertStringContainsString('Proyectos', $output);
        $this->assertStringContainsString('Staff', $output);
        $this->assertStringContainsString('Noticias', $output);
        $this->assertStringContainsString('Sobre nosotros', $output);
        $this->assertStringContainsString('Footer', $output);
        $this->assertStringContainsString('Usuarios y roles', $output);
    }

    public function testRedactorRoleDoesNotSeeRestrictedTabs(): void
    {
        $_SESSION['user_id'] = 2;
        $_SESSION['username'] = 'redactor_user';
        $_SESSION['role_id'] = 3;
        $_SESSION['role_name'] = 'Redactor';

        $user = authUser();
        $activeTab = 'noticias';

        ob_start();
        require dirname(__DIR__) . '/app/Views/admin.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Noticias', $output);
        $this->assertStringNotContainsString('Usuarios y roles', $output);
        $this->assertStringNotContainsString('Footer', $output);
    }

    public function testLogoutClearsSession(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';

        $this->assertTrue(authCheck());
        authLogout();
        $this->assertFalse(authCheck());
    }
}
