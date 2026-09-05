<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\NewsService;
use App\Repositories\NewsRepository;

class NewsServiceTest extends TestCase
{
    private $repoMock;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repoMock = $this->createMock(NewsRepository::class);
        $this->service = new NewsService($this->repoMock);
    }

    public function testGetAllReturnsPaginatedData()
    {
        $this->repoMock->method('countAll')->willReturn(5);
        $this->repoMock->method('findAll')->willReturn([['id' => 1]]);

        $result = $this->service->getAll(1, 10);

        $this->assertEquals(5, $result['total']);
        $this->assertCount(1, $result['items']);
    }

    public function testGetPublishedByIdReturnsNullIfNotPublished()
    {
        $this->repoMock->method('findById')->with(1)->willReturn(['id' => 1, 'status' => 'pendiente']);

        $result = $this->service->getPublishedById(1);

        $this->assertNull($result);
    }

    public function testGetPublishedByIdReturnsNewsIfPublished()
    {
        $this->repoMock->method('findById')->with(1)->willReturn(['id' => 1, 'status' => 'publicada']);

        $result = $this->service->getPublishedById(1);

        $this->assertNotNull($result);
        $this->assertEquals('publicada', $result['status']);
    }

    public function testCreateValidatesInputAndCreatesNews()
    {
        $data = [
            'title' => 'Test News',
            'content' => 'Content of the news'
        ];

        $this->repoMock->method('getStatusId')->with('pendiente')->willReturn(1);
        $this->repoMock->method('create')->willReturn(1);
        $this->repoMock->method('findById')->with(1)->willReturn(['id' => 1] + $data);

        $result = $this->service->create($data, 1);

        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Test News', $result['title']);
    }

    public function testCreateThrowsExceptionOnInvalidData()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->create(['title' => ''], 1);
    }

    public function testUpdateStatusThrowsExceptionOnInvalidStatus()
    {
        $this->repoMock->method('findById')->with(1)->willReturn(['id' => 1]);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->updateStatus(1, 'invalid_status');
    }
}
