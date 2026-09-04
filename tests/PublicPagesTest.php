<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class PublicPagesTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    public function testStaffViewRendersMembers(): void
    {
        $staff = [
            [
                'id' => 1,
                'name' => 'Pedro Rojas',
                'position' => 'Project Manager Officer',
                'photo' => 'staff-1.jpg',
                'description' => 'Coordina la planificación de los proyectos.',
            ],
            [
                'id' => 2,
                'name' => 'Ramiro Hernesto',
                'position' => 'Analista de Riesgos y Todologo',
                'photo' => 'staff-1.jpg',
                'description' => 'Responsable del análisis de riesgos.',
            ],
        ];

        ob_start();
        require dirname(__DIR__) . '/app/Views/staff.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Miembros del Staff', $output);
        $this->assertStringContainsString('Pedro Rojas', $output);
        $this->assertStringContainsString('Project Manager Officer', $output);
        $this->assertStringContainsString('Ramiro Hernesto', $output);
        $this->assertStringContainsString('Analista de Riesgos y Todologo', $output);
    }

    public function testNoticiasViewRendersGridAndSearch(): void
    {
        $noticias = [
            [
                'id' => 1,
                'title' => 'Estudiantes crean nueva IA de escaneo de animales',
                'subtitle' => 'Un equipo del laboratorio presentó un modelo.',
                'content' => 'Detalle de la noticia de IA.',
                'image' => 'noticia-1.jpg',
                'author' => 'Periodista 1',
            ],
        ];
        $pagination = ['total' => 1, 'page' => 1, 'totalPages' => 1];
        $tags = [];
        $selectedTagId = null;
        $query = '';

        ob_start();
        require dirname(__DIR__) . '/app/Views/noticias.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Noticias más recientes', $output);
        $this->assertStringContainsString('Estudiantes crean nueva IA de escaneo de animales', $output);
        $this->assertStringContainsString('Periodista 1', $output);
        $this->assertStringContainsString('Ver más', $output);
        $this->assertStringContainsString('/noticias/1', $output);
    }

    public function testNoticiaDetailViewRendersArticleAndRelated(): void
    {
        $noticia = [
            'id' => 2,
            'title' => 'Nuevo convenio de vinculación regional',
            'subtitle' => 'La universidad firmó un acuerdo para desarrollar plataformas digitales.',
            'content' => 'El convenio permitirá desarrollar plataformas digitales para municipios de la región de Coquimbo.',
            'image' => 'noticia-1.jpg',
            'author' => 'Periodista 2',
        ];
        $otrasNoticias = [
            [
                'id' => 1,
                'title' => 'Estudiantes crean nueva IA',
                'subtitle' => 'Resumen',
                'content' => 'Contenido',
                'image' => 'noticia-1.jpg',
                'author' => 'Periodista 1',
            ],
        ];

        ob_start();
        require dirname(__DIR__) . '/app/Views/noticia.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('NUEVO CONVENIO DE VINCULACIÓN REGIONAL', $output);
        $this->assertStringContainsString('Redactor: Periodista 2', $output);
        $this->assertStringContainsString('El convenio permitirá desarrollar plataformas digitales', $output);
        $this->assertStringContainsString('Otras noticias relevantes', $output);
        $this->assertStringContainsString('Estudiantes crean nueva IA', $output);
    }

    public function testServiciosViewRendersServices(): void
    {
        $servicios = [
            [
                'id' => 1,
                'name' => 'Desarrollo de Software a la Medida',
                'description' => 'Creamos soluciones digitales adaptadas.',
                'image' => 'proyecto-1.jpg',
                'link' => '/contacto',
                'active' => true,
            ],
        ];

        ob_start();
        require dirname(__DIR__) . '/app/Views/servicios.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Nuestros Servicios', $output);
        $this->assertStringContainsString('Desarrollo de Software a la Medida', $output);
        $this->assertStringContainsString('Solicitar servicio', $output);
        $this->assertStringContainsString('¿Tienes un requerimiento especial?', $output);
    }

    public function testContactoViewRendersForm(): void
    {
        ob_start();
        require dirname(__DIR__) . '/app/Views/contacto.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Envío de formulario de contacto', $output);
        $this->assertStringContainsString('Nombre', $output);
        $this->assertStringContainsString('Motivo de envío', $output);
        $this->assertStringContainsString('Correo de contacto', $output);
        $this->assertStringContainsString('Teléfono de contacto', $output);
        $this->assertStringContainsString('Cuerpo del motivo', $output);
        $this->assertStringContainsString('Enviar formulario', $output);
    }

    public function testHeaderLinksContainRealRoutes(): void
    {
        $currentPath = '/';
        $isLoggedIn = false;
        $pageTitle = 'Test';
        $metaDescription = 'Test';

        ob_start();
        require dirname(__DIR__) . '/app/Views/layout/header.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('href="/"', $output);
        $this->assertStringContainsString('href="/proyectos"', $output);
        $this->assertStringContainsString('href="/servicios"', $output);
        $this->assertStringContainsString('href="/staff"', $output);
        $this->assertStringContainsString('href="/noticias"', $output);
        $this->assertStringContainsString('href="/contacto"', $output);
        $this->assertStringNotContainsString('href="#staff"', $output);
        $this->assertStringNotContainsString('href="#contacto"', $output);
    }
}
