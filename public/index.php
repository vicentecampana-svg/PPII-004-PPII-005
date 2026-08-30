<?php

declare(strict_types=1);

// Autoloader PSR-4 para el namespace App\\ y helpers
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});
require_once dirname(__DIR__) . '/app/helpers.php';

sessionStart();

// ══════════════════════════════════════════════
//  HANDLER — ejecuta controller + middleware
// ══════════════════════════════════════════════

function handle(array $route): void
{
    foreach ($route['middleware'] as $mw) {
        match ($mw) {
            'csrf'  => mwCsrf(),
            'auth'  => mwAuth(),
            'guest' => mwGuest(),
            'force_password_change' => mwForcePasswordChange(),
            default => null,
        };
    }

    [$class, $action] = $route['handler'];
    $controller = new $class();

    if (isset($route['params'])) {
        $controller->$action(...$route['params']);
    } else {
        $controller->$action();
    }
}

function matchRoute(string $routePath, string $uri): ?array
{
    $routeParts = explode('/', trim($routePath, '/'));
    $uriParts = explode('/', trim($uri, '/'));

    if (count($routeParts) !== count($uriParts)) {
        return null;
    }

    $params = [];
    foreach ($routeParts as $i => $part) {
        if (preg_match('/^\{(\w+)\}$/', $part, $matches)) {
            $params[$matches[1]] = $uriParts[$i];
        } elseif ($part !== $uriParts[$i]) {
            return null;
        }
    }

    return $params;
}

// ══════════════════════════════════════════════
//  ROUTING & SECURITY
// ══════════════════════════════════════════════

sendSecurityHeaders();
handleCors();

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$uri = rtrim($uri, '/') ?: '/';

// Definición de rutas API
$routes = [
    'GET' => [
        '/'                        => [['App\Controllers\HomeController', 'index'],          []],
        '/proyectos'               => [['App\Controllers\ProyectosController', 'index'],     []],
        '/credits'                 => [['App\Controllers\CreditsController', 'index'],        []],
        '/creditos'                => [['App\Controllers\CreditsController', 'index'],        []],
        '/login'                   => [['App\Controllers\LoginController', 'show'],          ['guest']],
        '/admin'                   => [['App\Controllers\AdminController', 'index'],          ['auth', 'force_password_change']],
        '/logout'                  => [['App\Controllers\LogoutController', 'logout'],        []],
        '/cambiar-password'        => [['App\Controllers\PasswordController', 'show'],        ['auth']],
        '/api/auth/me'             => [['App\Controllers\AuthController', 'me'],             ['auth']],
        '/api/news'                => [['App\Controllers\NewsController', 'index'],           []],
        '/api/news/{id}'           => [['App\Controllers\NewsController', 'show'],            []],
        '/api/projects'            => [['App\Controllers\ProjectController', 'index'],        []],
        '/api/projects/{id}'       => [['App\Controllers\ProjectController', 'show'],         []],
        '/api/services'            => [['App\Controllers\ServiceController', 'index'],        []],
        '/api/services/{id}'       => [['App\Controllers\ServiceController', 'show'],         []],
        '/api/staff'               => [['App\Controllers\StaffController', 'index'],          []],
        '/api/staff/{id}'          => [['App\Controllers\StaffController', 'show'],           []],
        '/api/queries'             => [['App\Controllers\QueryController', 'index'],          ['auth']],
        '/api/queries/{id}'        => [['App\Controllers\QueryController', 'show'],           ['auth']],
        '/api/users'               => [['App\Controllers\UserController', 'index'],           ['auth']],
        '/api/users/{id}'          => [['App\Controllers\UserController', 'show'],            ['auth']],
        '/api/roles'               => [['App\Controllers\UserController', 'roles'],           ['auth']],
        '/api/tags'                => [['App\Controllers\TagController', 'index'],            []],
        '/api/audits'              => [['App\Controllers\AuditController', 'index'],          ['auth']],
        '/api/footer'              => [['App\Controllers\FooterApiController', 'show'],       []],
    ],
    'POST' => [
        '/login'                   => [['App\Controllers\LoginController', 'submit'],        ['guest', 'csrf']],
        '/credits'                 => [['App\Controllers\CreditsController', 'submit'],       ['csrf']],
        '/creditos'                => [['App\Controllers\CreditsController', 'submit'],       ['csrf']],
        '/api/credits/contact'     => [['App\Controllers\CreditsApiController', 'contact'],   []],
        '/logout'                  => [['App\Controllers\LogoutController', 'logout'],        []],
        '/cambiar-password'        => [['App\Controllers\PasswordController', 'submit'],      ['auth', 'csrf']],
        '/api/auth/login'          => [['App\Controllers\AuthController', 'login'],           ['guest']],
        '/api/auth/logout'         => [['App\Controllers\AuthController', 'logout'],          ['auth']],
        '/api/news'                => [['App\Controllers\NewsController', 'store'],           ['auth']],
        '/api/projects'            => [['App\Controllers\ProjectController', 'store'],        ['auth']],
        '/api/services'            => [['App\Controllers\ServiceController', 'store'],        ['auth']],
        '/api/staff'               => [['App\Controllers\StaffController', 'store'],          ['auth']],
        '/api/queries'             => [['App\Controllers\QueryController', 'store'],          []],
        '/api/users'               => [['App\Controllers\UserController', 'store'],           ['auth']],
        '/api/tags'                => [['App\Controllers\TagController', 'store'],            ['auth']],
    ],
    'PUT' => [
        '/api/news/{id}'           => [['App\Controllers\NewsController', 'update'],         ['auth']],
        '/api/projects/{id}'       => [['App\Controllers\ProjectController', 'update'],      ['auth']],
        '/api/services/{id}'       => [['App\Controllers\ServiceController', 'update'],      ['auth']],
        '/api/staff/{id}'          => [['App\Controllers\StaffController', 'update'],        ['auth']],
        '/api/users/{id}'          => [['App\Controllers\UserController', 'update'],         ['auth']],
        '/api/footer'              => [['App\Controllers\FooterApiController', 'update'],    ['auth']],
    ],
    'PATCH' => [
        '/api/news/{id}/status'       => [['App\Controllers\NewsController', 'updateStatus'],      ['auth']],
        '/api/projects/{id}/status'   => [['App\Controllers\ProjectController', 'updateStatus'],   ['auth']],
        '/api/services/{id}/status'   => [['App\Controllers\ServiceController', 'updateStatus'],   ['auth']],
        '/api/queries/{id}/status'    => [['App\Controllers\QueryController', 'updateStatus'],     ['auth']],
    ],
    'DELETE' => [
        '/api/news/{id}'           => [['App\Controllers\NewsController', 'destroy'],         ['auth']],
        '/api/staff/{id}'          => [['App\Controllers\StaffController', 'destroy'],        ['auth']],
        '/api/users/{id}'          => [['App\Controllers\UserController', 'destroy'],         ['auth']],
        '/api/tags/{id}'           => [['App\Controllers\TagController', 'destroy'],          ['auth']],
        '/api/projects/{id}'       => [['App\Controllers\ProjectController', 'destroy'],      ['auth']],
        '/api/services/{id}'       => [['App\Controllers\ServiceController', 'destroy'],      ['auth']],
    ],
];

// Buscar ruta exacta
if (isset($routes[$method][$uri])) {
    [$handler, $middleware] = $routes[$method][$uri];
    handle(['handler' => $handler, 'middleware' => $middleware]);
    exit;
}

// Buscar ruta con parámetros
if (isset($routes[$method])) {
    foreach ($routes[$method] as $routePath => [$handler, $middleware]) {
        $params = matchRoute($routePath, $uri);
        if ($params !== null) {
            handle(['handler' => $handler, 'middleware' => $middleware, 'params' => $params]);
            exit;
        }
    }
}

// 404
if (str_starts_with($uri, '/api/')) {
    respNotFound('Endpoint no encontrado');
}

http_response_code(404);
echo '<h1>404 - No encontrado</h1>';
