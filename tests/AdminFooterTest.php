<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class AdminFooterTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    public function testFooterTabRendersLinksAndSocialForm(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'superadmin';
        $_SESSION['role_id'] = 1;
        $_SESSION['role_name'] = 'SuperAdmin';

        $footerLinks = [
            [
                'id'       => 1,
                'grupo'    => 'Sitio',
                'etiqueta' => 'Noticias',
                'url'      => '/noticias',
                'orden'    => 1,
            ],
            [
                'id'       => 2,
                'grupo'    => 'Sitio',
                'etiqueta' => 'Inicio',
                'url'      => '/',
                'orden'    => 2,
            ],
        ];
        $editingFooterLink = null;
        $footerInfo = [
            'social_linkedin'  => 'https://linkedin.com/company/sfl',
            'social_twitter'   => 'https://twitter.com/sfl',
            'social_instagram' => 'https://instagram.com/sfl',
        ];
        $activeTab = 'footer';

        ob_start();
        require dirname(__DIR__) . '/app/Views/admin/footer.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Enlaces del footer', $output);
        $this->assertStringContainsString('Noticias', $output);
        $this->assertStringContainsString('/noticias', $output);
        $this->assertStringContainsString('Inicio', $output);
        $this->assertStringContainsString('Redes sociales', $output);
        $this->assertStringContainsString('https://linkedin.com/company/sfl', $output);
        $this->assertStringContainsString('Nuevo registro', $output);
        $this->assertStringContainsString('Columna (Sitio, Contenido, Contacto)', $output);
        $this->assertStringContainsString('Texto del enlace', $output);
        $this->assertStringContainsString('URL o ruta', $output);
        $this->assertStringContainsString('action="/admin/footer/links"', $output);
        $this->assertStringContainsString('action="/admin/footer/links/delete"', $output);
        $this->assertStringContainsString('action="/admin/footer/social"', $output);
    }

    public function testEditingFooterLinkPopulatesForm(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'superadmin';
        $_SESSION['role_id'] = 1;
        $_SESSION['role_name'] = 'SuperAdmin';

        $footerLinks = [];
        $editingFooterLink = [
            'id'       => 15,
            'grupo'    => 'Contacto',
            'etiqueta' => 'Escríbenos',
            'url'      => '/contacto',
            'orden'    => 3,
        ];
        $footerInfo = null;
        $activeTab = 'footer';

        ob_start();
        require dirname(__DIR__) . '/app/Views/admin/footer.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Editar registro', $output);
        $this->assertStringContainsString('Escríbenos', $output);
        $this->assertStringContainsString('value="15"', $output);
        $this->assertStringContainsString('value="/contacto"', $output);
        $this->assertStringContainsString('Guardar cambios', $output);
    }
}
