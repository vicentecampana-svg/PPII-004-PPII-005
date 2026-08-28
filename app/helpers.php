<?php

declare(strict_types=1);

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

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $db = config('db');

        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $db['host'], $db['port'], $db['database']);

        $pdo = new PDO($dsn, $db['username'], $db['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    return $pdo;
}

function dbQuery(string $sql, array $params = []): PDOStatement
{
    $params = array_map(fn($v) => is_bool($v) ? ($v ? 'true' : 'false') : $v, $params);
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function dbFetchOne(string $sql, array $params = []): ?array
{
    $row = dbQuery($sql, $params)->fetch();
    return $row ?: null;
}

function dbFetchAll(string $sql, array $params = []): array
{
    return dbQuery($sql, $params)->fetchAll();
}

function dbInsert(string $table, array $data): int
{
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders}) RETURNING id";
    return (int) dbQuery($sql, array_values($data))->fetchColumn();
}

function dbUpdate(string $table, array $data, string $where, array $whereParams = []): int
{
    $setParts = [];
    $params = [];
    foreach ($data as $col => $val) {
        $paramKey = 'set_' . $col;
        $setParts[] = "{$col} = :{$paramKey}";
        $params[$paramKey] = $val;
    }
    $set = implode(', ', $setParts);
    $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
    return dbQuery($sql, array_merge($params, $whereParams))->rowCount();
}

function dbDelete(string $table, string $where, array $params = []): int
{
    return dbQuery("DELETE FROM {$table} WHERE {$where}", $params)->rowCount();
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
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), camera=(), microphone=()');

    if (isHttps()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
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

function sessionStart(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $secure = isHttps();
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();
    }
}

function authCheck(): bool
{
    sessionStart();
    return isset($_SESSION['user_id']);
}

function authUser(): ?array
{
    sessionStart();
    if (!authCheck()) {
        return null;
    }
    return [
        'id'                   => $_SESSION['user_id'],
        'username'             => $_SESSION['username'],
        'role_id'              => $_SESSION['role_id'],
        'role_name'            => $_SESSION['role_name'],
        'must_change_password' => $_SESSION['must_change_password'] ?? false,
    ];
}

function authLogin(int $userId, string $username, int $roleId, string $roleName, bool $mustChangePassword = false): void
{
    sessionStart();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role_id'] = $roleId;
    $_SESSION['role_name'] = $roleName;
    $_SESSION['must_change_password'] = $mustChangePassword;
}

function authLogout(): void
{
    sessionStart();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

function authHasRole(string ...$roles): bool
{
    $user = authUser();
    return $user !== null && in_array($user['role_name'], $roles, true);
}

function authMustChangePassword(): bool
{
    sessionStart();
    return !empty($_SESSION['must_change_password']);
}

/**
 * Token CSRF para formularios HTML (no-API). Se genera una vez por sesión
 * y mwCsrf() lo valida contra $_POST['csrf_token'] en cada POST.
 */
function csrfToken(): string
{
    sessionStart();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
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
    if (isApiRequest()) {
        return;
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', (string) $token)) {
            http_response_code(403);
            echo 'Token CSRF inválido.';
            exit;
        }
    }
}

function mwAuth(): void
{
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

function respJson(mixed $data, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function respSuccess(mixed $data = null, int $statusCode = 200): never
{
    respJson(['success' => true, 'data' => $data], $statusCode);
}

function respCreated(mixed $data = null): never
{
    respSuccess($data, 201);
}

function respNoContent(): never
{
    http_response_code(204);
    exit;
}

function respError(string $code, string $message, int $statusCode = 400, ?array $details = null): never
{
    $payload = ['success' => false, 'error' => ['code' => $code, 'message' => $message]];
    if ($details !== null) {
        $payload['error']['details'] = $details;
    }
    respJson($payload, $statusCode);
}

function respBadRequest(string|array $message = 'Solicitud inválida'): never
{
    respError('BAD_REQUEST', is_string($message) ? $message : 'Solicitud inválida', 400, is_array($message) ? $message : null);
}

function respUnauthorized(string|array $message = 'No autenticado'): never
{
    respError('UNAUTHORIZED', is_string($message) ? $message : 'No autenticado', 401, is_array($message) ? $message : null);
}

function respForbidden(string|array $message = 'Acceso denegado'): never
{
    respError('FORBIDDEN', is_string($message) ? $message : 'Acceso denegado', 403, is_array($message) ? $message : null);
}

function respNotFound(string|array $message = 'Recurso no encontrado'): never
{
    respError('NOT_FOUND', is_string($message) ? $message : 'Recurso no encontrado', 404, is_array($message) ? $message : null);
}

function respUnprocessable(array $errors): never
{
    respJson(['success' => false, 'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Error de validación', 'details' => $errors]], 422);
}

function respServerError(string $message = 'Error interno del servidor'): never
{
    respError('SERVER_ERROR', $message, 500);
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
    ];

    if ($path === null || $path === '') {
        return $fallbacks[$fallbackType] ?? '/assets/images/proyecto-1.jpg';
    }

    if (str_starts_with($path, 'http') || str_starts_with($path, '/')) {
        return $path;
    }

    return '/storage/uploads/' . $path;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
