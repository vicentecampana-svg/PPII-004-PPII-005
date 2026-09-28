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
class MailerService
{
    private string $driver;
    private string $fromEmail;
    private string $fromName;
    private string $logPath;

    public function __construct()
    {
        $this->driver     = getenv('MAIL_DRIVER') ?: 'log';
        $this->fromEmail  = getenv('SMTP_FROM') ?: getenv('RESEND_FROM') ?: 'onboarding@resend.dev';
        $this->fromName   = getenv('SMTP_FROM_NAME') ?: 'Software Factory Lab ULS';
        $this->logPath    = dirname(__DIR__, 2) . '/storage/logs/mail_dev.log';
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

        if ($this->driver === 'log') {
            return $this->logMail($toEmail, $subject, $body, $resetLink);
        }

        if ($this->driver === 'resend') {
            return $this->sendResend($toEmail, $toName, $subject, $body);
        }

        return $this->sendSmtp($toEmail, $toName, $subject, $body);
    }

    // ──────────────────────────────────────────────
    //  API pública — envío genérico
    // ──────────────────────────────────────────────

    /**
     * Envía un correo genérico (contacto, notificaciones, etc.).
     */
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        if ($this->driver === 'log') {
            return $this->logMail($toEmail, $subject, $htmlBody, '');
        }

        if ($this->driver === 'resend') {
            return $this->sendResend($toEmail, $toName, $subject, $htmlBody);
        }

        return $this->sendSmtp($toEmail, $toName, $subject, $htmlBody);
    }

    /**
     * Notifica al encargado sobre una nueva consulta recibida desde el
     * formulario de contacto.
     *
     * @param  string $toEmail Correo del encargado / equipo responsable.
     * @param  array  $contact Datos de la consulta (name, email, phone, subject, message).
     * @return bool            true si se procesó sin errores.
     */
    public function sendContactNotification(string $toEmail, array $contact): bool
    {
        $subject = 'Nueva consulta de contacto — ' . (($contact['subject'] ?? 'Formulario') ?: 'Formulario');
        $body    = $this->buildContactNotificationBody($contact);

        return $this->send($toEmail, 'Encargado SFL ULS', $subject, $body);
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
    //  Resend API
    // ──────────────────────────────────────────────

    /**
     * Envía correo vía la API de Resend (HTTP, sin SMTP).
     */
    private function sendResend(string $toEmail, string $toName, string $subject, string $body): bool
    {
        $apiKey = getenv('RESEND_API_KEY');
        if (!$apiKey) {
            error_log('[MailerService] RESEND_API_KEY no configurada');
            return false;
        }

        $payload = json_encode([
            'from'    => $this->fromName . ' <' . $this->fromEmail . '>',
            'to'      => [$toEmail],
            'subject' => $subject,
            'html'    => $body,
        ]);

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $payload,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        error_log('[MailerService] Resend error ' . $httpCode . ': ' . ($response ?: 'sin respuesta'));
        return false;
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
          <style>
            body { font-family: Arial, sans-serif; background: #f8fafc; padding: 32px; margin: 0; }
            .email-card { max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 32px; border: 1px solid #e2e8f0; }
            .email-title { margin-top: 0; color: #1e293b; }
            .btn-action-wrapper { text-align: center; margin: 28px 0; }
            .btn-action { display: inline-block; background: #0f172a; color: #ffffff !important; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold; }
            .email-note { font-size: 0.85em; color: #64748b; }
            .email-fallback { font-size: 0.8em; color: #94a3b8; }
            .email-fallback-link { color: #3b82f6; word-break: break-all; }
            .email-divider { border: none; border-top: 1px solid #e2e8f0; margin: 24px 0; }
            .email-footer { font-size: 0.75em; color: #94a3b8; margin: 0; }
          </style>
        </head>
        <body>
          <div class="email-card">
            <h2 class="email-title">Recuperación de contraseña</h2>
            <p>Hola, <strong>{$safeName}</strong>.</p>
            <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en
               <strong>Software Factory Lab ULS</strong>. Haz clic en el botón para continuar:</p>
            <p class="btn-action-wrapper">
              <a href="{$safeLink}" class="btn-action">
                Restablecer contraseña
              </a>
            </p>
            <p class="email-note">
              Este enlace es válido por <strong>1 hora</strong> y sólo puede usarse
              una vez. Si no solicitaste el cambio, puedes ignorar este correo.
            </p>
            <p class="email-fallback">
              Si el botón no funciona, copia esta URL en tu navegador:<br>
              <a href="{$safeLink}" class="email-fallback-link">{$safeLink}</a>
            </p>
            <hr class="email-divider">
            <p class="email-footer">
              Software Factory Lab (SFL) — Universidad de La Serena
            </p>
          </div>
        </body>
        </html>
        HTML;
    }

    private function buildContactNotificationBody(array $contact): string
    {
        $name    = htmlspecialchars((string) ($contact['name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $email   = htmlspecialchars((string) ($contact['email'] ?? ''), ENT_QUOTES, 'UTF-8');
        $phone   = htmlspecialchars((string) ($contact['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
        $subject = htmlspecialchars((string) ($contact['subject'] ?? 'Formulario'), ENT_QUOTES, 'UTF-8');
        $message = nl2br(htmlspecialchars((string) ($contact['message'] ?? ''), ENT_QUOTES, 'UTF-8'));

        $phoneRow = $phone !== ''
            ? '<tr><td class="email-label">Teléfono</td><td class="email-value">' . $phone . '</td></tr>'
            : '';

        return <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
          <meta charset="UTF-8">
          <title>Nueva consulta de contacto</title>
          <style>
            body { font-family: Arial, sans-serif; background: #f8fafc; padding: 32px; margin: 0; }
            .email-card { max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 32px; border: 1px solid #e2e8f0; }
            .email-title { margin-top: 0; color: #1e293b; }
            .email-intro { color: #475569; }
            .email-table { width: 100%; border-collapse: collapse; font-size: 0.95em; }
            .email-label { padding: 8px 0; color: #475569; width: 140px; }
            .email-value { padding: 8px 0; color: #0f172a; font-weight: bold; }
            .email-message { margin-top: 16px; background: #f1f5f9; border-radius: 6px; padding: 16px; }
            .email-message-label { margin: 0 0 6px 0; color: #475569; }
            .email-message-text { margin: 0; color: #0f172a; line-height: 1.5; }
            .email-divider { border: none; border-top: 1px solid #e2e8f0; margin: 24px 0; }
            .email-footer { font-size: 0.75em; color: #94a3b8; margin: 0; }
          </style>
        </head>
        <body>
          <div class="email-card">
            <h2 class="email-title">Nueva consulta de contacto</h2>
            <p class="email-intro">Se recibió una nueva consulta a través del formulario de contacto de
               <strong>TechHub ULS</strong>.</p>
            <table class="email-table">
              <tr>
                <td class="email-label">Nombre</td>
                <td class="email-value">{$name}</td>
              </tr>
              <tr>
                <td class="email-label">Correo</td>
                <td class="email-value">{$email}</td>
              </tr>
              {$phoneRow}
              <tr>
                <td class="email-label">Asunto</td>
                <td class="email-value">{$subject}</td>
              </tr>
            </table>
            <div class="email-message">
              <p class="email-message-label"><strong>Mensaje:</strong></p>
              <p class="email-message-text">{$message}</p>
            </div>
            <hr class="email-divider">
            <p class="email-footer">
              TechHub — Software Factory Lab, Universidad de La Serena
            </p>
          </div>
        </body>
        </html>
        HTML;
    }
}
