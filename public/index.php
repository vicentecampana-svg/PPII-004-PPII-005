<?php

declare(strict_types=1);

// Autoloader PSR-4 para el namespace App\ y helpers
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
//  SEC-08 — MANEJADOR GLOBAL DE EXCEPCIONES
// ══════════════════════════════════════════════
//  Sin esto, una caída de la base de datos termina en un "Uncaught
//  PDOException" con página en blanco (o con la traza visible si
//  display_errors está activo). Acá se registra el error en el log y se
//  entrega una respuesta 500 genérica, sin exponer detalles internos.
set_exception_handler(static function (\Throwable $e): void {
    error_log(sprintf(
        '[SFL ULS] Excepción no controlada: %s en %s:%d',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    if (isApiRequest()) {
        respServerError();
    }

    http_response_code(500);
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Error 500</title></head><body>'
        . '<h1>Error interno del servidor</h1>'
        . '<p>Ocurrió un error inesperado. Por favor, intenta nuevamente más tarde.</p>'
        . '</body></html>';
});

register_shutdown_function(static function (): void {
    $err = error_get_last();
    if ($err === null || !in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }

    error_log(sprintf(
        '[SFL ULS] Error fatal: %s en %s:%d',
        $err['message'],
        $err['file'],
        $err['line']
    ));

    if (isApiRequest()) {
        respServerError();
    }

    http_response_code(500);
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Error 500</title></head><body>'
        . '<h1>Error interno del servidor</h1>'
        . '<p>Ocurrió un error inesperado. Por favor, intenta nuevamente más tarde.</p>'
        . '</body></html>';
});

// ══════════════════════════════════════════════
//  HANDLER — ejecuta controller + middleware
// ══════════════════════════════════════════════

function handle(array $route): void
{
    // RF-AUT-003: el cambio de contraseña obligatorio se impone en todo el
    // panel (/admin*), tanto en GET como en las acciones POST.
    $path = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '', '/') ?: '/';
    if (str_starts_with($path, '/admin')) {
        mwForcePasswordChange();
    }

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
        '/proyectos/{id}'          => [['App\Controllers\ProyectosController', 'show'],      []],
        '/servicios'               => [['App\Controllers\ServiciosController', 'index'],     []],
        '/staff'                   => [['App\Controllers\StaffWebController', 'index'],      []],
        '/noticias'                => [['App\Controllers\NoticiasController', 'index'],      []],
        '/noticias/{id}'           => [['App\Controllers\NoticiasController', 'show'],       []],
        '/contacto'                => [['App\Controllers\ContactoController', 'index'],      []],
        '/contactenos'             => [['App\Controllers\ContactoController', 'index'],      []],
        '/credits'                 => [['App\Controllers\CreditsController', 'index'],        []],
        '/creditos'                => [['App\Controllers\CreditsController', 'index'],        []],
        '/login'                        => [['App\Controllers\LoginController', 'show'],              ['guest']],
        '/captcha'                      => [['App\Controllers\CaptchaController', 'show'],            []],
        '/api/captcha'                  => [['App\Controllers\CaptchaController', 'api'],             []],
        '/admin'                        => [['App\Controllers\AdminController', 'index'],              ['auth', 'force_password_change']],
        '/cambiar-password'             => [['App\Controllers\PasswordController', 'show'],            ['auth']],
        '/recuperar-password'           => [['App\Controllers\PasswordRecoveryController', 'showRequest'],  ['guest']],
        '/restablecer-password/{token}' => [['App\Controllers\PasswordRecoveryController', 'showReset'],    ['guest']],
        '/api/auth/me'             => [['App\Controllers\AuthController', 'me'],             ['auth']],
        '/api/news'                => [['App\Controllers\NewsController', 'index'],           []],
        '/api/news/{id}'           => [['App\Controllers\NewsController', 'show'],            []],
        '/api/projects'            => [['App\Controllers\ProjectController', 'index'],        []],
        '/api/projects/{id}'       => [['App\Controllers\ProjectController', 'show'],         []],
        '/api/services'            => [['App\Controllers\ServiceController', 'index'],        []],
        '/api/services/{id}'       => [['App\Controllers\ServiceController', 'show'],         []],
        '/api/staff'               => [['App\Controllers\StaffController', 'index'],          []],
        '/api/staff/{id}'          => [['App\Controllers\StaffController', 'show'],           []],
        '/api/credits'             => [['App\Controllers\CreditController', 'index'],         []],
        '/api/credits/{id}'        => [['App\Controllers\CreditController', 'show'],          []],
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
        '/login'                        => [['App\Controllers\LoginController', 'submit'],                  ['guest', 'csrf']],
        '/contacto'                     => [['App\Controllers\ContactoController', 'submit'],              ['csrf']],
        '/credits'                      => [['App\Controllers\CreditsController', 'submit'],               ['csrf']],
        '/creditos'                     => [['App\Controllers\CreditsController', 'submit'],               ['csrf']],
        '/api/credits/contact'          => [['App\Controllers\CreditController', 'contact'],              []],
        '/logout'                       => [['App\Controllers\LogoutController', 'logout'],                ['csrf']],
        '/cambiar-password'             => [['App\Controllers\PasswordController', 'submit'],              ['auth', 'csrf']],
        '/recuperar-password'           => [['App\Controllers\PasswordRecoveryController', 'submitRequest'], ['guest', 'csrf']],
        '/restablecer-password/{token}' => [['App\Controllers\PasswordRecoveryController', 'submitReset'],  ['guest', 'csrf']],

        '/admin/proyectos'         => [['App\Controllers\AdminProjectsController', 'saveProject'],    ['auth', 'csrf']],
        '/admin/proyectos/delete'  => [['App\Controllers\AdminProjectsController', 'deleteProject'],  ['auth', 'csrf']],
        '/admin/staff'             => [['App\Controllers\AdminStaffController', 'saveStaff'],      ['auth', 'csrf']],
        '/admin/staff/delete'      => [['App\Controllers\AdminStaffController', 'deleteStaff'],    ['auth', 'csrf']],
        '/admin/creditos'          => [['App\Controllers\AdminCreditsController', 'saveCreditMember'],   ['auth', 'csrf']],
        '/admin/creditos/delete'   => [['App\Controllers\AdminCreditsController', 'deleteCreditMember'], ['auth', 'csrf']],
        '/admin/noticias'          => [['App\Controllers\AdminNewsController', 'saveNews'],         ['auth', 'csrf']],
        '/admin/noticias/delete'   => [['App\Controllers\AdminNewsController', 'deleteNews'],       ['auth', 'csrf']],
        '/admin/noticias/status'   => [['App\Controllers\AdminNewsController', 'toggleNewsStatus'], ['auth', 'csrf']],
        '/admin/sobre-nosotros'    => [['App\Controllers\AdminContentController', 'saveSobreNosotros'],['auth', 'csrf']],
        '/admin/footer/links'          => [['App\Controllers\AdminFooterController', 'saveFooterLink'],   ['auth', 'csrf']],
        '/admin/footer/links/delete'   => [['App\Controllers\AdminFooterController', 'deleteFooterLink'], ['auth', 'csrf']],
        '/admin/footer/social'         => [['App\Controllers\AdminFooterController', 'saveFooterSocial'], ['auth', 'csrf']],
        '/admin/usuarios'              => [['App\Controllers\AdminUsersController', 'saveUser'],         ['auth', 'csrf']],
        '/admin/usuarios/delete'       => [['App\Controllers\AdminUsersController', 'deleteUser'],       ['auth', 'csrf']],
        '/admin/usuarios/role'         => [['App\Controllers\AdminUsersController', 'updateUserRole'],   ['auth', 'csrf']],
        '/api/auth/login'          => [['App\Controllers\AuthController', 'login'],           ['guest']],
        '/api/auth/logout'         => [['App\Controllers\AuthController', 'logout'],          ['auth', 'csrf']],
        '/api/news'                => [['App\Controllers\NewsController', 'store'],           ['auth', 'csrf', 'role:superadmin,admin,editor,redactor']],
        '/api/projects'            => [['App\Controllers\ProjectController', 'store'],        ['auth', 'csrf', 'role:superadmin,admin']],
        '/api/services'            => [['App\Controllers\ServiceController', 'store'],        ['auth', 'csrf', 'role:superadmin,admin']],
        '/api/staff'               => [['App\Controllers\StaffController', 'store'],          ['auth', 'csrf', 'role:superadmin,admin']],
        '/api/credits'             => [['App\Controllers\CreditController', 'store'],          ['auth', 'csrf', 'role:superadmin']],
        '/api/queries'             => [['App\Controllers\QueryController', 'store'],          []],
        '/api/users'               => [['App\Controllers\UserController', 'store'],           ['auth', 'csrf', 'role:superadmin,admin']],
        '/api/tags'                => [['App\Controllers\TagController', 'store'],            ['auth', 'csrf', 'role:superadmin,admin,editor']],
        '/api/media/upload'        => [['App\Controllers\MediaController', 'upload'],         ['auth', 'csrf']],
    ],
    'PUT' => [
        '/api/news/{id}'           => [['App\Controllers\NewsController', 'update'],         ['auth', 'csrf', 'role:superadmin,admin,editor,redactor']],
        '/api/projects/{id}'       => [['App\Controllers\ProjectController', 'update'],      ['auth', 'csrf', 'role:superadmin,admin']],
        '/api/services/{id}'       => [['App\Controllers\ServiceController', 'update'],      ['auth', 'csrf', 'role:superadmin,admin']],
        '/api/staff/{id}'          => [['App\Controllers\StaffController', 'update'],        ['auth', 'csrf', 'role:superadmin,admin']],
        '/api/credits/{id}'        => [['App\Controllers\CreditController', 'update'],        ['auth', 'csrf', 'role:superadmin']],
        '/api/users/{id}'          => [['App\Controllers\UserController', 'update'],         ['auth', 'csrf', 'role:superadmin,admin']],
        '/api/footer'              => [['App\Controllers\FooterApiController', 'update'],    ['auth', 'csrf', 'role:superadmin,admin']],
    ],
    'PATCH' => [
        '/api/news/{id}/status'       => [['App\Controllers\NewsController', 'updateStatus'],      ['auth', 'csrf', 'role:superadmin,admin,editor']],
        '/api/projects/{id}/status'   => [['App\Controllers\ProjectController', 'updateStatus'],   ['auth', 'csrf', 'role:superadmin,admin']],
        '/api/services/{id}/status'   => [['App\Controllers\ServiceController', 'updateStatus'],   ['auth', 'csrf', 'role:superadmin,admin']],
        '/api/queries/{id}/status'    => [['App\Controllers\QueryController', 'updateStatus'],     ['auth', 'csrf', 'role:superadmin,admin,editor']],
    ],
    'DELETE' => [
        '/api/news/{id}'           => [['App\Controllers\NewsController', 'destroy'],         ['auth', 'csrf', 'role:superadmin,admin,editor,redactor']],
        '/api/staff/{id}'          => [['App\Controllers\StaffController', 'destroy'],        ['auth', 'csrf', 'role:superadmin,admin']],
        '/api/credits/{id}'        => [['App\Controllers\CreditController', 'destroy'],       ['auth', 'csrf', 'role:superadmin']],
        '/api/users/{id}'          => [['App\Controllers\UserController', 'destroy'],         ['auth', 'csrf', 'role:superadmin,admin']],
        '/api/tags/{id}'           => [['App\Controllers\TagController', 'destroy'],          ['auth', 'csrf', 'role:superadmin,admin,editor']],
        '/api/projects/{id}'       => [['App\Controllers\ProjectController', 'destroy'],      ['auth', 'csrf', 'role:superadmin,admin']],
        '/api/services/{id}'       => [['App\Controllers\ServiceController', 'destroy'],      ['auth', 'csrf', 'role:superadmin,admin']],
        '/api/media'               => [['App\Controllers\MediaController', 'destroy'],        ['auth', 'csrf']],
    ],
];

// CAL-05: resolución de rutas con el Router dedicado
$router = new \App\Router($routes);

$route = $router->resolve($method, $uri);
if ($route !== null) {
    handle($route);
    exit;
}

// 405: la ruta existe, pero no para este método HTTP
if ($router->methodNotAllowed($method, $uri)) {
    if (isApiRequest()) {
        respError('METHOD_NOT_ALLOWED', 'Método no permitido.', 405);
    }
    http_response_code(405);
    echo '<h1>405 - Método no permitido</h1>';
    exit;
}

// 404
if (isApiRequest()) {
    respNotFound();
} else {
    http_response_code(404);
    echo '<h1>404 - No encontrado</h1>';
}
