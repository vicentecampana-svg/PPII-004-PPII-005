<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\CreditsService;

class CreditsServiceTest extends TestCase
{
    private CreditsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CreditsService();
    }

    public function testPublicMembersDoNotContainEmails(): void
    {
        $members = $this->service->getPublicMembers();

        $this->assertNotEmpty($members);
        $this->assertCount(8, $members);
        $this->assertEquals('vicente-campana', $members[0]['key']);
        $this->assertEquals('pedro-rojas', $members[7]['key']);

        foreach ($members as $member) {
            $this->assertArrayHasKey('key', $member);
            $this->assertArrayHasKey('name', $member);
            $this->assertArrayHasKey('role', $member);
            $this->assertArrayNotHasKey('email', $member, 'La dirección de correo nunca debe exponerse públicamente');
        }
    }

    public function testSendContactMessageThrowsExceptionOnInvalidMember(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El integrante seleccionado no es válido.');

        $this->service->sendContactMessage([
            'member_key' => 'integrante-inexistente',
            'name'       => 'Juan Pérez',
            'email'      => 'juan@example.com',
            'message'    => 'Hola, este es un mensaje de prueba.',
        ]);
    }

    public function testSendContactMessageThrowsExceptionOnShortName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El nombre debe tener entre 2 y 150 caracteres.');

        $this->service->sendContactMessage([
            'member_key' => 'pedro-rojas',
            'name'       => 'J',
            'email'      => 'juan@example.com',
            'message'    => 'Hola, este es un mensaje de prueba.',
        ]);
    }

    public function testSendContactMessageThrowsExceptionOnInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El correo electrónico ingresado no es válido.');

        $this->service->sendContactMessage([
            'member_key' => 'pedro-rojas',
            'name'       => 'Juan Pérez',
            'email'      => 'correo-invalido',
            'message'    => 'Hola, este es un mensaje de prueba.',
        ]);
    }

    public function testSendContactMessageThrowsExceptionOnShortMessage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El mensaje debe tener entre 5 y 5000 caracteres.');

        $this->service->sendContactMessage([
            'member_key' => 'pedro-rojas',
            'name'       => 'Juan Pérez',
            'email'      => 'juan@example.com',
            'message'    => 'Hi',
        ]);
    }

    public function testSendContactMessageSuccess(): void
    {
        $result = $this->service->sendContactMessage([
            'member_key' => 'vicente-campana',
            'name'       => 'María González',
            'email'      => 'maria@example.com',
            'message'    => 'Hola Vicente, me gustaría contactar por el proyecto.',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('Vicente Campaña', $result['recipient']);
        $this->assertStringContainsString('enviado exitosamente', $result['message']);
    }
}
