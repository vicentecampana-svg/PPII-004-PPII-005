<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * BUG-01: la URL pretendida (_intended_url) se conserva solo si es una ruta
 * interna segura, para volver a /cambiar-password tras el login sin open redirect.
 */
final class IntendedUrlTest extends TestCase
{
    #[DataProvider('safeUrls')]
    public function testAcceptsInternalUrls(string $url): void
    {
        $this->assertTrue(isSafeIntendedUrl($url));
    }

    #[DataProvider('unsafeUrls')]
    public function testRejectsUnsafeUrls(?string $url): void
    {
        $this->assertFalse(isSafeIntendedUrl($url));
    }

    public static function safeUrls(): array
    {
        return [
            'cambiar-password'     => ['/cambiar-password'],
            'alias guion bajo'     => ['/cambiar_password'],
            'alias contrasena'     => ['/cambiar-contrasena'],
            'admin con query'      => ['/admin?tab=usuarios'],
            'login-like prefijo'   => ['/loginfo'],
        ];
    }

    public static function unsafeUrls(): array
    {
        return [
            'null'                 => [null],
            'vacia'                => [''],
            'login'                => ['/login'],
            'login con query'      => ['/login?x=1'],
            'logout'               => ['/logout'],
            'externa'              => ['https://evil.example/'],
            'protocol-relative'    => ['//evil.example'],
            'backslash'            => ['/\\evil.example'],
            'salto de linea'       => ["/admin\r\nSet-Cookie: x=1"],
            'relativa'             => ['admin'],
        ];
    }
}
