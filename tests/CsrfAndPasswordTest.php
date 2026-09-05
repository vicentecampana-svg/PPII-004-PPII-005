<?php

declare(strict_types=1);

namespace Tests;

use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;

class CsrfAndPasswordTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER = [];
        $_SESSION = [];
    }

    public function testPasswordMinimumLengthInCreate(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $audit = $this->createMock(AuditService::class);
        $service = new UserService($repo, $audit);

        $this->expectException(\InvalidArgumentException::class);
        $service->create([
            'username' => 'testuser',
            'email'    => 'test@example.com',
            'password' => 'shortpass12', // 11 characters
            'role_id'  => 2,
        ]);
    }

    public function testPasswordMinimumLengthInUpdate(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('findById')->willReturn([
            'id'       => 1,
            'username' => 'testuser',
            'email'    => 'test@example.com',
            'password' => password_hash('ValidPassword123!', PASSWORD_DEFAULT),
            'role_id'  => 2,
            'active'   => true,
        ]);

        $audit = $this->createMock(AuditService::class);
        $service = new UserService($repo, $audit);

        $this->expectException(\InvalidArgumentException::class);
        $service->update(1, [
            'password' => 'shortpass12', // 11 chars
        ]);
    }

    public function testPasswordMinimumLengthInReset(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('findById')->willReturn([
            'id'       => 1,
            'username' => 'testuser',
            'email'    => 'test@example.com',
            'password' => password_hash('ValidPassword123!', PASSWORD_DEFAULT),
            'role_id'  => 2,
            'active'   => true,
        ]);

        $audit = $this->createMock(AuditService::class);
        $service = new UserService($repo, $audit);

        $this->expectException(\InvalidArgumentException::class);
        $service->resetPassword(1, 'shortpass12'); // 11 chars
    }

    public function testPasswordMinimumLengthInChange(): void
    {
        $currentHash = password_hash('OldPassword123!', PASSWORD_DEFAULT);
        $repo = $this->createMock(UserRepository::class);
        $repo->method('findWithPasswordById')->willReturn([
            'id'       => 1,
            'username' => 'testuser',
            'email'    => 'test@example.com',
            'password' => $currentHash,
            'role_id'  => 2,
            'active'   => true,
        ]);

        $audit = $this->createMock(AuditService::class);
        $service = new UserService($repo, $audit);

        $this->expectException(\InvalidArgumentException::class);
        $service->changePassword(1, 'OldPassword123!', 'shortpass12'); // 11 chars
    }

    public function testValidPasswordAcceptedInCreate(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('findByEmail')->willReturn(null);
        $repo->method('create')->willReturn(42);
        $repo->method('findById')->willReturn([
            'id'       => 42,
            'username' => 'validuser',
            'email'    => 'valid@example.com',
            'role_id'  => 2,
            'active'   => true,
        ]);

        $audit = $this->createMock(AuditService::class);
        $service = new UserService($repo, $audit);

        $user = $service->create([
            'username' => 'validuser',
            'email'    => 'valid@example.com',
            'password' => 'ValidPassword123!', // 17 chars >= 12
            'role_id'  => 2,
        ]);

        $this->assertSame(42, $user['id']);
    }

    public function testCsrfValidationFailsWhenTokenMismatch(): void
    {
        $_SESSION['csrf_token'] = 'valid_session_token_1234567890abcdef';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'invalid_token';

        $sessionToken = $_SESSION['csrf_token'] ?? '';
        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        $this->assertFalse(hash_equals($sessionToken, $headerToken));
    }

    public function testCsrfValidationPassesWhenTokenMatches(): void
    {
        $_SESSION['csrf_token'] = 'valid_session_token_1234567890abcdef';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'valid_session_token_1234567890abcdef';

        $sessionToken = $_SESSION['csrf_token'] ?? '';
        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        $this->assertTrue(hash_equals($sessionToken, $headerToken));
    }
}
