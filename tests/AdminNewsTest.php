<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class AdminNewsTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    public function testNoticiasTabRendersListAndForm(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role_id'] = 1;
        $_SESSION['role_name'] = 'SuperAdmin';

        $user = authUser();
        $newsList = [
            [
                'id'       => 1,
                'title'    => 'Se crea la noticia nueva por el redactor',
                'subtitle' => 'Lorem ipsum es simplemente el texto de relleno',
                'content'  => 'Contenido completo de la noticia.',
                'image'    => null,
                'status'   => 'pendiente',
            ],
            [
                'id'       => 2,
                'title'    => 'Convenio firmado con empresas regionales',
                'subtitle' => 'La universidad firmó un acuerdo clave',
                'content'  => 'Texto de la noticia aprobada.',
                'image'    => 'convenio.jpg',
                'status'   => 'publicada',
            ],
        ];
        $editingNews = null;
        $activeTab = 'noticias';

        ob_start();
        require dirname(__DIR__) . '/app/Views/admin/noticias.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Noticias', $output);
        $this->assertStringContainsString('Se crea la noticia nueva por el redactor', $output);
        $this->assertStringContainsString('Pendiente', $output);
        $this->assertStringContainsString('Convenio firmado con empresas regionales', $output);
        $this->assertStringContainsString('Aprobada', $output);
        $this->assertStringContainsString('Nueva noticia', $output);
        $this->assertStringContainsString('Título', $output);
        $this->assertStringContainsString('Redactor', $output);
        $this->assertStringContainsString('Cuerpo (un párrafo por línea)', $output);
        $this->assertStringContainsString('action="/admin/noticias"', $output);
        $this->assertStringContainsString('action="/admin/noticias/delete"', $output);
        $this->assertStringContainsString('action="/admin/noticias/status"', $output);
    }

    public function testEditingNewsPopulatesForm(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role_id'] = 1;
        $_SESSION['role_name'] = 'SuperAdmin';

        $user = authUser();
        $newsList = [];
        $editingNews = [
            'id'       => 10,
            'title'    => 'Noticia en Edición Especial',
            'subtitle' => 'Subtítulo en edición',
            'content'  => 'Contenido detallado para editar.',
            'image'    => 'news-10.jpg',
            'status'   => 'publicada',
            'author'   => 'admin',
        ];
        $activeTab = 'noticias';

        ob_start();
        require dirname(__DIR__) . '/app/Views/admin/noticias.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Editar noticia', $output);
        $this->assertStringContainsString('Noticia en Edición Especial', $output);
        $this->assertStringContainsString('Subtítulo en edición', $output);
        $this->assertStringContainsString('value="10"', $output);
        $this->assertStringContainsString('Guardar cambios', $output);
    }
}
