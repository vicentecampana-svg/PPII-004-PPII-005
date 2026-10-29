<?php

declare(strict_types=1);

namespace Tests;

use App\Services\PasswordPolicy;
use PHPUnit\Framework\TestCase;

/**
 * Acceso al cambio de contraseña desde el panel y validación en el navegador
 * de los formularios donde se define una contraseña (cambio, restablecimiento
 * y alta de usuarios).
 */
final class PasswordFormsTest extends TestCase
{
    protected function setUp(): void
    {
        sessionStart();
        $_SESSION = [];
    }

    /**
     * Las reglas que recibe el navegador deben producir exactamente los mismos
     * mensajes que PasswordPolicy::errors().
     */
    public function testClientConfigMatchesServerPolicy(): void
    {
        $config = PasswordPolicy::clientConfig();

        $samples = ['', 'Ab1!xyz', 'abcdefghijkl1!', 'ABCDEFGHIJKL1!', 'Abcdefghijkl!',
            'Abcdefghijkl1', 'ValidPassword123!', 'Ñandú-Seguro-2026', str_repeat('aA1!', 31)];

        foreach ($samples as $password) {
            $clientErrors = [];
            $length = mb_strlen($password);
            if ($length < $config['minLength']) {
                $clientErrors[] = $config['minLengthMessage'];
            }
            if ($length > $config['maxLength']) {
                $clientErrors[] = $config['maxLengthMessage'];
            }
            foreach ($config['rules'] as $rule) {
                if (!preg_match('/' . $rule['pattern'] . '/u', $password)) {
                    $clientErrors[] = $rule['message'];
                }
            }

            $this->assertSame(PasswordPolicy::errors($password), $clientErrors, "Contraseña: {$password}");
        }
    }

    public function testClientConfigAttributeIsEscapedJson(): void
    {
        $attribute = PasswordPolicy::clientConfigAttribute();

        $this->assertMatchesRegularExpression('/^data-password-policy="[^"]+"$/', $attribute);
        $json = html_entity_decode(substr($attribute, strlen('data-password-policy="'), -1), ENT_QUOTES, 'UTF-8');
        $this->assertSame(PasswordPolicy::clientConfig(), json_decode($json, true));
    }

    public function testAdminHeaderShowsChangePasswordButtonForEveryRole(): void
    {
        foreach (['SuperAdmin', 'Admin', 'Redactor'] as $i => $role) {
            $_SESSION['user_id'] = $i + 1;
            $_SESSION['username'] = 'usuario_' . $i;
            $_SESSION['role_id'] = $i + 1;
            $_SESSION['role_name'] = $role;

            $user = authUser();
            $activeTab = 'noticias';

            ob_start();
            require dirname(__DIR__) . '/app/Views/admin.php';
            $output = ob_get_clean();

            $this->assertStringContainsString('href="/cambiar-password"', $output, "Rol: {$role}");
            $this->assertStringContainsString('Cambiar contraseña', $output, "Rol: {$role}");
        }
    }

    public function testChangePasswordViewWiresClientValidation(): void
    {
        $csrfToken = 'token';
        $errors = [];
        $forced = false;

        ob_start();
        require dirname(__DIR__) . '/app/Views/cambiar-password.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('data-password-policy="', $output);
        $this->assertMatchesRegularExpression('/id="new_password"[^>]*data-password-new/s', $output);
        $this->assertMatchesRegularExpression('/id="confirm_password"[^>]*data-password-confirm="new_password"/s', $output);
        $this->assertDoesNotMatchRegularExpression('/id="current_password"[^>]*data-password-(new|confirm)/s', $output);
        $this->assertStringContainsString('/assets/js/password-policy.js', $output);
        $this->assertStringContainsString('href="/admin"', $output);
    }

    public function testForcedChangeHidesBackToPanelLink(): void
    {
        $csrfToken = 'token';
        $errors = [];
        $forced = true;

        ob_start();
        require dirname(__DIR__) . '/app/Views/cambiar-password.php';
        $output = ob_get_clean();

        $this->assertStringNotContainsString('Volver al panel', $output);
    }

    public function testChangePasswordViewShowsServerErrors(): void
    {
        $csrfToken = 'token';
        $errors = [
            'current_password' => 'La contraseña actual es incorrecta.',
            'confirm_password' => PasswordPolicy::MISMATCH_MESSAGE,
        ];

        ob_start();
        require dirname(__DIR__) . '/app/Views/cambiar-password.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('La contraseña actual es incorrecta.', $output);
        $this->assertMatchesRegularExpression('/id="confirm-password-error">Las contraseñas no coinciden\./', $output);
        $this->assertMatchesRegularExpression('/id="new-password-error" hidden>/', $output);
    }

    public function testResetPasswordViewWiresClientValidation(): void
    {
        $csrfToken = 'token';
        $token = 'abc';
        $errors = [];

        ob_start();
        require dirname(__DIR__) . '/app/Views/restablecer-password.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('data-password-policy="', $output);
        $this->assertMatchesRegularExpression('/id="new_password"[^>]*data-password-new/s', $output);
        $this->assertMatchesRegularExpression('/id="confirm_password"[^>]*data-password-confirm="new_password"/s', $output);
        $this->assertStringContainsString('/assets/js/password-policy.js', $output);
    }

    public function testUserFormPasswordIsRequiredOnCreateAndOptionalOnEdit(): void
    {
        $usersList = [];
        $rolesList = [];
        $activeTab = 'usuarios';

        $editingUser = null;
        ob_start();
        require dirname(__DIR__) . '/app/Views/admin/usuarios.php';
        $create = ob_get_clean();

        $this->assertStringContainsString('data-password-policy="', $create);
        $this->assertMatchesRegularExpression('/id="user-password"[^>]*data-password-new[^>]*aria-required="true"/s', $create);
        // La validación nativa (minlength/required) se adelantaría al mensaje propio del script.
        $this->assertDoesNotMatchRegularExpression('/id="user-password"[^>]*\s(minlength|required)[=\s>]/s', $create);
        $this->assertStringContainsString('/assets/js/password-policy.js', $create);

        $editingUser = ['id' => 5, 'email' => 'a@b.cl', 'username' => 'a', 'role_id' => 3, 'active' => true];
        ob_start();
        require dirname(__DIR__) . '/app/Views/admin/usuarios.php';
        $edit = ob_get_clean();

        $this->assertMatchesRegularExpression('/id="user-password"[^>]*data-password-optional/s', $edit);
    }
}
