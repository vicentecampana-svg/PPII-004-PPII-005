<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Repositories\CreditMemberRepository;
use App\Services\AuditService;
use App\Services\CreditMemberService;
use PHPUnit\Framework\TestCase;

class CreditMemberServiceTest extends TestCase
{
    private $repoMock;
    private $auditMock;
    private CreditMemberService $service;

    private function stubMember(array $overrides = []): array
    {
        return array_merge([
            'id'    => 1,
            'key'   => 'vicente-campana',
            'name'  => 'Vicente Campaña',
            'role'  => 'Project Manager',
            'email' => 'vicente.campana@userena.cl',
            'orden' => 1,
        ], $overrides);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->repoMock  = $this->createMock(CreditMemberRepository::class);
        $this->auditMock = $this->createMock(AuditService::class);
        $this->service   = new CreditMemberService($this->repoMock, $this->auditMock);
    }

    public function testGetAllReturnsPaginatedData(): void
    {
        $this->repoMock->method('count')->willReturn(3);
        $this->repoMock->method('findAll')->willReturn([$this->stubMember()]);

        $result = $this->service->getAll(1, 10);

        $this->assertEquals(3, $result['total']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(10, $result['per_page']);
        $this->assertEquals(1, $result['total_pages']);
        $this->assertCount(1, $result['items']);
    }

    public function testGetAllForwardsIncludeEmailFlag(): void
    {
        $this->repoMock->method('count')->willReturn(3);
        $this->repoMock->expects($this->once())
            ->method('findAll')
            ->with(10, 0, true);

        $this->service->getAll(1, 10, true);
    }

    public function testGetAllPublicNeverExposesEmail(): void
    {
        $this->repoMock->method('findAllPublic')->willReturn([$this->stubMember()]);

        $members = $this->service->getAllPublic();

        $this->assertCount(1, $members);
        $this->assertSame('vicente-campana', $members[0]['key']);
        $this->assertArrayNotHasKey('email', $members[0]);
    }

    public function testCreateThrowsOnMissingFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->create(['name' => 'Nuevo Integrante']);
    }

    public function testCreateUsesProvidedKey(): void
    {
        $this->repoMock->method('keyExists')->with('nuevo-miembro')->willReturn(false);
        $this->repoMock->method('nextOrden')->willReturn(9);
        $this->repoMock->method('create')->willReturn(42);
        $this->repoMock->method('findById')->with(42, true)->willReturn($this->stubMember(['id' => 42]));

        $this->auditMock->expects($this->once())->method('log');

        $result = $this->service->create([
            'name'  => 'Nuevo Miembro',
            'role'  => 'Desarrollo',
            'email' => 'nuevo@userena.cl',
            'key'   => 'nuevo-miembro',
        ]);

        $this->assertEquals(42, $result['id']);
    }

    public function testCreateAutoGeneratesSlugFromName(): void
    {
        $this->repoMock->method('keyExists')->with('agustina-hernandez')->willReturn(false);
        $this->repoMock->method('nextOrden')->willReturn(9);
        $this->repoMock->method('create')->willReturn(43);
        $this->repoMock->method('findById')->with(43, true)->willReturn($this->stubMember(['id' => 43]));

        $this->service->create([
            'name'  => 'Agustina Hernández',
            'role'  => 'Desarrollo Frontend',
            'email' => 'agustina.hernandez@userena.cl',
        ]);
    }

    public function testCreateAppendsSuffixWhenKeyExists(): void
    {
        $this->repoMock->method('keyExists')->willReturnCallback(
            static fn(string $key): bool => $key === 'maximiliano-saavedra'
        );
        $this->repoMock->method('nextOrden')->willReturn(9);
        $this->repoMock->method('create')->willReturn(44);
        $this->repoMock->method('findById')->with(44, true)->willReturn($this->stubMember(['id' => 44]));

        $this->service->create([
            'name'  => 'Maximiliano Saavedra',
            'role'  => 'Backend',
            'email' => 'max@userena.cl',
        ]);
    }

    public function testUpdateModifiesPartialFields(): void
    {
        $this->repoMock->method('findById')->with(1, true)->willReturn($this->stubMember());
        $this->repoMock->expects($this->once())
            ->method('update')
            ->with(1, ['role' => 'Director de Proyectos']);

        $this->auditMock->expects($this->once())->method('log');

        $this->service->update(1, ['role' => 'Director de Proyectos']);
    }

    public function testUpdateThrowsWhenMemberNotFound(): void
    {
        $this->repoMock->method('findById')->with(99, true)->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Integrante de créditos no encontrado.');

        $this->service->update(99, ['role' => 'QA']);
    }

    public function testUpdateRejectsInvalidEmail(): void
    {
        $this->repoMock->method('findById')->with(1, true)->willReturn($this->stubMember());

        $this->expectException(\InvalidArgumentException::class);

        $this->service->update(1, ['email' => 'no-es-un-correo']);
    }

    public function testDeleteRemovesAndAudits(): void
    {
        $this->repoMock->method('findById')->with(1)->willReturn($this->stubMember());
        $this->repoMock->expects($this->once())->method('delete')->with(1);
        $this->auditMock->expects($this->once())->method('log');

        $this->service->delete(1);
    }

    public function testDeleteThrowsWhenMemberNotFound(): void
    {
        $this->repoMock->method('findById')->with(99)->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->delete(99);
    }
}
