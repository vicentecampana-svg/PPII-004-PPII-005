<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * IU-002 / RNF-SEC-004: la sesión del panel expira a los 15 minutos
 * de inactividad y debe autenticarse nuevamente.
 */
class SessionTimeoutTest extends TestCase
{
    protected function setUp(): void
    {
        sessionStart();
        $_SESSION = [];
    }

    public function testAuthenticatedSessionExpiresAfterIdleTimeout(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['_last_activity'] = time() - 901; // 15 min + 1 s de inactividad

        sessionStart();

        $this->assertArrayNotHasKey('user_id', $_SESSION, 'La sesión debe quedar cerrada tras el timeout.');
        $this->assertTrue($_SESSION['_session_expired'] ?? false, 'Debe marcarse el flag de sesión expirada.');
    }

    public function testAuthenticatedSessionSurvivesWithinIdleTimeout(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['_last_activity'] = time() - 100;

        sessionStart();

        $this->assertSame(1, $_SESSION['user_id']);
        $this->assertArrayNotHasKey('_session_expired', $_SESSION);
        $this->assertGreaterThanOrEqual(time() - 2, $_SESSION['_last_activity'], 'La actividad debe refrescarse.');
    }

    public function testSessionWithoutLastActivityGetsGraceInsteadOfExpiring(): void
    {
        $_SESSION['user_id'] = 1;

        sessionStart();

        $this->assertSame(1, $_SESSION['user_id']);
        $this->assertArrayHasKey('_last_activity', $_SESSION);
        $this->assertArrayNotHasKey('_session_expired', $_SESSION);
    }

    public function testGuestSessionsAreNotExpiredByIdleTimeout(): void
    {
        sessionStart();

        $this->assertArrayNotHasKey('_session_expired', $_SESSION);
    }
}
