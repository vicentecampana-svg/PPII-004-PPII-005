<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\QueryService;
use App\Repositories\QueryRepository;
use App\Services\MailerService;
use App\Services\FooterService;

class QueryServiceTest extends TestCase
{
    private $repoMock;
    private $mailerMock;
    private $footerMock;
    private $service;
    private string $notifyEnv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notifyEnv = (string) getenv('CONTACT_NOTIFY_EMAIL');
        putenv('CONTACT_NOTIFY_EMAIL=');
        $this->repoMock = $this->createMock(QueryRepository::class);
        $this->mailerMock = $this->createMock(MailerService::class);
        $this->footerMock = $this->createMock(FooterService::class);
        $this->service = new QueryService($this->repoMock, null, $this->mailerMock, $this->footerMock);
    }

    protected function tearDown(): void
    {
        putenv('CONTACT_NOTIFY_EMAIL=' . $this->notifyEnv);
        parent::tearDown();
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
        $this->footerMock->method('getAll')->willReturn(['info' => ['email' => 'encargado@techhub.cl']]);

        $this->mailerMock->expects($this->once())
            ->method('sendContactNotification')
            ->with('encargado@techhub.cl', ['id' => 1] + $data);

        $result = $this->service->create($data);

        $this->assertEquals(1, $result['id']);
        $this->assertEquals('John Doe', $result['name']);
    }

    public function testCreateDoesNotNotifyWhenFooterEmailIsInvalid()
    {
        $this->footerMock->method('getAll')->willReturn(['info' => ['email' => '']]);

        $data = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'message' => 'Hello there',
        ];

        $this->repoMock->method('create')->willReturn(1);
        $this->repoMock->method('findById')->with(1)->willReturn(['id' => 1] + $data);

        $this->mailerMock->expects($this->once())
            ->method('sendContactNotification')
            ->with('contacto@techhub.cl', ['id' => 1] + $data);

        $this->service->create($data);
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
