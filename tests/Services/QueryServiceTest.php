<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\QueryService;
use App\Repositories\QueryRepository;

class QueryServiceTest extends TestCase
{
    private $repoMock;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repoMock = $this->createMock(QueryRepository::class);
        $this->service = new QueryService($this->repoMock);
    }

    public function testGetAllReturnsPaginatedData()
    {
        $this->repoMock->method('count')->willReturn(3);
        $this->repoMock->method('countPending')->willReturn(1);
        $this->repoMock->method('findAll')->willReturn([['id' => 1]]);

        $result = $this->service->getAll(1, 10);

        $this->assertEquals(3, $result['total']);
        $this->assertEquals(1, $result['pending']);
        $this->assertCount(1, $result['items']);
    }

    public function testCreateValidatesInputAndCreatesQuery()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Hello there',
        ];

        $this->repoMock->method('create')->willReturn(1);
        $this->repoMock->method('findById')->with(1)->willReturn(['id' => 1] + $data);

        $result = $this->service->create($data);

        $this->assertEquals(1, $result['id']);
        $this->assertEquals('John Doe', $result['name']);
    }

    public function testCreateThrowsExceptionOnInvalidData()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->create([]);
    }

    public function testSetStatusThrowsExceptionOnInvalidStatus()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->setStatus(1, 'invalid');
    }
}
