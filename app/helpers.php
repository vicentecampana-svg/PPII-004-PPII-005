<?php

declare(strict_types=1);

use App\Database;
use App\Response;
use App\Session;

// ══════════════════════════════════════════════
//  CONFIGURATION
// ══════════════════════════════════════════════

function config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/app.php';
    }

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
}

// ══════════════════════════════════════════════
//  DATABASE — Conexión PDO singleton a PostgreSQL
// ══════════════════════════════════════════════

// CAL-04: la conexión y las consultas viven en App\Database; estas funciones
// se conservan como envoltorios para no romper los llamados existentes.
function db(): PDO
{
    return Database::connection();
}

function dbQuery(string $sql, array $params = []): PDOStatement
{
    return Database::query($sql, $params);
}

function dbFetchOne(string $sql, array $params = []): ?array
{
    return Database::fetchOne($sql, $params);
}

function dbFetchAll(string $sql, array $params = []): array
{
    return Database::fetchAll($sql, $params);
}

function dbInsert(string $table, array $data): int
{
    return Database::insert($table, $data);
}

function dbUpdate(string $table, array $data, string $where, array $whereParams = []): int
{
    return Database::update($table, $data, $where, $whereParams);
}

function dbDelete(string $table, string $where, array $params = []): int
{
    return Database::delete($table, $where, $params);
}

// ══════════════════════════════════════════════
//  SECURITY & HTTPS
// ══════════════════════════════════════════════

function isHttps(): bool
{
    return (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
}

function sendSecurityHeaders(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), camera=(), microphone=()');

    // SEC-05: Content-Security-Policy. Se permiten orígenes externos solo
    // para las fuentes de Google; el resto del contenido debe provenir del
    // propio origen. `unsafe-inline` se mantiene porque el layout aún incluye
    // scripts y estilos en línea (ver views/layout/footer.php y views/credits.php).
    $csp = "default-src 'self'; "
        . "script-src 'self' 'unsafe-inline'; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        . "font-src 'self' https://fonts.gstatic.com; "
        . "img-src 'self' data:; "
        . "connect-src 'self'; "
        . "frame-ancestors 'none'; "
        . "base-uri 'self'; "
        . "form-action 'self'";
    header('Content-Security-Policy: ' . $csp);

    if (isHttps()) {
        header('Strict-Transport-Security: max-age=31557600; includeSubDomains');
    }
}

function handleCors(): void
{
    $corsConfig = config('cors', []);
    $allowedOrigins = $corsConfig['allowed_origins'] ?? [];
    $allowCredentials = $corsConfig['allow_credentials'] ?? true;

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
        header("Access-Control-Allow-Origin: {$origin}");
        if ($allowCredentials) {
            header('Access-Control-Allow-Credentials: true');
        }
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
        header('Vary: Origin');
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// ══════════════════════════════════════════════
//  SESSION / AUTH
// ══════════════════════════════════════════════

// CAL-04: la sesión y la autenticación viven en App\Session; estas funciones
// se conservan como envoltorios para no romper los llamados existentes.
function sessionStart(): void
{
    Session::start();
}

function enforceSessionIdleTimeout(): void
{
    Session::enforceIdleTimeout();
}

function authCheck(): bool
{
    return Session::check();
}

function authUser(): ?array
{
    return Session::user();
}

function authLogin(int $userId, string $username, int $roleId, string $roleName, bool $mustChangePassword = false): void
{
    Session::login($userId, $username, $roleId, $roleName, $mustChangePassword);
}

function authLogout(): void
{
    Session::logout();
}

function authHasRole(string ...$roles): bool
{
    return Session::hasRole(...$roles);
}

function authMustChangePassword(): bool
{
    return Session::mustChangePassword();
}

function csrfToken(): string
{
    return Session::csrfToken();
}

// ══════════════════════════════════════════════
//  MIDDLEWARE
// ══════════════════════════════════════════════

function isApiRequest(): bool
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    return str_starts_with($uri, '/api/');
}

function mwCsrf(): void
{
    sessionStart();

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $mutatingMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    if (!in_array($method, $mutatingMethods, true)) {
        return;
    }

    // Leer token de cabecera HTTP (X-CSRF-Token), $_POST o JSON input
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if ($token === '' && isApiRequest()) {
        $jsonInput = getJsonInput();
        $token = $jsonInput['csrf_token'] ?? '';
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if ($sessionToken === '' || !hash_equals($sessionToken, (string) $token)) {
        if (isApiRequest()) {
            respForbidden('Token CSRF inválido o ausente.');
        }
        http_response_code(403);
        echo 'Token CSRF inválido o ausente.';
        exit;
    }
}

function mwAuth(): void
{
    // Una sesión abierta no sobrevive a que la cuenta se elimine o desactive.
    if (authCheck() && !(new \App\Repositories\UserRepository())->isActive((int) $_SESSION['user_id'])) {
        authLogout();
    }

    if (!authCheck()) {
        if (isApiRequest()) {
            respUnauthorized();
        }
        header('Location: /login');
        exit;
    }
}

function mwGuest(): void
{
    if (authCheck()) {
        if (isApiRequest()) {
            respError('ALREADY_AUTHENTICATED', 'Ya está autenticado.', 409);
        }
        header('Location: /admin');
        exit;
    }
}

function mwRole(string ...$roles): void
{
    mwAuth();
    $user = authUser();
    $userRole = $user['role_name'] ?? '';

    if (!in_array($userRole, $roles, true)) {
        if (isApiRequest()) {
            respForbidden('Acceso denegado para el rol ' . ($userRole ?: 'desconocido'));
        }
        http_response_code(403);
        echo '<h1>403 - Acceso denegado</h1>';
        exit;
    }
}

function mwForcePasswordChange(): void
{
    if (authCheck() && authMustChangePassword()) {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        $uri = rtrim($uri, '/') ?: '/';
        if ($uri !== '/cambiar-password' && $uri !== '/logout') {
            if (isApiRequest()) {
                respError('PASSWORD_CHANGE_REQUIRED', 'Debe cambiar su contraseña.', 403);
            }
            header('Location: /cambiar-password');
            exit;
        }
    }
}

// ══════════════════════════════════════════════
//  RESPONSE — JSON API
// ══════════════════════════════════════════════

// CAL-04: las respuestas viven en App\Response; estas funciones se conservan
// como envoltorios para no romper los llamados existentes.
function respJson(mixed $data, int $statusCode = 200): never
{
    Response::json($data, $statusCode);
}

function respSuccess(mixed $data = null, int $statusCode = 200): never
{
    Response::success($data, $statusCode);
}

function respCreated(mixed $data = null): never
{
    Response::created($data);
}

function respNoContent(): never
{
    Response::noContent();
}

function respError(string $code, string $message, int $statusCode = 400, ?array $details = null): never
{
    Response::error($code, $message, $statusCode, $details);
}

function respBadRequest(string|array $message = 'Solicitud inválida'): never
{
    Response::badRequest($message);
}

function respUnauthorized(string|array $message = 'No autenticado'): never
{
    Response::unauthorized($message);
}

function respForbidden(string|array $message = 'Acceso denegado'): never
{
    Response::forbidden($message);
}

function respNotFound(string|array $message = 'Recurso no encontrado'): never
{
    Response::notFound($message);
}

function respUnprocessable(array $errors): never
{
    Response::unprocessable($errors);
}

function respServerError(string $message = 'Error interno del servidor'): never
{
    Response::serverError($message);
}

// ══════════════════════════════════════════════
//  UTILIDADES
// ══════════════════════════════════════════════

function getJsonInput(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function mediaUrl(?string $path, string $fallbackType = ''): string
{
    $fallbacks = [
        'proyecto' => '/assets/images/proyecto-1.jpg',
        'staff'    => '/assets/images/staff-1.jpg',
        'noticia'  => '/assets/images/noticia-1.jpg',
        'servicio' => '/assets/images/proyecto-1.jpg',
    ];

    if ($path === null || trim($path) === '') {
        return $fallbacks[$fallbackType] ?? '/assets/images/proyecto-1.jpg';
    }

    $path = trim($path);

    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/assets/')) {
        return $path;
    }

    if (str_starts_with($path, '/uploads/')) {
        return $path;
    }

    if (str_starts_with($path, '/storage/uploads/')) {
        return '/uploads/' . basename($path);
    }

    if (str_starts_with($path, '/')) {
        return $path;
    }

    // Nombre de archivo "pelado" (sin ruta): normalmente es un upload real
    // guardado en public/uploads/. Pero algunos registros de ejemplo guardan
    // el nombre de una imagen de demo que solo existe en public/assets/images/
    // (datos semilla de config/schema.sql); en ese caso hay que servirla desde
    // ahí, porque en /uploads/ nunca existió y se vería como imagen rota.
    $baseName = basename($path);

    if (is_file(dirname(__DIR__) . '/public/assets/images/' . $baseName)) {
        return '/assets/images/' . $baseName;
    }

    return '/uploads/' . ltrim($path, '/');
}

/**
 * URL de un archivo de public/ con su fecha de modificación como versión
 * (?v=...), para que el navegador descargue la versión nueva tras un cambio
 * en vez de seguir usando la guardada en caché.
 */
function assetUrl(string $path): string
{
    $file = dirname(__DIR__) . '/public/' . ltrim($path, '/');

    return is_file($file) ? $path . '?v=' . filemtime($file) : $path;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Escapa un texto multipárrafo escrito en el panel para mostrarlo con
 * `white-space: pre-line`. Cualquier cantidad de saltos de línea seguidos
 * (1, 2 o más, aunque haya espacios entre ellos) se normaliza a una sola
 * línea en blanco, para que la separación entre párrafos sea siempre la misma.
 */
function eParagraphs(?string $value): string
{
    $text = preg_replace('/[ \t]*\R\s*/u', "\n\n", trim($value ?? ''));

    return e($text);
}
