<?php

declare(strict_types=1);

namespace Tests;

use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;

/**
 * RF-AUT-003 / RF-AUT-004: los disparadores de `must_change_password`.
 *
 * - Usuario nuevo con contraseña inicial → true (al primer inicio de sesión).
 * - Contraseña asignada o restablecida por el Super Usuario → true.
 * - Cambio de contraseña por el propio usuario → false.
 * - Recuperación por token → false (el formulario ya es el cambio).
 */
class MustChangePasswordTest extends TestCase
{
    private UserRepository $repo;
    private UserService $service;

    protected function setUp(): void
    {
        $_SESSION = [];
        $this->repo = $this->createMock(UserRepository::class);
        $this->service = new UserService($this->repo, $this->createMock(AuditService::class));
    }

    public function testCreateDefaultsToMustChangePassword(): void
    {
        $this->repo->method('findByEmail')->willReturn(null);
        $this->repo->method('findByUsername')->willReturn(null);
        $this->repo->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn(array $data): bool => ($data['must_change_password'] ?? null) === true))
            ->willReturn(7);
        $this->repo->method('findById')->willReturn(['id' => 7]);

        $this->service->create([
            'username' => 'nuevo.usuario',
            'email'    => 'nuevo@test.cl',
            'password' => 'SecurePass123!',
            'role_id'  => 3,
        ]);
    }

    public function testUpdateWithPasswordForcesChangeByDefault(): void
    {
        $this->repo->method('findById')->willReturn(['id' => 5, 'username' => 'usuario5']);
        $this->repo->expects($this->once())
            ->method('update')
            ->with(
                5,
                $this->callback(static fn(array $fields): bool =>
                    isset($fields['password']) && ($fields['must_change_password'] ?? null) === true)
            );

        $this->service->update(5, ['password' => 'SecurePass123!']);
    }

    public function testUpdateWithPasswordRespectsExplicitFalseFlag(): void
    {
        $this->repo->method('findById')->willReturn(['id' => 5, 'username' => 'usuario5']);
        $this->repo->expects($this->once())
            ->method('update')
            ->with(
                5,
                $this->callback(static fn(array $fields): bool =>
                    isset($fields['password']) && ($fields['must_change_password'] ?? null) === false)
            );

        $this->service->update(5, [
            'password'             => 'SecurePass123!',
            'must_change_password' => false,
        ]);
    }

    public function testUpdateWithoutPasswordDoesNotTouchFlag(): void
    {
        $this->repo->method('findById')->willReturn(['id' => 5, 'username' => 'usuario5']);
        $this->repo->expects($this->once())
            ->method('update')
            ->with(
                5,
                $this->callback(static fn(array $fields): bool => !array_key_exists('must_change_password', $fields))
            );

        $this->service->update(5, ['username' => 'usuario.cambiado']);
    }

    public function testSuperUserResetForcesChange(): void
    {
        $this->repo->method('findById')->willReturn(['id' => 5, 'username' => 'usuario5']);
        $this->repo->expects($this->once())
            ->method('update')
            ->with(
                5,
                $this->callback(static fn(array $fields): bool => ($fields['must_change_password'] ?? null) === true)
            );

        $this->service->resetPassword(5, 'SecurePass123!');
    }

    public function testSelfChangeClearsFlag(): void
    {
        $this->repo->method('findWithPasswordById')->willReturn([
            'id'       => 5,
            'username' => 'usuario5',
            'password' => password_hash('OldPassword123!', PASSWORD_DEFAULT),
        ]);
        $this->repo->method('findById')->willReturn(['id' => 5, 'username' => 'usuario5']);
        $this->repo->expects($this->once())
            ->method('update')
            ->with(
                5,
                $this->callback(static fn(array $fields): bool => ($fields['must_change_password'] ?? null) === false)
            );

        $this->service->changePassword(5, 'OldPassword123!', 'SecurePass123!');
    }
}
