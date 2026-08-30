<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Cierre de sesión para la interfaz web.
 */
final class LogoutController
{
    public function logout(): void
    {
        authLogout();
        header('Location: /');
        exit;
    }
}
