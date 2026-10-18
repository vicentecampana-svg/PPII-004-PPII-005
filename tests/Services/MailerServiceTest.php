<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\MailerService;
use PHPUnit\Framework\TestCase;

/**
 * Tests para MailerService y verificación de envío SMTP nativo (BUG-02 / CAL-08).
 */
final class MailerServiceTest extends TestCase
{
    private string $originalDriver;
    private ?string $originalHost;
    private ?string $originalPort;
    private ?string $originalUser;
    private ?string $originalPass;

    protected function setUp(): void
    {
        $this->originalDriver = getenv('MAIL_DRIVER') ?: 'log';
        $this->originalHost   = getenv('SMTP_HOST') ?: null;
        $this->originalPort   = getenv('SMTP_PORT') ?: null;
        $this->originalUser   = getenv('SMTP_USER') ?: null;
        $this->originalPass   = getenv('SMTP_PASS') ?: null;
    }

    protected function tearDown(): void
    {
        putenv('MAIL_DRIVER=' . $this->originalDriver);
        putenv($this->originalHost !== null ? 'SMTP_HOST=' . $this->originalHost : 'SMTP_HOST');
        putenv($this->originalPort !== null ? 'SMTP_PORT=' . $this->originalPort : 'SMTP_PORT');
        putenv($this->originalUser !== null ? 'SMTP_USER=' . $this->originalUser : 'SMTP_USER');
        putenv($this->originalPass !== null ? 'SMTP_PASS=' . $this->originalPass : 'SMTP_PASS');
        putenv('SMTP_ENCRYPTION');
    }

    public function testLogDriverWritesToFile(): void
    {
        putenv('MAIL_DRIVER=log');
        $logPath = dirname(__DIR__, 2) . '/storage/logs/mail_dev.log';
        if (file_exists($logPath)) {
            unlink($logPath);
        }

        $mailer = new MailerService('log');
        $success = $mailer->sendPasswordReset('usuario@test.cl', 'Usuario Test', 'https://sfl.uls.cl/reset/token123');

        $this->assertTrue($success);
        $this->assertFileExists($logPath);
        $content = (string) file_get_contents($logPath);
        $this->assertStringContainsString('usuario@test.cl', $content);
        $this->assertStringContainsString('https://sfl.uls.cl/reset/token123', $content);
    }

    public function testSendSmtpReturnsFalseWhenHostNotConfigured(): void
    {
        putenv('MAIL_DRIVER=smtp');
        putenv('SMTP_HOST=');

        $mailer = new MailerService('smtp');
        $result = $mailer->send('dest@test.cl', 'Destino', 'Asunto Prueba', '<p>Hola</p>');

        $this->assertFalse($result);
    }

    public function testSendSmtpReturnsFalseWhenConnectionFails(): void
    {
        putenv('MAIL_DRIVER=smtp');
        putenv('SMTP_HOST=127.0.0.1');
        putenv('SMTP_PORT=65534'); // Puerto no utilizado
        putenv('SMTP_TIMEOUT=1');

        $mailer = new MailerService('smtp');
        $result = $mailer->send('dest@test.cl', 'Destino', 'Asunto', '<p>Hola</p>');

        $this->assertFalse($result);
    }

    public function testSendSmtpFullConversationWithAuthLoginAndStartTls(): void
    {
        putenv('MAIL_DRIVER=smtp');
        putenv('SMTP_HOST=smtp.uls.cl');
        putenv('SMTP_PORT=587');
        putenv('SMTP_USER=notificaciones@uls.cl');
        putenv('SMTP_PASS=SuperClave2026!');

        $mockMailer = new class('smtp', 'no-reply@sfl.uls.cl', 'SFL ULS') extends MailerService {
            /** @var string[] */
            public array $sentCommands = [];
            public bool $tlsEnabled = false;

            protected function openSmtpSocket(string $remote, int $timeout, int &$errno, string &$errstr)
            {
                // Simular socket retornando un stream en memoria válido
                return fopen('php://memory', 'r+');
            }

            protected function enableCrypto($socket, int $cryptoMethod): bool
            {
                $this->tlsEnabled = true;
                return true;
            }

            protected function readSmtpResponse($socket): string
            {
                return "220 smtp.uls.cl ESMTP Service Ready\r\n";
            }

            protected function sendSmtpCommand($socket, string $command): string
            {
                $this->sentCommands[] = $command;

                if (str_starts_with($command, 'EHLO') || str_starts_with($command, 'HELO')) {
                    return "250-smtp.uls.cl Hello\r\n250-STARTTLS\r\n250-AUTH LOGIN\r\n250 OK\r\n";
                }
                if ($command === 'STARTTLS') {
                    return "220 2.0.0 Ready to start TLS\r\n";
                }
                if ($command === 'AUTH LOGIN') {
                    return "334 VXNlcm5hbWU6\r\n"; // "Username:" base64
                }
                if ($command === base64_encode('notificaciones@uls.cl')) {
                    return "334 UGFzc3dvcmQ6\r\n"; // "Password:" base64
                }
                if ($command === base64_encode('SuperClave2026!')) {
                    return "235 2.7.0 Authentication successful\r\n";
                }
                if (str_starts_with($command, 'MAIL FROM:')) {
                    return "250 2.1.0 Sender OK\r\n";
                }
                if (str_starts_with($command, 'RCPT TO:')) {
                    return "250 2.1.5 Recipient OK\r\n";
                }
                if ($command === 'DATA') {
                    return "354 Start mail input; end with <CRLF>.<CRLF>\r\n";
                }
                if (str_ends_with($command, "\r\n.")) {
                    return "250 2.0.0 OK: queued 12345\r\n";
                }
                if ($command === 'QUIT') {
                    return "221 2.0.0 Bye\r\n";
                }

                return "500 Unrecognized command\r\n";
            }
        };

        $result = $mockMailer->send('profesor@uls.cl', 'Profesor ULS', 'Asunto Importante', '<p>Contenido</p>');

        $this->assertTrue($result, 'El flujo completo SMTP autenticado debe retornar true.');
        $this->assertTrue($mockMailer->tlsEnabled, 'Debe activar TLS en puerto 587.');

        // Verificar comandos enviados en orden
        $cmds = $mockMailer->sentCommands;
        $this->assertNotEmpty($cmds);
        $this->assertStringStartsWith('EHLO', $cmds[0]);
        $this->assertSame('STARTTLS', $cmds[1]);
        $this->assertStringStartsWith('EHLO', $cmds[2], 'RFC 3207: debe repetir EHLO post-STARTTLS');
        $this->assertSame('AUTH LOGIN', $cmds[3]);
        $this->assertSame(base64_encode('notificaciones@uls.cl'), $cmds[4]);
        $this->assertSame(base64_encode('SuperClave2026!'), $cmds[5]);
        $this->assertSame('MAIL FROM:<no-reply@sfl.uls.cl>', $cmds[6]);
        $this->assertSame('RCPT TO:<profesor@uls.cl>', $cmds[7]);
        $this->assertSame('DATA', $cmds[8]);
        $this->assertStringContainsString('Subject: =?UTF-8?B?', $cmds[9]);
        $this->assertStringContainsString('Content-Transfer-Encoding: base64', $cmds[9]);
        $this->assertStringEndsWith("\r\n.", $cmds[9]);
        $this->assertSame('QUIT', $cmds[10]);
    }

    public function testSendSmtpFailsWhenAuthPasswordRejected(): void
    {
        putenv('MAIL_DRIVER=smtp');
        putenv('SMTP_HOST=smtp.uls.cl');
        putenv('SMTP_PORT=25');
        putenv('SMTP_USER=usuario');
        putenv('SMTP_PASS=clave_incorrecta');

        $mockMailer = new class('smtp', 'no-reply@sfl.uls.cl', 'SFL ULS') extends MailerService {
            protected function openSmtpSocket(string $remote, int $timeout, int &$errno, string &$errstr)
            {
                return fopen('php://memory', 'r+');
            }

            protected function readSmtpResponse($socket): string
            {
                return "220 smtp.uls.cl Service Ready\r\n";
            }

            protected function sendSmtpCommand($socket, string $command): string
            {
                if (str_starts_with($command, 'EHLO')) {
                    return "250-smtp.uls.cl\r\n250 AUTH LOGIN\r\n";
                }
                if ($command === 'AUTH LOGIN') {
                    return "334 VXNlcm5hbWU6\r\n";
                }
                if ($command === base64_encode('usuario')) {
                    return "334 UGFzc3dvcmQ6\r\n";
                }
                if ($command === base64_encode('clave_incorrecta')) {
                    return "535 5.7.8 Error: authentication failed\r\n";
                }
                return "250 OK\r\n";
            }
        };

        $result = $mockMailer->send('destino@uls.cl', 'Destino', 'Asunto', '<p>Test</p>');
        $this->assertFalse($result, 'Si la contraseña SMTP es rechazada, debe retornar false.');
    }
}
