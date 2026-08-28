<?php

declare(strict_types=1);

// ══════════════════════════════════════════════
//  DATABASE — Conexión PDO singleton a PostgreSQL
// ══════════════════════════════════════════════

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $config = require dirname(__DIR__) . '/config/app.php';
        $db = $config['db'];

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
    $params = [];
    $setParts = [];
    foreach ($data as $col => $val) {
        $placeholder = 'set_' . str_replace(['-', '.'], '_', $col);
        $setParts[] = "{$col} = :{$placeholder}";
        $params[$placeholder] = $val;
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
//  SESSION / AUTH
// ══════════════════════════════════════════════

function sessionStart(): void
{
    if (session_status() === PHP_SESSION_NONE) {
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
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role_id'] = $roleId;
    $_SESSION['role_name'] = $roleName;
    $_SESSION['must_change_password'] = $mustChangePassword;
}

function authLogout(): void
{
    sessionStart();
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
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    return str_starts_with($uri, '/api/');
}

function mwCsrf(): void
{
    sessionStart();
    if (isApiRequest()) {
        return;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
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

function mwForcePasswordChange(): void
{
    if (authCheck() && authMustChangePassword()) {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
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
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
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
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
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
