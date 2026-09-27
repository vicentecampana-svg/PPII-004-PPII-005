<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class AdminSobreNosotrosTest extends TestCase
{
    protected function setUp(): void
    {
        sessionStart();
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

        $this->assertStringContainsString('admin-title-md">Sobre nosotros</h2>', $output);
        $this->assertStringContainsString('admin-title-md">Misión, visión y objetivos</h2>', $output);
        $this->assertStringContainsString('for="sobre-titulo">Título</label>', $output);
        $this->assertStringContainsString('for="sobre-texto">Descripción</label>', $output);
        $this->assertStringContainsString('for="mision-titulo">Título</label>', $output);
        $this->assertStringContainsString('for="mision-texto">Descripción</label>', $output);
        $this->assertStringContainsString('action="/admin/sobre-nosotros"', $output);
        $this->assertStringContainsString('Guardar cambios', $output);
    }

    public function testHomeRendersWhenMisionIsEmpty(): void
    {
        $contenido = [
            'sobre_titulo'  => 'Sobre nosotros',
            'sobre_texto'   => 'Texto institucional',
            'mision_titulo' => null,
            'mision_texto'  => null,
        ];
        $proyectos = $staff = $noticias = [];

        ob_start();
        require dirname(__DIR__) . '/app/Views/home.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('<h1>Sobre nosotros</h1>', $output);
        $this->assertStringNotContainsString('<h2></h2>', $output);
    }

    public function testParagraphSpacingIsSameForOneOrManyLineBreaks(): void
    {
        $expected = "Primer párrafo.\n\nSegundo párrafo.";

        $this->assertSame($expected, eParagraphs("Primer párrafo.\nSegundo párrafo."));
        $this->assertSame($expected, eParagraphs("Primer párrafo.\r\n\r\nSegundo párrafo."));
        $this->assertSame($expected, eParagraphs("Primer párrafo.  \n \n\n\tSegundo párrafo.\n"));
        $this->assertSame('&lt;b&gt;', eParagraphs('<b>'));
        $this->assertSame('', eParagraphs(null));
    }
}
