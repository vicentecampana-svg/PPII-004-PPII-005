<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Helper compartido para renderizar una vista dentro del layout
 * (header + contenido + footer).
 */
abstract class Controller
{
    protected function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewsPath = dirname(__DIR__) . '/Views/';

        require $viewsPath . 'layout/header.php';
        require $viewsPath . $view . '.php';
        require $viewsPath . 'layout/footer.php';
    }
}
