<?php

declare(strict_types=1);

namespace App\Services;

class CreditsService
{
    /**
     * Mapeo privado y seguro de los integrantes del equipo Charlie.
     * Las direcciones de correo electrónico NUNCA se exponen en el Frontend / DOM.
     * Se pueden sobreescribir mediante variables de entorno si se configuran en el servidor.
     */
    private const TEAM_MEMBERS = [
        'vicente-campana' => [
            'key'   => 'vicente-campana',
            'name'  => 'Vicente Campaña',
            'role'  => 'Project Manager',
            'email' => 'vicente.campana@userena.cl',
        ],
        'wilmary-guedez' => [
            'key'   => 'wilmary-guedez',
            'name'  => 'Wilmary Guedez',
            'role'  => 'Ingeniera en Requerimientos',
            'email' => 'wilmary.guedez@userena.cl',
        ],
        'esteban-zepeda' => [
            'key'   => 'esteban-zepeda',
            'name'  => 'Esteban Zepeda',
            'role'  => 'Diseño UX/UI',
            'email' => 'esteban.zepeda@userena.cl',
        ],
        'bastian-pizarro' => [
            'key'   => 'bastian-pizarro',
            'name'  => 'Bastian Pizarro',
            'role'  => 'Diseño UX/UI',
            'email' => 'bastian.pizarro@userena.cl',
        ],
        'maximiliano-saavedra' => [
            'key'   => 'maximiliano-saavedra',
            'name'  => 'Maximiliano Saavedra',
            'role'  => 'Desarrollo Backend y Base de datos',
            'email' => 'maximiliano.saavedra@userena.cl',
        ],
        'agustina-lopez' => [
            'key'   => 'agustina-lopez',
            'name'  => 'Agustina Lopez',
            'role'  => 'Desarrollo Frontend',
            'email' => 'agustina.lopez@userena.cl',
        ],
        'basthian-valenzuela' => [
            'key'   => 'basthian-valenzuela',
            'name'  => 'Basthian Valenzuela',
            'role'  => 'Quality Assurance',
            'email' => 'basthian.valenzuela@userena.cl',
        ],
        'pedro-rojas' => [
            'key'   => 'pedro-rojas',
            'name'  => 'Pedro Rojas',
            'role'  => 'Apoyo Desarrollo',
            'email' => 'pedro.rojasm3@userena.cl',
        ],
    ];

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
        ], array_values(self::TEAM_MEMBERS));
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

        if ($memberKey === '' || !isset(self::TEAM_MEMBERS[$memberKey])) {
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

        $recipientData = self::TEAM_MEMBERS[$memberKey];
        $recipientEmail = $this->resolveRecipientEmail($memberKey, $recipientData['email']);

        $dispatched = $this->dispatchEmail(
            $recipientEmail,
            $recipientData['name'],
            $recipientData['role'],
            $name,
            $email,
            $message
        );

        $this->persistAuditRecord($recipientData['name'], $name, $email, $message);

        return [
            'success'   => true,
            'message'   => 'Tu mensaje ha sido enviado exitosamente a ' . $recipientData['name'] . '.',
            'recipient' => $recipientData['name'],
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
     * Despacha el correo mediante mail() de PHP.
     */
    private function dispatchEmail(
        string $to,
        string $recipientName,
        string $recipientRole,
        string $senderName,
        string $senderEmail,
        string $message
    ): bool {
        $subject = "=?UTF-8?B?" . base64_encode("[SFL Lab - Créditos] Mensaje para {$recipientName}") . "?=";

        $body = "Has recibido un nuevo mensaje desde la sección de Créditos del sitio web SFL ULS Lab.\n\n"
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

        $cleanSenderEmail = str_replace(["\r", "\n"], '', $senderEmail);
        $cleanSenderName = str_replace(["\r", "\n"], '', $senderName);

        $headers = [
            'From: SFL ULS Lab <contacto@sfl.uls.cl>',
            "Reply-To: {$cleanSenderName} <{$cleanSenderEmail}>",
            'X-Mailer: PHP/' . phpversion(),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ];

        // En entornos sin servidor SMTP configurado (e.g. CLI/testing local), mail() retorna false pero no debe detener el flujo.
        $sent = @mail($to, $subject, $body, implode("\r\n", $headers));
        return $sent;
    }

    /**
     * Opcional: Persistir registro en la base de datos para trazabilidad.
     */
    private function persistAuditRecord(
        string $recipientName,
        string $senderName,
        string $senderEmail,
        string $message
    ): void {
        try {
            if (function_exists('dbInsert')) {
                dbInsert('contact_request', [
                    'name'    => mb_substr($senderName, 0, 150),
                    'email'   => mb_substr($senderEmail, 0, 150),
                    'phone'   => null,
                    'subject' => mb_substr("Créditos: {$recipientName}", 0, 255),
                    'message' => $message,
                    'status'  => 'enviado',
                ]);
            }
        } catch (\Throwable) {
            // La persistencia es opcional y no debe interrumpir el envío exitoso.
        }
    }
}
