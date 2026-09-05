<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

class SecurityHardeningTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER = [];
        $_SESSION = [];
    }

    public function testIsHttpsDetection(): void
    {
        $_SERVER['HTTPS'] = 'on';
        $this->assertTrue(isHttps());

        $_SERVER['HTTPS'] = 'off';
        $this->assertFalse(isHttps());

        unset($_SERVER['HTTPS']);
        $_SERVER['SERVER_PORT'] = '443';
        $this->assertTrue(isHttps());

        $_SERVER['SERVER_PORT'] = '80';
        $this->assertFalse(isHttps());

        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $this->assertTrue(isHttps());

        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';
        $this->assertFalse(isHttps());
    }

    public function testSessionCookieParameters(): void
    {
        // Check session settings
        sessionStart();
        $params = session_get_cookie_params();

        $this->assertTrue($params['httponly'], 'Session cookie must be HttpOnly.');
        $this->assertSame('Lax', $params['samesite'], 'Session cookie SameSite must be Lax.');
        $this->assertSame('/', $params['path'], 'Session cookie path must be /.');
    }

    public function testAuthLoginStoresSession(): void
    {
        authLogin(10, 'secureuser', 2, 'admin', false);

        $this->assertTrue(authCheck());
        $this->assertSame(10, authUser()['id']);
        $this->assertSame('secureuser', authUser()['username']);
        $this->assertSame('admin', authUser()['role_name']);
        $this->assertFalse(authMustChangePassword());
    }

    public function testCorsAllowedOrigins(): void
    {
        $allowed = 'http://localhost:8080';
        $corsConfig = config('cors', []);
        $this->assertContains($allowed, $corsConfig['allowed_origins']);

        $disallowed = 'http://malicious-site.com';
        $this->assertNotContains($disallowed, $corsConfig['allowed_origins']);
    }
}
