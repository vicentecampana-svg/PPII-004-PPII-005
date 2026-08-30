<?php

declare(strict_types=1);

namespace Tests;

use App\Services\CaptchaService;
use App\Services\RateLimiterService;
use PHPUnit\Framework\TestCase;

class CaptchaAndRateLimitTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER = [];
        $_SESSION = [];
    }

    public function testCaptchaGeneration(): void
    {
        $service = new CaptchaService();
        $captcha = $service->generate();

        $this->assertNotEmpty($captcha['code']);
        $this->assertSame(5, strlen($captcha['code']));
        $this->assertStringStartsWith('<svg', trim($captcha['svg']));
        $this->assertSame($captcha['code'], $_SESSION['_captcha_code']);
    }

    public function testCaptchaValidationSuccess(): void
    {
        $service = new CaptchaService();
        $_SESSION['_captcha_code'] = 'A2B3C';
        $_SESSION['_captcha_expires_at'] = time() + 300;

        $this->assertTrue($service->validate('a2b3c')); // case-insensitive
    }

    public function testCaptchaValidationFailureWithWrongCode(): void
    {
        $service = new CaptchaService();
        $_SESSION['_captcha_code'] = 'A2B3C';
        $_SESSION['_captcha_expires_at'] = time() + 300;

        $this->assertFalse($service->validate('WRONG'));
    }

    public function testCaptchaCannotBeReused(): void
    {
        $service = new CaptchaService();
        $_SESSION['_captcha_code'] = 'A2B3C';
        $_SESSION['_captcha_expires_at'] = time() + 300;

        $this->assertTrue($service->validate('A2B3C'));
        $this->assertFalse($service->validate('A2B3C'), 'Second attempt with same captcha code must fail');
    }

    public function testCaptchaFailsWhenExpired(): void
    {
        $service = new CaptchaService();
        $_SESSION['_captcha_code'] = 'A2B3C';
        $_SESSION['_captcha_expires_at'] = time() - 10; // expired

        $this->assertFalse($service->validate('A2B3C'));
    }

    public function testRateLimiterMaxAttemptsThreshold(): void
    {
        $limiter = new RateLimiterService();
        $testIp = '192.168.100.50';
        $testEmail = 'ratelimittest@example.com';

        // Clear any previous attempts
        $limiter->recordSuccess($testIp, $testEmail);
        $this->assertFalse($limiter->isBlocked($testIp, $testEmail));

        // Record 4 failed attempts -> still not blocked
        for ($i = 0; $i < 4; $i++) {
            $limiter->recordFailedAttempt($testIp, $testEmail);
        }
        $this->assertFalse($limiter->isBlocked($testIp, $testEmail));

        // 5th failed attempt -> now blocked!
        $limiter->recordFailedAttempt($testIp, $testEmail);
        $this->assertTrue($limiter->isBlocked($testIp, $testEmail));

        // Successful login resets the lockout
        $limiter->recordSuccess($testIp, $testEmail);
        $this->assertFalse($limiter->isBlocked($testIp, $testEmail));
    }
}
