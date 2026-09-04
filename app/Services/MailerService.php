<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Servicio de envío de correo con soporte para modo desarrollo.
 *
 * ┌──────────────────────────────────────────────────────────────────────┐
 * │  ⚠  SMTP NO CONFIGURADO — pendiente confirmar servidor universitario │
 * │                                                                      │
 * │  En APP_ENV=development (o sin SMTP_HOST definido) los correos NO    │
 * │  se envían: se escriben en storage/logs/mail_dev.log para poder      │
 * │  ver el contenido y el enlace de recuperación sin necesitar SMTP.    │
 * │                                                                      │
 * │  Para activar el envío real en producción, añade al .env:            │
 * │                                                                      │
 * │    SMTP_HOST=smtp.uls.cl          # host del servidor universitario  │
 * │    SMTP_PORT=587                  # 587 (STARTTLS) o 465 (SSL)       │
 * │    SMTP_USER=no-reply@uls.cl      # cuenta de envío                  │
 * │    SMTP_PASS=<contraseña>         # contraseña SMTP                  │
 * │    SMTP_FROM=no-reply@uls.cl      # dirección From del correo        │
 * │    SMTP_FROM_NAME=TechHub ULS     # nombre visible del remitente     │
 * │    MAIL_DRIVER=smtp               # activar driver real              │
 * │                                                                      │
 * │  El proyecto usa la extensión nativa `mail()` (o SMTP vía stream)    │
 * │  para no añadir dependencias. Si se prefiere PHPMailer/Symfony Mailer│
 * │  instálalo con Composer y reemplaza el método sendSmtp().            │
 * └──────────────────────────────────────────────────────────────────────┘
 */
final class MailerService
{
    private bool $isDevMode;
    private string $fromEmail;
    private string $fromName;
    private string $logPath;

    public function __construct()
    {
        $driver         = getenv('MAIL_DRIVER') ?: 'log';
        $this->isDevMode = ($driver !== 'smtp');
        $this->fromEmail = getenv('SMTP_FROM')      ?: 'no-reply@techhub.uls.cl';
        $this->fromName  = getenv('SMTP_FROM_NAME') ?: 'TechHub ULS';
        $this->logPath   = dirname(__DIR__, 2) . '/storage/logs/mail_dev.log';
    }

    /**
     * Envía (o loguea) el correo de recuperación de contraseña.
     *
     * @param  string $toEmail    Destinatario.
     * @param  string $toName     Nombre visible del destinatario.
     * @param  string $resetLink  URL completa con el token de recuperación.
     * @return bool               true si se procesó sin errores.
     */
    public function sendPasswordReset(string $toEmail, string $toName, string $resetLink): bool
    {
        $subject = 'Recuperación de contraseña — TechHub ULS';
        $body    = $this->buildPasswordResetBody($toName, $resetLink);

        if ($this->isDevMode) {
            return $this->logMail($toEmail, $subject, $body, $resetLink);
        }

        return $this->sendSmtp($toEmail, $toName, $subject, $body);
    }

    // ──────────────────────────────────────────────
    //  Modo desarrollo: escribe en storage/logs/
    // ──────────────────────────────────────────────

    private function logMail(string $to, string $subject, string $body, string $link): bool
    {
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $entry = implode("\n", [
            str_repeat('─', 70),
            '[' . date('Y-m-d H:i:s') . '] MAIL (dev – no enviado)',
            'To:      ' . $to,
            'Subject: ' . $subject,
            'Link:    ' . $link,
            'Body:',
            $body,
            '',
        ]);

        return (bool) file_put_contents($this->logPath, $entry, FILE_APPEND | LOCK_EX);
    }

    // ──────────────────────────────────────────────
    //  Modo producción: envío real vía SMTP nativo
    // ──────────────────────────────────────────────

    /**
     * Envía el correo usando la función mail() con cabeceras SMTP.
     *
     * Si el equipo decide adoptar PHPMailer o Symfony Mailer, reemplazar
     * este método; el resto del servicio no cambia.
     */
    private function sendSmtp(string $toEmail, string $toName, string $subject, string $body): bool
    {
        $fromHeader = sprintf('=?UTF-8?B?%s?= <%s>', base64_encode($this->fromName), $this->fromEmail);
        $toHeader   = sprintf('=?UTF-8?B?%s?= <%s>', base64_encode($toName), $toEmail);

        $headers = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            'From: '    . $fromHeader,
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . PHP_VERSION,
        ]);

        // ini_set para SMTP (sólo en entornos Windows o con ini configurado)
        $smtpHost = getenv('SMTP_HOST');
        $smtpPort = (int) (getenv('SMTP_PORT') ?: 587);
        if ($smtpHost) {
            ini_set('SMTP', $smtpHost);
            ini_set('smtp_port', (string) $smtpPort);
        }

        return mail(
            $toEmail,
            '=?UTF-8?B?' . base64_encode($subject) . '?=',
            base64_encode($body),
            $headers
        );
    }

    // ──────────────────────────────────────────────
    //  Plantilla HTML del correo
    // ──────────────────────────────────────────────

    private function buildPasswordResetBody(string $name, string $link): string
    {
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
          <meta charset="UTF-8">
          <title>Recuperación de contraseña</title>
        </head>
        <body style="font-family: Arial, sans-serif; background: #f8fafc; padding: 32px;">
          <div style="max-width: 480px; margin: 0 auto; background: #fff;
                      border-radius: 8px; padding: 32px; border: 1px solid #e2e8f0;">
            <h2 style="margin-top: 0; color: #1e293b;">Recuperación de contraseña</h2>
            <p>Hola, <strong>{$safeName}</strong>.</p>
            <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en
               <strong>TechHub ULS</strong>. Haz clic en el botón para continuar:</p>
            <p style="text-align: center; margin: 28px 0;">
              <a href="{$safeLink}"
                 style="display: inline-block; background: #0f172a; color: #fff;
                        padding: 12px 24px; border-radius: 6px; text-decoration: none;
                        font-weight: bold;">
                Restablecer contraseña
              </a>
            </p>
            <p style="font-size: 0.85em; color: #64748b;">
              Este enlace es válido por <strong>1 hora</strong> y sólo puede usarse
              una vez. Si no solicitaste el cambio, puedes ignorar este correo.
            </p>
            <p style="font-size: 0.8em; color: #94a3b8;">
              Si el botón no funciona, copia esta URL en tu navegador:<br>
              <a href="{$safeLink}" style="color: #3b82f6;">{$safeLink}</a>
            </p>
            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;">
            <p style="font-size: 0.75em; color: #94a3b8; margin: 0;">
              TechHub — Software Factory Lab, Universidad de La Serena
            </p>
          </div>
        </body>
        </html>
        HTML;
    }
}
