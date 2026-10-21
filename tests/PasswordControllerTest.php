<?php

declare(strict_types=1);

namespace Tests;

use App\Controllers\PasswordController;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;

/**
 * Tests para PasswordController y verificación de BUG-01 (findWithPasswordById).
 */
final class PasswordControllerTest extends TestCase
{
    private UserRepository $userRepo;
    private UserService $userService;
    private AuditService $auditService;
    private ?string $redirectedTo = null;

    protected function setUp(): void
    {
        sessionStart();
        $_SESSION = [];
        $_POST = [];
        $this->redirectedTo = null;

        $this->userRepo = $this->createMock(UserRepository::class);
        $this->userService = $this->createMock(UserService::class);
        $this->auditService = $this->createMock(AuditService::class);
    }

    private function createController(): PasswordController
    {
        return new PasswordController(
            $this->userService,
            $this->userRepo,
            $this->auditService,
            function (string $url): void {
                $this->redirectedTo = $url;
            }
        );
    }

    private function authenticateUser(int $id = 42, string $username = 'test.user'): void
    {
        $_SESSION['user_id'] = $id;
        $_SESSION['username'] = $username;
        $_SESSION['role_id'] = 2;
        $_SESSION['role_name'] = 'Editor';
        $_SESSION['must_change_password'] = true;
        $_SESSION['_last_activity'] = time();
    }

    public function testSubmitSucceedsWithValidCredentialsAndUsesFindWithPasswordById(): void
    {
        $this->authenticateUser(42, 'test.user');

        $currentPlain = 'CurrentValidPass123!';
        $currentHash = password_hash($currentPlain, PASSWORD_DEFAULT);
        $newPlain = 'NewSecurePassword456!';

        $_POST = [
            'current_password' => $currentPlain,
            'new_password'     => $newPlain,
            'confirm_password' => $newPlain,
        ];

        // BUG-01: Debe llamar a findWithPasswordById (que contiene 'password')
        $this->userRepo->expects($this->once())
            ->method('findWithPasswordById')
            ->with(42)
            ->willReturn([
                'id'                   => 42,
                'username'             => 'test.user',
                'email'                => 'test@sfluls.cl',
                'password'             => $currentHash,
                'role_id'              => 2,
                'must_change_password' => true,
            ]);

        // Debe actualizar la contraseña y desmarcar must_change_password
        $this->userService->expects($this->once())
            ->method('update')
            ->with(42, [
                'password'             => $newPlain,
                'must_change_password' => false,
            ])
            ->willReturn([]);

        $this->auditService->expects($this->once())
            ->method('log')
            ->with(42, 'cambiar_password', 'user', 42, 'Contraseña cambiada por el usuario');

        $controller = $this->createController();
        $controller->submit();

        $this->assertSame('/admin', $this->redirectedTo);
        $this->assertFalse($_SESSION['must_change_password']);
        $this->assertSame('Contraseña actualizada correctamente.', $_SESSION['_password_success']);
        $this->assertEmpty($_SESSION['_password_errors'] ?? []);
    }

    public function testSubmitFailsWhenCurrentPasswordIsWrong(): void
    {
        $this->authenticateUser(42, 'test.user');

        $_POST = [
            'current_password' => 'WrongCurrentPassword123!',
            'new_password'     => 'NewSecurePassword456!',
            'confirm_password' => 'NewSecurePassword456!',
        ];

        $this->userRepo->expects($this->once())
            ->method('findWithPasswordById')
            ->with(42)
            ->willReturn([
                'id'       => 42,
                'username' => 'test.user',
                'password' => password_hash('CorrectPassword123!', PASSWORD_DEFAULT),
            ]);

        $this->userService->expects($this->never())->method('update');

        $controller = $this->createController();
        $controller->submit();

        $this->assertSame('/cambiar-password', $this->redirectedTo);
        $this->assertSame('La contraseña actual es incorrecta.', $_SESSION['_password_errors']['current_password'] ?? '');
    }

    public function testSubmitRedirectsToLoginWhenUserNotFound(): void
    {
        $this->authenticateUser(999, 'ghost.user');

        $this->userRepo->expects($this->once())
            ->method('findWithPasswordById')
            ->with(999)
            ->willReturn(null);

        $controller = $this->createController();
        $controller->submit();

        $this->assertSame('/login', $this->redirectedTo);
    }

    public function testSubmitFailsSafelyWhenUserPasswordHashIsNullWithoutThrowingTypeError(): void
    {
        $this->authenticateUser(42, 'test.user');

        $_POST = [
            'current_password' => 'AnyPassword123!',
            'new_password'     => 'NewSecurePassword456!',
            'confirm_password' => 'NewSecurePassword456!',
        ];

        $this->userRepo->expects($this->once())
            ->method('findWithPasswordById')
            ->with(42)
            ->willReturn([
                'id'       => 42,
                'username' => 'test.user',
                'password' => null,
            ]);

        $this->userService->expects($this->never())->method('update');

        $controller = $this->createController();
        $controller->submit();

        $this->assertSame('/cambiar-password', $this->redirectedTo);
        $this->assertSame('La contraseña actual es incorrecta.', $_SESSION['_password_errors']['current_password'] ?? '');
    }

    public function testSubmitHandlesUpdateExceptionWithoutThrowingFatalError(): void
    {
        $this->authenticateUser(42, 'test.user');

        $_POST = [
            'current_password' => 'CurrentValidPass123!',
            'new_password'     => 'NewSecurePassword456!',
            'confirm_password' => 'NewSecurePassword456!',
        ];

        $this->userRepo->expects($this->once())
            ->method('findWithPasswordById')
            ->with(42)
            ->willReturn([
                'id'       => 42,
                'username' => 'test.user',
                'password' => password_hash('CurrentValidPass123!', PASSWORD_DEFAULT),
            ]);

        $this->userService->expects($this->once())
            ->method('update')
            ->willThrowException(new \RuntimeException('Database connection lost'));

        $controller = $this->createController();
        $controller->submit();

        $this->assertSame('/cambiar-password', $this->redirectedTo);
        $this->assertSame('Ocurrió un error al actualizar la contraseña. Intente nuevamente.', $_SESSION['_password_errors']['general'] ?? '');
    }
}
