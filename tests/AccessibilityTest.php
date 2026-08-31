<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class AccessibilityTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    public function testHeaderHasSkipLinkAndLandmarks(): void
    {
        $currentPath = '/';
        $isLoggedIn = false;
        $pageTitle = 'Test Title';
        $metaDescription = 'Test Meta';

        ob_start();
        require dirname(__DIR__) . '/app/Views/layout/header.php';
        $output = ob_get_clean();

        // 1. Skip link (WCAG 2.4.1)
        $this->assertStringContainsString('class="skip-link"', $output);
        $this->assertStringContainsString('href="#main-content"', $output);
        $this->assertStringContainsString('Saltar al contenido principal', $output);

        // 2. Main landmark with matching ID and tabindex
        $this->assertStringContainsString('<main id="main-content" class="site-main" tabindex="-1">', $output);

        // 3. Header landmark and semantic roles
        $this->assertStringContainsString('role="banner"', $output);

        // 4. Nav toggle without inline onclick
        $this->assertStringNotContainsString('onclick="', $output);
        $this->assertStringContainsString('aria-label="Abrir menú"', $output);
        $this->assertStringContainsString('aria-expanded="false"', $output);
        $this->assertStringContainsString('aria-controls="mobile-nav"', $output);

        // 5. Logo alt text
        $this->assertStringContainsString('alt="Software Factory Lab Universidad de La Serena"', $output);
    }

    public function testFooterHasAccessibleLandmarksAndSocialLabels(): void
    {
        $enlacesFooter = [];
        $contacto = [
            'address' => 'La Serena, Chile',
            'email' => 'contacto@sfl.uls.cl',
            'social_linkedin' => 'https://linkedin.com',
            'social_twitter' => 'https://twitter.com',
            'social_instagram' => 'https://instagram.com',
            'copyright_text' => '© SFL. Todos los derechos reservados',
        ];
        $currentPath = '/';

        ob_start();
        require dirname(__DIR__) . '/app/Views/layout/footer.php';
        $output = ob_get_clean();

        // 1. Footer landmark
        $this->assertStringContainsString('role="contentinfo"', $output);

        // 2. Logo alt text
        $this->assertStringContainsString('alt="Software Factory Lab Universidad de La Serena"', $output);

        // 3. Social link accessible names
        $this->assertStringContainsString('aria-label="Software Factory Lab en LinkedIn (abre en pestaña nueva)"', $output);
        $this->assertStringContainsString('aria-label="Software Factory Lab en X / Twitter (abre en pestaña nueva)"', $output);
        $this->assertStringContainsString('aria-label="Software Factory Lab en Instagram (abre en pestaña nueva)"', $output);

        // 4. Rel security on external links
        $this->assertStringContainsString('rel="noopener noreferrer"', $output);

        // 5. Clean event listeners in script
        $this->assertStringContainsString('addEventListener', $output);
        $this->assertStringContainsString('Escape', $output);
    }

    public function testLoginFormHasAccessibleLabelsAndErrorRoles(): void
    {
        $csrfToken = 'test-token';
        $errors = [
            'general' => 'Credenciales inválidas',
            'email' => 'El correo es requerido',
            'password' => 'La contraseña es requerida',
        ];
        $email = 'test@userena.cl';

        ob_start();
        require dirname(__DIR__) . '/app/Views/login.php';
        $output = ob_get_clean();

        // Form error roles (WCAG 4.1.3 Status Messages)
        $this->assertStringContainsString('role="alert"', $output);
        $this->assertStringContainsString('aria-live="assertive"', $output);

        // Input error relationships (WCAG 1.3.1 & 3.3.1)
        $this->assertStringContainsString('aria-invalid="true"', $output);
        $this->assertStringContainsString('aria-describedby="email-error"', $output);
        $this->assertStringContainsString('aria-describedby="password-error"', $output);

        // Captcha reload button accessible label
        $this->assertStringContainsString('aria-label="Recargar código de seguridad CAPTCHA"', $output);
    }

    public function testCreditsModalHasAccessibleDialogAttributes(): void
    {
        $members = [];

        ob_start();
        require dirname(__DIR__) . '/app/Views/credits.php';
        $output = ob_get_clean();

        // Dialog roles and properties (WCAG 4.1.2)
        $this->assertStringContainsString('role="dialog"', $output);
        $this->assertStringContainsString('aria-modal="true"', $output);
        $this->assertStringContainsString('aria-labelledby="modal-title"', $output);
        $this->assertStringContainsString('aria-describedby="modal-subtitle"', $output);
    }
}
