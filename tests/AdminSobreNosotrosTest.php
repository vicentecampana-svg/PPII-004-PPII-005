<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class AdminSobreNosotrosTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    public function testSobreNosotrosTabRendersForm(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role_id'] = 1;
        $_SESSION['role_name'] = 'SuperAdmin';

        $siteContent = [
            'sobre_titulo'  => 'Sobre nosotros',
            'sobre_texto'   => 'Software Factory Lab es la fábrica de software...',
            'mision_titulo' => 'Misión, visión y objetivos',
            'mision_texto'  => 'Formar talento tecnológico mediante la práctica...',
        ];
        $activeTab = 'sobre-nosotros';

        ob_start();
        require dirname(__DIR__) . '/app/Views/admin/sobre-nosotros.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Sobre nosotros, misión y visión', $output);
        $this->assertStringContainsString('Título «Sobre nosotros»', $output);
        $this->assertStringContainsString('Texto «Sobre nosotros»', $output);
        $this->assertStringContainsString('Título «Misión y visión»', $output);
        $this->assertStringContainsString('Texto «Misión, visión y objetivos»', $output);
        $this->assertStringContainsString('action="/admin/sobre-nosotros"', $output);
        $this->assertStringContainsString('Guardar cambios', $output);
    }
}
