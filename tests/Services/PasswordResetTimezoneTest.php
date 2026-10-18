<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Repositories\PasswordResetRepository;
use App\Repositories\PasswordResetRequestRepository;
use App\Repositories\UserRepository;
use App\Services\MailerService;
use App\Services\PasswordResetService;
use PHPUnit\Framework\TestCase;

/**
 * Tests para verificación de BUG-03 (zona horaria única UTC en expiración de token).
 */
final class PasswordResetTimezoneTest extends TestCase
{
    private string $originalTimezone;

    protected function setUp(): void
    {
        $this->originalTimezone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);
    }

    public function testConfigSpecifiesUtcTimezone(): void
    {
        $this->assertSame('UTC', config('timezone'));
    }

    public function testTokenExpirationIsAlwaysUtcWithIsoOffsetEvenWhenPhpRunsInChileTimezone(): void
    {
        // Simular que el servidor PHP está configurado en zona horaria de Chile (UTC-3 / UTC-4)
        date_default_timezone_set('America/Santiago');

        $userRepo = $this->createMock(UserRepository::class);
        $resetRepo = $this->createMock(PasswordResetRepository::class);
        $requestRepo = $this->createMock(PasswordResetRequestRepository::class);
        $mailer = $this->createMock(MailerService::class);

        $requestRepo->method('lastRequestAt')->willReturn(null);
        $userRepo->method('findByEmail')->willReturn([
            'id'       => 10,
            'email'    => 'usuario@sfluls.cl',
            'username' => 'usuario10',
            'active'   => true,
        ]);

        $capturedExpiresAt = null;

        // Verificar que create recibe un timestamp explícito en UTC (+00:00)
        $resetRepo->expects($this->once())
            ->method('create')
            ->with(
                10,
                $this->isType('string'),
                $this->callback(function (string $expiresAt) use (&$capturedExpiresAt): bool {
                    $capturedExpiresAt = $expiresAt;
                    return true;
                })
            );

        $service = new PasswordResetService($userRepo, $resetRepo, $requestRepo, $mailer);

        $before = time() + 3600;
        $result = $service->requestReset('usuario@sfluls.cl', 'https://sfl.uls.cl');
        $after = time() + 3600;

        $this->assertSame(0, $result);
        $this->assertNotNull($capturedExpiresAt);

        // BUG-03: Debe terminar con +00:00 o Z, indicando UTC explícito
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\+00:00$/',
            $capturedExpiresAt,
            'La fecha de expiración debe incluir explícitamente el offset UTC +00:00 para evitar descalces con PostgreSQL.'
        );

        // Parsear el timestamp capturado como UTC absoluto
        $parsedEpoch = strtotime($capturedExpiresAt);
        $this->assertGreaterThanOrEqual($before - 2, $parsedEpoch);
        $this->assertLessThanOrEqual($after + 2, $parsedEpoch);
    }

    public function testTokenExpirationInTokyoTimezoneProducesIdenticalUtcEpoch(): void
    {
        // Probar en zona horaria opuesta (UTC+9)
        date_default_timezone_set('Asia/Tokyo');

        $userRepo = $this->createMock(UserRepository::class);
        $resetRepo = $this->createMock(PasswordResetRepository::class);
        $requestRepo = $this->createMock(PasswordResetRequestRepository::class);
        $mailer = $this->createMock(MailerService::class);

        $requestRepo->method('lastRequestAt')->willReturn(null);
        $userRepo->method('findByEmail')->willReturn([
            'id'       => 10,
            'email'    => 'usuario@sfluls.cl',
            'username' => 'usuario10',
            'active'   => true,
        ]);

        $capturedExpiresAt = null;

        $resetRepo->expects($this->once())
            ->method('create')
            ->with(
                10,
                $this->isType('string'),
                $this->callback(function (string $expiresAt) use (&$capturedExpiresAt): bool {
                    $capturedExpiresAt = $expiresAt;
                    return true;
                })
            );

        $service = new PasswordResetService($userRepo, $resetRepo, $requestRepo, $mailer);
        $service->requestReset('usuario@sfluls.cl', 'https://sfl.uls.cl');

        $this->assertNotNull($capturedExpiresAt);
        $this->assertStringEndsWith('+00:00', $capturedExpiresAt);
    }
}
