<?php

declare(strict_types=1);

namespace Tests;

use App\Repositories\QueryRepository;
use App\Services\ContactDigestService;
use App\Services\FooterService;
use App\Services\FormGuardService;
use App\Services\MailBudgetService;
use App\Services\MailerService;
use App\Services\PasswordResetService;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;

/**
 * Protección de formularios y de la cuota de correo: tope por ámbito y aviso
 * agrupado (SEC-09), CAPTCHA y cierre de rutas alternativas (SEC-10), límite
 * de intentos al cambiar la contraseña (SEC-11) y no revelar qué cuentas
 * existen (SEC-12).
 */
final class SecurityStackTest extends TestCase
{
    private const ENV = [
        'MAIL_DAILY_LIMIT', 'MAIL_SHARE_CONTACT', 'MAIL_SHARE_CREDITS', 'MAIL_SHARE_PASSWORD_RESET',
        'CONTACT_DIGEST_INTERVAL_MINUTES', 'CONTACT_NOTIFY_EMAIL',
    ];

    /** @var array<string,string|false> */
    private array $envBackup = [];
    /** @var int[] */
    private array $mailLogIds = [];
    /** @var string[] */
    private array $formEmails = [];

    protected function setUp(): void
    {
        foreach (self::ENV as $name) {
            $this->envBackup[$name] = getenv($name);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->envBackup as $name => $value) {
            putenv($value === false ? $name : "{$name}={$value}");
        }
        foreach ($this->mailLogIds as $id) {
            dbDelete('mail_log', 'id = :id', ['id' => $id]);
        }
        foreach ($this->formEmails as $email) {
            dbDelete('form_submission', 'LOWER(email) = LOWER(:email)', ['email' => $email]);
        }
    }

    // ── SEC-09: tope de correos por ámbito ────────────────────────────────

    public function testScopeLimitIsAPercentageOfTheDailyLimit(): void
    {
        putenv('MAIL_DAILY_LIMIT=100');
        putenv('MAIL_SHARE_CONTACT=20');
        putenv('MAIL_SHARE_CREDITS=');
        putenv('MAIL_SHARE_PASSWORD_RESET=15');
        $budget = new MailBudgetService();

        $this->assertSame(20, $budget->limitFor(MailBudgetService::SCOPE_CONTACT));
        $this->assertSame(20, $budget->limitFor(MailBudgetService::SCOPE_CREDITS), 'Sin variable se usa el porcentaje por defecto.');
        $this->assertSame(15, $budget->limitFor(MailBudgetService::SCOPE_PASSWORD_RESET));

        putenv('MAIL_DAILY_LIMIT=3000');
        putenv('MAIL_SHARE_CONTACT=150');
        $this->assertSame(3000, $budget->limitFor(MailBudgetService::SCOPE_CONTACT), 'Un porcentaje mayor a 100 se limita al 100 %.');

        putenv('MAIL_DAILY_LIMIT=abc');
        $this->assertSame(MailBudgetService::DEFAULT_DAILY_LIMIT, $budget->dailyLimit());
    }

    public function testUnknownScopeIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new MailBudgetService())->limitFor('otro');
    }

    public function testReserveStopsAtTheScopeLimitWithoutAffectingOtherScopes(): void
    {
        $budget = new MailBudgetService();
        $used = $budget->countInWindow(MailBudgetService::SCOPE_CREDITS);

        // Tope de créditos = lo ya usado + 2; recuperación conserva cupo propio.
        putenv('MAIL_DAILY_LIMIT=' . ($used + 2));
        putenv('MAIL_SHARE_CREDITS=100');
        putenv('MAIL_SHARE_PASSWORD_RESET=100');

        $this->mailLogIds[] = $first = $budget->reserve(MailBudgetService::SCOPE_CREDITS);
        $this->mailLogIds[] = $second = $budget->reserve(MailBudgetService::SCOPE_CREDITS);
        $third = $budget->reserve(MailBudgetService::SCOPE_CREDITS);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertNull($third, 'Al alcanzar el tope del ámbito no se reservan más envíos.');

        if ($budget->countInWindow(MailBudgetService::SCOPE_PASSWORD_RESET) < $used + 2) {
            $this->mailLogIds[] = $other = $budget->reserve(MailBudgetService::SCOPE_PASSWORD_RESET);
            $this->assertNotNull($other, 'El tope de un ámbito no bloquea a los demás.');
        }
    }

    public function testZeroShareNeverSends(): void
    {
        putenv('MAIL_SHARE_CREDITS=0');

        $this->assertNull((new MailBudgetService())->reserve(MailBudgetService::SCOPE_CREDITS));
    }

    public function testMailerDoesNotSendWhenBudgetIsExhausted(): void
    {
        $budget = $this->createMock(MailBudgetService::class);
        $budget->method('reserve')->willReturn(null);
        $budget->expects($this->never())->method('markDelivered');

        $sent = (new MailerService($budget))->send('a@b.cl', 'A', 'Asunto', 'Cuerpo', MailBudgetService::SCOPE_CREDITS);

        $this->assertFalse($sent);
    }

    public function testMailerUsesThePasswordResetScope(): void
    {
        $budget = $this->createMock(MailBudgetService::class);
        $budget->expects($this->once())->method('reserve')
            ->with(MailBudgetService::SCOPE_PASSWORD_RESET)->willReturn(null);

        $this->assertFalse((new MailerService($budget))->sendPasswordReset('a@b.cl', 'A', 'http://x/restablecer-password/t'));
    }

    // ── SEC-09: aviso agrupado de consultas ───────────────────────────────

    private function digest(QueryRepository $repo, MailerService $mailer, ?string $lastDigestAt): ContactDigestService
    {
        putenv('CONTACT_NOTIFY_EMAIL=encargado@sfluls.cl');
        putenv('CONTACT_DIGEST_INTERVAL_MINUTES=60');

        $budget = $this->createMock(MailBudgetService::class);
        $budget->method('lastReservationAt')->willReturn($lastDigestAt);

        return new ContactDigestService($repo, $mailer, $budget, $this->createMock(FooterService::class));
    }

    public function testDigestIsSentWhenDueAndMarksTheIncludedQueries(): void
    {
        $contacts = [['id' => 7, 'name' => 'Ana'], ['id' => 9, 'name' => 'Luis']];
        $repo = $this->createMock(QueryRepository::class);
        $repo->method('countUnnotified')->willReturn(2);
        $repo->method('findUnnotified')->with(ContactDigestService::MAX_ITEMS)->willReturn($contacts);
        $repo->expects($this->once())->method('markNotifiedUpTo')->with(9);

        $mailer = $this->createMock(MailerService::class);
        $mailer->expects($this->once())->method('sendContactDigest')
            ->with('encargado@sfluls.cl', $contacts, 2)->willReturn(true);

        $lastDigest = date('Y-m-d H:i:sP', time() - 61 * 60);
        $this->assertTrue($this->digest($repo, $mailer, $lastDigest)->sendIfDue());
    }

    public function testDigestWaitsForTheInterval(): void
    {
        $repo = $this->createMock(QueryRepository::class);
        $repo->method('countUnnotified')->willReturn(5);
        $repo->expects($this->never())->method('markNotifiedUpTo');

        $mailer = $this->createMock(MailerService::class);
        $mailer->expects($this->never())->method('sendContactDigest');

        $lastDigest = date('Y-m-d H:i:sP', time() - 10 * 60);
        $this->assertFalse($this->digest($repo, $mailer, $lastDigest)->sendIfDue());
    }

    public function testDigestIsNotSentWithoutPendingQueries(): void
    {
        $repo = $this->createMock(QueryRepository::class);
        $repo->method('countUnnotified')->willReturn(0);

        $mailer = $this->createMock(MailerService::class);
        $mailer->expects($this->never())->method('sendContactDigest');

        $this->assertFalse($this->digest($repo, $mailer, null)->sendIfDue());
    }

    public function testFailedDigestKeepsTheQueriesPending(): void
    {
        $repo = $this->createMock(QueryRepository::class);
        $repo->method('countUnnotified')->willReturn(1);
        $repo->method('findUnnotified')->willReturn([['id' => 3]]);
        $repo->expects($this->never())->method('markNotifiedUpTo');

        $mailer = $this->createMock(MailerService::class);
        $mailer->method('sendContactDigest')->willReturn(false);

        $this->assertFalse($this->digest($repo, $mailer, null)->sendIfDue());
    }

    // ── SEC-10: CAPTCHA y rutas alternativas ──────────────────────────────

    private function render(string $view, array $vars): string
    {
        extract($vars);
        ob_start();
        require dirname(__DIR__) . '/app/Views/' . $view . '.php';

        return (string) ob_get_clean();
    }

    public function testContactFormHasCaptchaAndHoneypotAndKeepsInput(): void
    {
        sessionStart();
        $html = $this->render('contacto', [
            'flashSuccess' => null,
            'flashError'   => 'El código de seguridad (CAPTCHA) es incorrecto o ha expirado.',
            'old'          => ['name' => 'Ana <b>', 'email' => 'ana@x.cl', 'subject' => 'Otro', 'message' => 'Hola'],
        ]);

        $this->assertStringContainsString('name="captcha"', $html);
        $this->assertStringContainsString('src="/captcha"', $html);
        $this->assertStringContainsString('name="website"', $html);
        $this->assertStringContainsString('value="Ana &lt;b&gt;"', $html);
        $this->assertMatchesRegularExpression('/<option value="Otro" selected>/', $html);
        $this->assertStringContainsString('>Hola</textarea>', $html);
    }

    public function testCreditsModalSendsCsrfHoneypotAndCaptcha(): void
    {
        sessionStart();
        $html = $this->render('credits', ['miembros' => [], 'flashSuccess' => null, 'flashError' => null]);

        $this->assertStringContainsString('name="website"', $html, 'El honeypot que revisa el servidor debe existir en el formulario.');
        $this->assertStringContainsString('name="captcha"', $html);
        $this->assertStringContainsString("'X-CSRF-Token'", $html);
        $this->assertMatchesRegularExpression('/captcha:\s*captchaInput\.value/', $html);
    }

    public function testPublicPostApisRequireCsrf(): void
    {
        $routes = (string) file_get_contents(dirname(__DIR__) . '/public/index.php');

        foreach (['/api/credits/contact', '/api/queries'] as $path) {
            $this->assertMatchesRegularExpression(
                "#'" . preg_quote($path, '#') . "'\s*=>\s*\[\[[^\]]+'(contact|store)'\],\s*\[[^\]]*'csrf'#",
                $routes,
                "{$path} debe exigir CSRF"
            );
        }
    }

    // ── SEC-11: intentos fallidos al cambiar la contraseña ────────────────

    public function testFailedAttemptsAreCountedPerAccountRegardlessOfIp(): void
    {
        $guard = new FormGuardService();
        $this->formEmails[] = $email = 'sec11_' . bin2hex(random_bytes(5)) . '@test.local';
        $form = \App\Controllers\PasswordController::FAILED_ATTEMPT_FORM;

        $guard->record($form, '10.0.0.1', $email);
        $guard->record($form, '10.0.0.2', $email);
        $guard->record('contacto', '10.0.0.3', $email);

        $this->assertSame(2, $guard->countRecentByEmail($form, $email));
        $this->assertSame(2, $guard->countRecentByEmail($form, strtoupper($email)));
    }

    // ── SEC-13: registro con IP para analizar ataques ─────────────────────

    public function testSuccessfulLoginKeepsHistoryAndStillResetsTheLockout(): void
    {
        $limiter = new \App\Services\RateLimiterService();
        $ip = '10.13.' . random_int(0, 255) . '.' . random_int(1, 254);
        $email = 'sec13_' . bin2hex(random_bytes(5)) . '@test.local';

        for ($i = 0; $i < 5; $i++) {
            $limiter->recordFailedAttempt($ip, $email);
        }
        $this->assertTrue($limiter->isBlocked($ip, $email));

        $limiter->recordSuccess($ip, $email);
        $this->assertFalse($limiter->isBlocked($ip, $email), 'Un login exitoso sigue reiniciando el bloqueo.');
        $this->assertSame(
            6,
            (int) dbFetchOne('SELECT COUNT(*) AS t FROM login_attempt WHERE email = :e', ['e' => $email])['t'],
            'Los intentos (5 fallidos y el exitoso) se conservan para el análisis.'
        );

        // Los fallos posteriores al éxito vuelven a contar.
        $limiter->recordFailedAttempt($ip, $email);
        $this->assertSame(1, $limiter->countFailedAttemptsByEmail($email));

        dbDelete('login_attempt', 'email = :e', ['e' => $email]);
    }

    public function testRejectionsAreRecordedWithIpWithoutCountingForTheLimit(): void
    {
        $guard = new FormGuardService();
        $this->formEmails[] = $email = 'sec13_' . bin2hex(random_bytes(5)) . '@test.local';

        $guard->recordRejection('contacto', FormGuardService::REJECT_CAPTCHA, '10.0.0.9', $email);

        $row = dbFetchOne('SELECT form, reason, ip FROM form_rejection WHERE email = :e', ['e' => $email]);
        $this->assertSame(['form' => 'contacto', 'reason' => 'captcha', 'ip' => '10.0.0.9'], $row);
        $this->assertFalse($guard->isLimited('contacto', '10.0.0.99', $email));

        dbDelete('form_rejection', 'email = :e', ['e' => $email]);
    }

    public function testCaptchaReloadButtonIsAnIconInEveryForm(): void
    {
        foreach (['login', 'contacto', 'credits'] as $view) {
            $html = (string) file_get_contents(dirname(__DIR__) . "/app/Views/{$view}.php");
            $this->assertMatchesRegularExpression(
                '/class="captcha-reload-btn"[^>]*aria-label="Recargar código de seguridad CAPTCHA"[^>]*>\s*<span aria-hidden="true">⟳<\/span>/s',
                $html,
                "Vista {$view}"
            );
        }
    }

    // ── SEC-12: no revelar qué cuentas existen ────────────────────────────

    public function testVerifyPasswordWithoutAccountFailsAfterHashing(): void
    {
        $hash = password_hash('Correcta2026!', PASSWORD_DEFAULT);

        $this->assertTrue(UserService::verifyPassword('Correcta2026!', $hash));
        $this->assertFalse(UserService::verifyPassword('Correcta2026!', null));
        $this->assertFalse(UserService::verifyPassword('Correcta2026!', ''));

        // Sin cuenta debe costar lo mismo que verificar un hash real.
        $start = hrtime(true);
        UserService::verifyPassword('x', $hash);
        $withAccount = hrtime(true) - $start;
        $start = hrtime(true);
        UserService::verifyPassword('x', null);
        $withoutAccount = hrtime(true) - $start;
        $this->assertGreaterThan($withAccount * 0.5, $withoutAccount);
    }

    public function testHashesUseAFixedCostAndOldHashesAreUpgradedOnLogin(): void
    {
        $this->assertSame(12, password_get_info(UserService::hashPassword('x'))['options']['cost']);

        $repo = $this->createMock(\App\Repositories\UserRepository::class);
        $repo->expects($this->once())->method('update')
            ->with(5, $this->callback(static fn(array $f): bool => password_get_info($f['password'])['options']['cost'] === 12
                && password_verify('Clave2026!x', $f['password'])));
        $service = new UserService($repo, $this->createMock(\App\Services\AuditService::class));

        $service->rehashIfNeeded(5, 'Clave2026!x', password_hash('Clave2026!x', PASSWORD_BCRYPT, ['cost' => 10]));
        $service->rehashIfNeeded(5, 'Clave2026!x', UserService::hashPassword('Clave2026!x'));
    }

    public function testResetLinkIsNotSentForUnknownEmail(): void
    {
        $mailer = $this->createMock(MailerService::class);
        $mailer->expects($this->never())->method('sendPasswordReset');

        (new PasswordResetService(null, null, null, $mailer))
            ->sendResetLink('nadie_' . bin2hex(random_bytes(5)) . '@test.local', 'http://localhost');
    }
}
