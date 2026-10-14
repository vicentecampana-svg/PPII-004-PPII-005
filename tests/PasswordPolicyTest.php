<?php

declare(strict_types=1);

namespace Tests;

use App\Services\PasswordPolicy;
use PHPUnit\Framework\TestCase;

/**
 * RF-AUT-003 / RF-AUT-004 / RNF-SEC-004: política de contraseñas
 * (12 caracteres mínimo + mayúscula + minúscula + número + especial).
 */
class PasswordPolicyTest extends TestCase
{
    public function testRejectsTooShortPassword(): void
    {
        $errors = PasswordPolicy::errors('Ab1!xyz');

        $this->assertContains('La contraseña debe tener al menos 12 caracteres.', $errors);
        $this->assertFalse(PasswordPolicy::isValid('Ab1!xyz'));
    }

    public function testRejectsPasswordWithoutUppercase(): void
    {
        $errors = PasswordPolicy::errors('abcdefghijkl1!'); // 14 chars, sin mayúscula

        $this->assertSame(['La contraseña debe contener al menos una letra mayúscula.'], $errors);
    }

    public function testRejectsPasswordWithoutLowercase(): void
    {
        $errors = PasswordPolicy::errors('ABCDEFGHIJKL1!'); // 14 chars, sin minúscula

        $this->assertSame(['La contraseña debe contener al menos una letra minúscula.'], $errors);
    }

    public function testRejectsPasswordWithoutDigit(): void
    {
        $errors = PasswordPolicy::errors('Abcdefghijkl!'); // 14 chars, sin número

        $this->assertSame(['La contraseña debe contener al menos un número.'], $errors);
    }

    public function testRejectsPasswordWithoutSpecialCharacter(): void
    {
        $errors = PasswordPolicy::errors('Abcdefghijkl1'); // 14 chars, sin especial

        $this->assertSame(['La contraseña debe contener al menos un carácter especial.'], $errors);
    }

    public function testRejectsPasswordLongerThanMaxLength(): void
    {
        $password = str_repeat('aA1!', 31); // 124 chars

        $errors = PasswordPolicy::errors($password);

        $this->assertContains('La contraseña no puede superar 120 caracteres.', $errors);
    }

    public function testAcceptsCompliantPassword(): void
    {
        $this->assertSame([], PasswordPolicy::errors('ValidPassword123!'));
        $this->assertTrue(PasswordPolicy::isValid('ValidPassword123!'));
    }

    public function testHintMentionsAllRequirements(): void
    {
        $hint = PasswordPolicy::hint();

        $this->assertStringContainsString('12', $hint);
        $this->assertStringContainsString('mayúscula', $hint);
        $this->assertStringContainsString('minúscula', $hint);
        $this->assertStringContainsString('número', $hint);
        $this->assertStringContainsString('especial', $hint);
    }
}
