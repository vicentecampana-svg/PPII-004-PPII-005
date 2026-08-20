<?php

declare(strict_types=1);

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (is_file($autoload)) {
    require $autoload;
} else {
    // Sin `composer install` disponible: autoloader PSR-4 mínimo para App\.
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relative = substr($class, strlen($prefix));
        $path = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($path)) {
            require $path;
        }
    });
}

use App\Controllers\HomeController;
use App\Controllers\ProyectosController;

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

switch ($uri) {
    case '/':
    case '/index.php':
        (new HomeController())->index();
        break;

    case '/proyectos':
        (new ProyectosController())->index();
        break;

    default:
        http_response_code(404);
        echo '404 - Página no encontrada';
        break;
}
