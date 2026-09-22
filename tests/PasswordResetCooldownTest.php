<?php

declare(strict_types=1);

namespace Tests;

use App\Services\PasswordResetService;
use PHPUnit\Framework\TestCase;

final class PasswordResetCooldownTest extends TestCase
{
    private PasswordResetService $service;
    /** @var string[] */
    private array $emailsToCleanUp = [];

    protected function setUp(): void
    {
        $this->service = new PasswordResetService();
    }

    protected function tearDown(): void
    {
        foreach ($this->emailsToCleanUp as $email) {
            dbDelete('password_reset_request', 'LOWER(email) = LOWER(:email)', ['email' => $email]);
        }
        $this->emailsToCleanUp = [];
    }

    private function uniqueEmail(): string
    {
        $email = 'cooldown_' . bin2hex(random_bytes(6)) . '@cooldown-test.local';
        $this->emailsToCleanUp[] = $email;
        return $email;
    }

    public function testSecondsUntilNextAllowedIsZeroWithoutPriorRequest(): void
    {
        $email = $this->uniqueEmail();

        $this->assertSame(0, $this->service->secondsUntilNextAllowed($email));
    }

    public function testSecondRequestWithinCooldownIsBlocked(): void
    {
        $email = $this->uniqueEmail();

        $first = $this->service->requestReset($email, 'http://localhost', '127.0.0.1');
        $this->assertSame(0, $first, 'La primera solicitud debe procesarse sin bloqueo.');

        $second = $this->service->requestReset($email, 'http://localhost', '127.0.0.1');
        $this->assertGreaterThan(0, $second, 'Una segunda solicitud inmediata debe quedar bloqueada por el cooldown.');
        $this->assertLessThanOrEqual(PasswordResetService::RESEND_COOLDOWN_SECONDS, $second);
    }

    public function testCooldownAppliesEvenWhenEmailDoesNotBelongToAnyUser(): void
    {
        // Correo inexistente: el cooldown debe comportarse igual que con
        // uno real, para no filtrar si una cuenta existe.
        $email = $this->uniqueEmail();

        $first = $this->service->requestReset($email, 'http://localhost', '127.0.0.1');
        $this->assertSame(0, $first);

        $remaining = $this->service->secondsUntilNextAllowed($email);
        $this->assertGreaterThan(0, $remaining);
    }

    public function testCooldownExpiresAfterConfiguredWindow(): void
    {
        $email = $this->uniqueEmail();

        // Simula una solicitud ocurrida hace más tiempo que el cooldown,
        // insertando directamente el registro con una fecha pasada.
        dbInsert('password_reset_request', [
            'email'        => $email,
            'ip'           => '127.0.0.1',
            'requested_at' => date('Y-m-d H:i:s', time() - PasswordResetService::RESEND_COOLDOWN_SECONDS - 5),
        ]);

        $this->assertSame(0, $this->service->secondsUntilNextAllowed($email));

        $result = $this->service->requestReset($email, 'http://localhost', '127.0.0.1');
        $this->assertSame(0, $result, 'Pasado el cooldown, la solicitud debe procesarse normalmente.');
    }
}
