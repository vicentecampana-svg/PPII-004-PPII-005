<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Servicio de envío de correo con soporte para modo desarrollo y producción.
 *
 * En APP_ENV=development (o con MAIL_DRIVER=log) los correos se escriben
 * en storage/logs/mail_dev.log para visualizarlos sin servidor SMTP.
 *
 * Para activar el envío real en producción, configurar en .env:
 *   MAIL_DRIVER=smtp              # Activa el envío vía socket SMTP nativo
 *   SMTP_HOST=smtp.uls.cl         # Servidor SMTP (ej. universitario o proveedor)
 *   SMTP_PORT=587                 # 587 (STARTTLS) o 465 (SSL)
 *   SMTP_USER=no-reply@uls.cl     # Usuario para AUTH LOGIN
 *   SMTP_PASS=<contraseña>        # Contraseña SMTP
 *   SMTP_FROM=no-reply@sfl.uls.cl # Dirección remitente visible
 *   SMTP_FROM_NAME=SFL ULS        # Nombre del remitente
 *
 * El envío SMTP implementa el protocolo RFC 5321 mediante sockets nativos
 * con soporte para autenticación (AUTH LOGIN) y cifrado TLS/SSL
 * sin dependencias externas.
 */
class MailerService
{
    private string $driver;
    private string $fromEmail;
    private string $fromName;
    private string $logPath;

    public function __construct(
        ?string $driver = null,
        ?string $fromEmail = null,
        ?string $fromName = null
    ) {
        $this->driver    = $driver ?? (getenv('MAIL_DRIVER') ?: 'log');
        $this->fromEmail = $fromEmail ?? (getenv('SMTP_FROM') ?: getenv('RESEND_FROM') ?: 'no-reply@sfl.uls.cl');
        $this->fromName  = $fromName ?? (getenv('SMTP_FROM_NAME') ?: 'SFL ULS');
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
        $subject = 'Recuperación de contraseña — SFL ULS';
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
    //  Modo producción: envío real vía SMTP autenticado por socket (BUG-02)
    // ──────────────────────────────────────────────

    /**
     * Envía correo mediante conexión directa por socket SMTP (RFC 5321).
     * Soporta STARTTLS (puerto 587), SSL directo (puerto 465) y autenticación AUTH LOGIN.
     *
     * @param  string $toEmail Destinatario.
     * @param  string $toName  Nombre visible del destinatario.
     * @param  string $subject Asunto.
     * @param  string $body    Cuerpo en HTML.
     * @return bool            true si el servidor aceptó el correo (código 250).
     */
    protected function sendSmtp(string $toEmail, string $toName, string $subject, string $body): bool
    {
        $host     = (string) (getenv('SMTP_HOST') ?: '');
        $port     = (int) (getenv('SMTP_PORT') ?: 587);
        $user     = (string) (getenv('SMTP_USER') ?: '');
        $pass     = (string) (getenv('SMTP_PASS') ?: '');
        $timeout  = (int) (getenv('SMTP_TIMEOUT') ?: 15);
        $security = strtolower((string) (getenv('SMTP_ENCRYPTION') ?: ''));

        if ($host === '') {
            error_log('[MailerService] SMTP_HOST no configurado.');
            return false;
        }

        $useDirectSsl = ($port === 465 || $security === 'ssl');
        $useStartTls  = (!$useDirectSsl && ($port === 587 || $security === 'tls' || $security === 'starttls'));
        $remote       = ($useDirectSsl ? 'ssl://' : 'tcp://') . $host . ':' . $port;

        $errno  = 0;
        $errstr = '';
        $socket = $this->openSmtpSocket($remote, $timeout, $errno, $errstr);

        if (!$socket) {
            error_log(sprintf('[MailerService] Error al conectar a %s:%d — (%d) %s', $host, $port, $errno, $errstr));
            return false;
        }

        stream_set_timeout($socket, $timeout);

        try {
            // 1. Saludo inicial del servidor (220)
            $response = $this->readSmtpResponse($socket);
            if ($this->getResponseCode($response) !== 220) {
                error_log('[MailerService] Banner SMTP inesperado: ' . trim($response));
                return false;
            }

            $clientHost = gethostname() ?: 'localhost';

            // 2. EHLO / HELO inicial
            $response = $this->sendSmtpCommand($socket, 'EHLO ' . $clientHost);
            if ($this->getResponseCode($response) !== 250) {
                $response = $this->sendSmtpCommand($socket, 'HELO ' . $clientHost);
                if ($this->getResponseCode($response) !== 250) {
                    error_log('[MailerService] Falló HELO/EHLO: ' . trim($response));
                    return false;
                }
            }

            // 3. STARTTLS si corresponde (puerto 587 o tls configurado)
            if ($useStartTls) {
                $response = $this->sendSmtpCommand($socket, 'STARTTLS');
                if ($this->getResponseCode($response) !== 220) {
                    error_log('[MailerService] El servidor rechazó STARTTLS: ' . trim($response));
                    return false;
                }

                $cryptoOk = $this->enableCrypto($socket, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if (!$cryptoOk) {
                    error_log('[MailerService] Error al negociar TLS con el servidor SMTP.');
                    return false;
                }

                // RFC 3207: tras STARTTLS se debe repetir EHLO
                $response = $this->sendSmtpCommand($socket, 'EHLO ' . $clientHost);
                if ($this->getResponseCode($response) !== 250) {
                    error_log('[MailerService] Falló EHLO post-STARTTLS: ' . trim($response));
                    return false;
                }
            }

            // 4. Autenticación AUTH LOGIN si hay credenciales
            if ($user !== '') {
                $response = $this->sendSmtpCommand($socket, 'AUTH LOGIN');
                if ($this->getResponseCode($response) !== 334) {
                    error_log('[MailerService] Falló inicio de AUTH LOGIN: ' . trim($response));
                    return false;
                }

                $response = $this->sendSmtpCommand($socket, base64_encode($user));
                if ($this->getResponseCode($response) !== 334) {
                    error_log('[MailerService] Usuario SMTP rechazado: ' . trim($response));
                    return false;
                }

                $response = $this->sendSmtpCommand($socket, base64_encode($pass));
                if ($this->getResponseCode($response) !== 235) {
                    error_log('[MailerService] Contraseña SMTP rechazada: ' . trim($response));
                    return false;
                }
            }

            // 5. Envelope: MAIL FROM y RCPT TO
            $response = $this->sendSmtpCommand($socket, 'MAIL FROM:<' . $this->fromEmail . '>');
            if ($this->getResponseCode($response) !== 250) {
                error_log('[MailerService] MAIL FROM rechazado: ' . trim($response));
                return false;
            }

            $response = $this->sendSmtpCommand($socket, 'RCPT TO:<' . $toEmail . '>');
            $rcptCode = $this->getResponseCode($response);
            if ($rcptCode !== 250 && $rcptCode !== 251) {
                error_log('[MailerService] RCPT TO rechazado: ' . trim($response));
                return false;
            }

            // 6. DATA
            $response = $this->sendSmtpCommand($socket, 'DATA');
            if ($this->getResponseCode($response) !== 354) {
                error_log('[MailerService] DATA rechazado: ' . trim($response));
                return false;
            }

            // 7. Enviar cabeceras MIME y cuerpo (RFC 5322)
            $fromEncoded = sprintf('=?UTF-8?B?%s?= <%s>', base64_encode($this->fromName), $this->fromEmail);
            $toEncoded   = sprintf('=?UTF-8?B?%s?= <%s>', base64_encode($toName), $toEmail);
            $subjectB64  = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $messageId   = sprintf('<%s@%s>', bin2hex(random_bytes(16)), $host ?: 'sfl.uls.cl');

            $headers = implode("\r\n", [
                'Date: ' . date(DATE_RFC2822),
                'From: ' . $fromEncoded,
                'To: ' . $toEncoded,
                'Subject: ' . $subjectB64,
                'Reply-To: ' . $this->fromEmail,
                'Message-ID: ' . $messageId,
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
                'X-Mailer: SFL-ULS-Mailer/PHP-' . PHP_VERSION,
            ]);

            $payload = $headers . "\r\n\r\n" . rtrim(chunk_split(base64_encode($body), 76, "\r\n"), "\r\n") . "\r\n.";
            $response = $this->sendSmtpCommand($socket, $payload);
            if ($this->getResponseCode($response) !== 250) {
                error_log('[MailerService] Envío de mensaje rechazado: ' . trim($response));
                return false;
            }

            // 8. Cierre de sesión SMTP
            $this->sendSmtpCommand($socket, 'QUIT');
            return true;
        } finally {
            if (is_resource($socket)) {
                fclose($socket);
            }
        }
    }

    /**
     * Abre la conexión socket hacia el servidor SMTP.
     *
     * @param  string $remote
     * @param  int    $timeout
     * @param  int    &$errno
     * @param  string &$errstr
     * @return resource|false
     */
    protected function openSmtpSocket(string $remote, int $timeout, int &$errno, string &$errstr)
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
            ],
        ]);

        return @stream_socket_client(
            $remote,
            $errno,
            $errstr,
            (float) $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );
    }

    /**
     * Habilita cifrado TLS sobre el socket existente.
     *
     * @param  resource $socket
     * @param  int      $cryptoMethod
     * @return bool
     */
    protected function enableCrypto($socket, int $cryptoMethod): bool
    {
        return (bool) @stream_socket_enable_crypto($socket, true, $cryptoMethod);
    }

    /**
     * Envía un comando por el socket y retorna la respuesta del servidor.
     *
     * @param  resource $socket
     * @param  string   $command
     * @return string
     */
    protected function sendSmtpCommand($socket, string $command): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->readSmtpResponse($socket);
    }

    /**
     * Lee la respuesta completa (incluyendo líneas multilínea) del servidor SMTP.
     *
     * @param  resource $socket
     * @return string
     */
    protected function readSmtpResponse($socket): string
    {
        $response = '';
        while (!feof($socket)) {
            $line = fgets($socket, 512);
            if ($line === false) {
                break;
            }
            $response .= $line;
            // RFC 5321: El código de respuesta de 3 dígitos seguido de un espacio marca la última línea.
            if (preg_match('/^\d{3}(?:\s|$)/', $line)) {
                break;
            }
        }
        return $response;
    }

    /**
     * Extrae el código numérico de una respuesta SMTP (ej: 250 de "250 OK\r\n").
     *
     * @param  string $response
     * @return int
     */
    protected function getResponseCode(string $response): int
    {
        return (int) substr(trim($response), 0, 3);
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
               <strong>SFL ULS</strong>.</p>
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
              SFL ULS — Universidad de La Serena
            </p>
          </div>
        </body>
        </html>
        HTML;
    }
}
