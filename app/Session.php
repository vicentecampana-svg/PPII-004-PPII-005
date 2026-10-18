<?php

declare(strict_types=1);

namespace App;

/**
 * CAL-04: sesión y autenticación en una clase pequeña.
 *
 * Reemplaza las funciones globales sessionStart()/authCheck()/authLogin()/
 * authLogout()/csrfToken() de helpers.php.
 */
final class Session
{
    public static function start(): void
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

        self::enforceIdleTimeout();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public static function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * IU-002 / RNF-SEC-004: las sesiones autenticadas del panel expiran a los
     * 15 minutos de inactividad y deben autenticarse nuevamente.
     */
    public static function enforceIdleTimeout(): void
    {
        if (!isset($_SESSION['user_id'])) {
            return;
        }

        $timeout = (int) config('session.idle_timeout_seconds', 900);
        $last = $_SESSION['_last_activity'] ?? null;

        if ($last === null) {
            $_SESSION['_last_activity'] = time();

            return;
        }

        if (time() - (int) $last > $timeout) {
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['_session_expired'] = true;
        } else {
            $_SESSION['_last_activity'] = time();
        }
    }

    public static function check(): bool
    {
        self::start();

        return isset($_SESSION['user_id']);
    }

    public static function user(): ?array
    {
        self::start();
        if (!self::check()) {
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

    public static function login(int $userId, string $username, int $roleId, string $roleName, bool $mustChangePassword = false): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['role_id'] = $roleId;
        $_SESSION['role_name'] = $roleName;
        $_SESSION['must_change_password'] = $mustChangePassword;
        $_SESSION['_last_activity'] = time();
    }

    public static function logout(): void
    {
        self::start();
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

    public static function hasRole(string ...$roles): bool
    {
        $user = self::user();

        return $user !== null && in_array($user['role_name'], $roles, true);
    }

    public static function mustChangePassword(): bool
    {
        self::start();

        return !empty($_SESSION['must_change_password']);
    }

    /**
     * Token CSRF para formularios HTML y API. Se genera por sesión y
     * mwCsrf() lo valida en cada petición mutante.
     */
    public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}
