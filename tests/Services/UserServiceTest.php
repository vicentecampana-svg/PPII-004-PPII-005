<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\UserService;
use App\Repositories\UserRepository;
use App\Services\AuditService;

class UserServiceTest extends TestCase
{
    private $repoMock;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repoMock = $this->createMock(UserRepository::class);
        // Mock de auditoría para no escribir en audit_log de la base real.
        $this->service = new UserService($this->repoMock, $this->createMock(AuditService::class));
    }

    public function testGetAllReturnsPaginatedData()
    {
        $this->repoMock->method('count')->willReturn(15);
        $this->repoMock->method('findAll')->willReturn([['id' => 1], ['id' => 2]]);

        $result = $this->service->getAll(1, 10);

        $this->assertEquals(15, $result['total']);
        $this->assertEquals(2, $result['total_pages']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(10, $result['per_page']);
        $this->assertCount(2, $result['items']);
    }

    public function testGetByIdReturnsUser()
    {
        $this->repoMock->method('findById')->with(1)->willReturn(['id' => 1, 'username' => 'test']);

        $result = $this->service->getById(1);

        $this->assertNotNull($result);
        $this->assertEquals('test', $result['username']);
    }

    public function testCreateValidatesInputAndCreatesUser()
    {
        $data = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'SecurePass123!',
            'role_id' => 1,
        ];

        $this->repoMock->method('findByEmail')->with('test@example.com')->willReturn(null);
        $this->repoMock->method('create')->willReturn(1);
        $this->repoMock->method('findById')->with(1)->willReturn(['id' => 1] + $data);

        $result = $this->service->create($data);

        $this->assertEquals(1, $result['id']);
        $this->assertEquals('testuser', $result['username']);
    }

    public function testCreateThrowsExceptionOnInvalidData()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->create([]);
    }

    public function testDeleteThrowsExceptionIfUserNotFound()
    {
        $this->repoMock->method('findById')->with(99)->willReturn(null);
        $this->expectException(\RuntimeException::class);
        $this->service->delete(99);
    }

    public function testDeleteIsLogicalAndDoesNotRemoveRow()
    {
        $this->repoMock->method('findById')->with(5)->willReturn(['id' => 5, 'username' => 'testu']);
        $this->repoMock->expects($this->once())->method('delete')->with(5)->willReturn(1);

        $this->service->delete(5);
    }

    public function testCreateRejectsEmailOfDeletedUser()
    {
        $this->repoMock->method('findByEmail')->willReturn(['id' => 5, 'deleted_at' => '2026-09-25 12:00:00']);
        $this->repoMock->expects($this->never())->method('create');

        try {
            $this->service->create([
                'username' => 'nuevo',
                'email'    => 'test@test.cl',
                'password' => 'SecurePass123!',
                'role_id'  => 3,
            ]);
            $this->fail('Se esperaba InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('cuenta eliminada', json_decode($e->getMessage(), true)['email']);
        }
    }

    public function testCreateRejectsUsernameOfDeletedUser()
    {
        $this->repoMock->method('findByEmail')->willReturn(null);
        $this->repoMock->method('findByUsername')->willReturn(['id' => 5, 'deleted_at' => '2026-09-25 12:00:00']);
        $this->repoMock->expects($this->never())->method('create');

        try {
            $this->service->create([
                'username' => 'testu',
                'email'    => 'otro@test.cl',
                'password' => 'SecurePass123!',
                'role_id'  => 3,
            ]);
            $this->fail('Se esperaba InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('cuenta eliminada', json_decode($e->getMessage(), true)['username']);
        }
    }
}
