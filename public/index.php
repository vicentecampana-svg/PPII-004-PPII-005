<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// ══════════════════════════════════════════════
//  HANDLER — ejecuta controller + middleware
// ══════════════════════════════════════════════

function handle(array $route): void
{
    foreach ($route['middleware'] as $mw) {
        if (str_starts_with($mw, 'role:')) {
            $roles = explode(',', substr($mw, 5));
            mwRole(...$roles);
            continue;
        }

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
        $params = array_map(fn($v) => is_numeric($v) ? (int) $v : $v, $route['params']);
        $controller->$action(...$params);
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
//  ROUTING
// ══════════════════════════════════════════════

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';

// CORS preflight
if ($method === 'OPTIONS') {
    http_response_code(204);
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
    header('Access-Control-Max-Age: 86400');
    exit;
}

// Definición de rutas API
$routes = [
    'GET' => [
        '/'                        => [['App\Controllers\HomeController', 'index'],          []],
        '/proyectos'               => [['App\Controllers\ProyectosController', 'index'],     []],
        '/login'                   => [['App\Controllers\LoginController', 'show'],          ['guest']],
        '/admin'                   => [['App\Controllers\AdminController', 'index'],         ['auth', 'force_password_change']],
        '/logout'                  => [['App\Controllers\LogoutController', 'logout'],       []],
        '/cambiar-password'        => [['App\Controllers\PasswordController', 'show'],       ['auth']],
        '/api/auth/me'             => [['App\Controllers\AuthController', 'me'],             ['auth']],
        '/api/news'                => [['App\Controllers\NewsController', 'index'],           []],
        '/api/news/{id}'           => [['App\Controllers\NewsController', 'show'],            []],
        '/api/projects'            => [['App\Controllers\ProjectController', 'index'],        []],
        '/api/projects/{id}'       => [['App\Controllers\ProjectController', 'show'],         []],
        '/api/services'            => [['App\Controllers\ServiceController', 'index'],        []],
        '/api/services/{id}'       => [['App\Controllers\ServiceController', 'show'],         []],
        '/api/staff'               => [['App\Controllers\StaffController', 'index'],          []],
        '/api/staff/{id}'          => [['App\Controllers\StaffController', 'show'],           []],
        '/api/queries'             => [['App\Controllers\QueryController', 'index'],          ['auth', 'role:superadmin,admin,editor']],
        '/api/queries/{id}'        => [['App\Controllers\QueryController', 'show'],           ['auth', 'role:superadmin,admin,editor']],
        '/api/users'               => [['App\Controllers\UserController', 'index'],           ['auth', 'role:superadmin,admin']],
        '/api/users/{id}'          => [['App\Controllers\UserController', 'show'],            ['auth', 'role:superadmin,admin']],
        '/api/roles'               => [['App\Controllers\UserController', 'roles'],           ['auth', 'role:superadmin,admin']],
        '/api/tags'                => [['App\Controllers\TagController', 'index'],            []],
        '/api/audits'              => [['App\Controllers\AuditController', 'index'],          ['auth', 'role:superadmin']],
        '/api/footer'              => [['App\Controllers\FooterApiController', 'show'],       []],
    ],
    'POST' => [
        '/login'                   => [['App\Controllers\LoginController', 'submit'],        ['guest', 'csrf']],
        '/logout'                  => [['App\Controllers\LogoutController', 'logout'],       []],
        '/cambiar-password'        => [['App\Controllers\PasswordController', 'submit'],     ['auth', 'csrf']],
        '/api/auth/login'          => [['App\Controllers\AuthController', 'login'],           ['guest']],
        '/api/auth/logout'         => [['App\Controllers\AuthController', 'logout'],          ['auth']],
        '/api/news'                => [['App\Controllers\NewsController', 'store'],           ['auth', 'role:superadmin,admin,editor,redactor']],
        '/api/projects'            => [['App\Controllers\ProjectController', 'store'],        ['auth', 'role:superadmin,admin']],
        '/api/services'            => [['App\Controllers\ServiceController', 'store'],        ['auth', 'role:superadmin,admin']],
        '/api/staff'               => [['App\Controllers\StaffController', 'store'],          ['auth', 'role:superadmin,admin']],
        '/api/queries'             => [['App\Controllers\QueryController', 'store'],          []],
        '/api/users'               => [['App\Controllers\UserController', 'store'],           ['auth', 'role:superadmin,admin']],
        '/api/tags'                => [['App\Controllers\TagController', 'store'],            ['auth', 'role:superadmin,admin,editor']],
    ],
    'PUT' => [
        '/api/news/{id}'           => [['App\Controllers\NewsController', 'update'],         ['auth', 'role:superadmin,admin,editor,redactor']],
        '/api/projects/{id}'       => [['App\Controllers\ProjectController', 'update'],      ['auth', 'role:superadmin,admin']],
        '/api/services/{id}'       => [['App\Controllers\ServiceController', 'update'],      ['auth', 'role:superadmin,admin']],
        '/api/staff/{id}'          => [['App\Controllers\StaffController', 'update'],        ['auth', 'role:superadmin,admin']],
        '/api/users/{id}'          => [['App\Controllers\UserController', 'update'],         ['auth', 'role:superadmin,admin']],
        '/api/footer'              => [['App\Controllers\FooterApiController', 'update'],    ['auth', 'role:superadmin,admin']],
    ],
    'PATCH' => [
        '/api/news/{id}/status'       => [['App\Controllers\NewsController', 'updateStatus'],      ['auth', 'role:superadmin,admin,editor']],
        '/api/projects/{id}/status'   => [['App\Controllers\ProjectController', 'updateStatus'],   ['auth', 'role:superadmin,admin']],
        '/api/services/{id}/status'   => [['App\Controllers\ServiceController', 'updateStatus'],   ['auth', 'role:superadmin,admin']],
        '/api/queries/{id}/status'    => [['App\Controllers\QueryController', 'updateStatus'],     ['auth', 'role:superadmin,admin,editor']],
    ],
    'DELETE' => [
        '/api/news/{id}'           => [['App\Controllers\NewsController', 'destroy'],         ['auth', 'role:superadmin,admin,editor,redactor']],
        '/api/staff/{id}'          => [['App\Controllers\StaffController', 'destroy'],        ['auth', 'role:superadmin,admin']],
        '/api/users/{id}'          => [['App\Controllers\UserController', 'destroy'],         ['auth', 'role:superadmin,admin']],
        '/api/tags/{id}'           => [['App\Controllers\TagController', 'destroy'],          ['auth', 'role:superadmin,admin,editor']],
        '/api/projects/{id}'       => [['App\Controllers\ProjectController', 'destroy'],      ['auth', 'role:superadmin,admin']],
        '/api/services/{id}'       => [['App\Controllers\ServiceController', 'destroy'],      ['auth', 'role:superadmin,admin']],
    ],
];

// Buscar ruta exacta
if (isset($routes[$method][$uri])) {
    [$handler, $middleware] = $routes[$method][$uri];
    handle(['handler' => $handler, 'middleware' => $middleware]);
    exit;
}

// Buscar ruta dinámica
if (isset($routes[$method])) {
    foreach ($routes[$method] as $routePath => [$handler, $middleware]) {
        if (!str_contains($routePath, '{')) {
            continue;
        }

        $params = matchRoute($routePath, $uri);
        if ($params !== null) {
            handle(['handler' => $handler, 'middleware' => $middleware, 'params' => $params]);
            exit;
        }
    }
}

// 404
if (isApiRequest()) {
    respNotFound();
} else {
    http_response_code(404);
    echo '<h1>404 - No encontrado</h1>';
}
