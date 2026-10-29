<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CreditMemberRepository;

class CreditsService
{
    /**
     * Los integrantes del apartado Créditos (equipo Charlie) ahora viven en la
     * tabla `credit_member`, editables por el Super Admin desde el panel.
     * Las direcciones de correo electrónico NUNCA se exponen en el Frontend / DOM.
     * Aún se pueden sobreescribir por integrante mediante variables de entorno
     * (EMAIL_VICENTE_CAMPANA, etc.) si se configuran en el servidor.
     */
    private CreditMemberRepository $repo;
    private MailerService $mailer;

    public function __construct(?CreditMemberRepository $repo = null, ?MailerService $mailer = null)
    {
        $this->repo   = $repo   ?? new CreditMemberRepository();
        $this->mailer = $mailer ?? new MailerService();
    }

    /**
     * Retorna la lista pública de integrantes para la vista de créditos.
     * Excluye deliberadamente cualquier dirección de correo electrónico.
     */
    public function getPublicMembers(): array
    {
        return array_map(static fn(array $m): array => [
            'key'  => $m['key'],
            'name' => $m['name'],
            'role' => $m['role'],
        ], $this->repo->findAllPublic());
    }

    /**
     * Valida y procesa el envío de un mensaje de contacto a un integrante.
     *
     * @param array $input Datos del formulario (member_key, name, email, message)
     * @return array Resultado de la operación
     * @throws \InvalidArgumentException Si la validación falla
     */
    public function sendContactMessage(array $input): array
    {
        $memberKey = trim((string)($input['member_key'] ?? $input['member_id'] ?? ''));
        $name      = trim((string)($input['name'] ?? ''));
        $email     = trim((string)($input['email'] ?? ''));
        $message   = trim((string)($input['message'] ?? ''));

        $member = $this->repo->findByKey($memberKey);
        if (!$member) {
            throw new \InvalidArgumentException('El integrante seleccionado no es válido.');
        }

        if (mb_strlen($name) < 2 || mb_strlen($name) > 150) {
            throw new \InvalidArgumentException('El nombre debe tener entre 2 y 150 caracteres.');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            throw new \InvalidArgumentException('El correo electrónico ingresado no es válido.');
        }

        if (mb_strlen($message) < 5 || mb_strlen($message) > 5000) {
            throw new \InvalidArgumentException('El mensaje debe tener entre 5 y 5000 caracteres.');
        }

        $recipientEmail = $this->resolveRecipientEmail($member['key'], $member['email']);

        $dispatched = $this->dispatchEmail(
            $recipientEmail,
            $member['name'],
            $member['role'],
            $name,
            $email,
            $message
        );

        $this->persistAuditRecord($member['name'], $name, $email, $message, $dispatched);

        return [
            'success'   => true,
            'message'   => 'Tu mensaje ha sido enviado exitosamente a ' . $member['name'] . '.',
            'recipient' => $member['name'],
        ];
    }

    /**
     * Resuelve el correo del integrante, permitiendo variables de entorno como override.
     */
    private function resolveRecipientEmail(string $key, string $defaultEmail): string
    {
        $envKey = 'EMAIL_' . strtoupper(str_replace('-', '_', $key));
        $envEmail = getenv($envKey);
        if ($envEmail !== false && filter_var($envEmail, FILTER_VALIDATE_EMAIL)) {
            return $envEmail;
        }

        return $defaultEmail;
    }

    /**
     * Despacha el correo mediante MailerService.
     *
     * SEC-09: ya no se recurre a mail() nativo si el envío falla, porque eso
     * se saltaría el tope diario de correos del ámbito.
     */
    private function dispatchEmail(
        string $to,
        string $recipientName,
        string $recipientRole,
        string $senderName,
        string $senderEmail,
        string $message
    ): bool {
        $subject = "[SFL Lab - Créditos] Mensaje para {$recipientName}";

        $textBody = "Has recibido un nuevo mensaje desde la sección de Créditos del sitio web SFL ULS Lab.\n\n"
            . "------------------------------------------------------------\n"
            . "Destinatario: {$recipientName} ({$recipientRole})\n"
            . "Remitente:    {$senderName}\n"
            . "Correo:       {$senderEmail}\n"
            . "Fecha y hora: " . date('Y-m-d H:i:s') . "\n"
            . "------------------------------------------------------------\n\n"
            . "Mensaje:\n"
            . "{$message}\n\n"
            . "------------------------------------------------------------\n"
            . "Software Factory Lab — Universidad de La Serena\n";

        try {
            $htmlBody = nl2br(htmlspecialchars($textBody, ENT_QUOTES, 'UTF-8'));
            return $this->mailer->send($to, $recipientName, $subject, $htmlBody, MailBudgetService::SCOPE_CREDITS);
        } catch (\Throwable $e) {
            error_log('[SFL ULS] No se pudo enviar el mensaje de créditos: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Persiste el mensaje en la base de datos para trazabilidad.
     *
     * SEC-09: si el correo no llegó al integrante (por ejemplo, porque se
     * alcanzó el tope diario), el mensaje queda pendiente y sin avisar, así
     * que se incluye en el próximo aviso agrupado al encargado.
     */
    private function persistAuditRecord(
        string $recipientName,
        string $senderName,
        string $senderEmail,
        string $message,
        bool $dispatched
    ): void {
        try {
            if (function_exists('dbInsert')) {
                dbInsert('contact_request', [
                    'name'        => mb_substr($senderName, 0, 150),
                    'email'       => mb_substr($senderEmail, 0, 150),
                    'phone'       => null,
                    'subject'     => mb_substr("Créditos: {$recipientName}", 0, 255),
                    'message'     => $message,
                    'status'      => $dispatched ? 'enviado' : 'pendiente',
                    'notified_at' => $dispatched ? date('Y-m-d H:i:sP') : null,
                ]);
            }
        } catch (\Throwable) {
            // La persistencia es opcional y no debe interrumpir el envío exitoso.
        }
    }
}
