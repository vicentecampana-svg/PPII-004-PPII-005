<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\QueryRepository;

/**
 * SEC-09: aviso agrupado de consultas de contacto al encargado.
 *
 * En lugar de un correo por consulta (lo que permitiría agotar la cuota del
 * proveedor enviando el formulario de forma automatizada), se envía como
 * máximo un aviso cada CONTACT_DIGEST_INTERVAL_MINUTES y solo si hay al menos
 * una consulta acumulada desde el último aviso.
 *
 * sendIfDue() se llama al recibir cada consulta y desde bin/contact-digest.php,
 * que debe programarse con cron para que las consultas acumuladas también se
 * avisen cuando no llegan consultas nuevas.
 */
class ContactDigestService
{
    public const DEFAULT_INTERVAL_MINUTES = 60;

    /** Máximo de consultas detalladas por aviso; el resto pasa al siguiente. */
    public const MAX_ITEMS = 20;

    private QueryRepository $repo;
    private MailerService $mailer;
    private MailBudgetService $budget;
    private FooterService $footer;

    public function __construct(
        ?QueryRepository $repo = null,
        ?MailerService $mailer = null,
        ?MailBudgetService $budget = null,
        ?FooterService $footer = null
    ) {
        $this->repo   = $repo   ?? new QueryRepository();
        $this->budget = $budget ?? new MailBudgetService();
        $this->mailer = $mailer ?? new MailerService($this->budget);
        $this->footer = $footer ?? new FooterService();
    }

    public function intervalMinutes(): int
    {
        $value = trim((string) getenv('CONTACT_DIGEST_INTERVAL_MINUTES'));

        return ctype_digit($value) && (int) $value > 0 ? (int) $value : self::DEFAULT_INTERVAL_MINUTES;
    }

    /**
     * Envía el aviso si ya pasó el intervalo desde el anterior y hay consultas
     * acumuladas. Devuelve true solo si se envió un aviso.
     */
    public function sendIfDue(): bool
    {
        // Evita que dos solicitudes simultáneas envíen el mismo aviso.
        $locked = (bool) (dbFetchOne(
            'SELECT pg_try_advisory_lock(hashtext(:key)) AS locked',
            ['key' => 'contact_digest']
        )['locked'] ?? false);
        if (!$locked) {
            return false;
        }

        try {
            return $this->sendIfDueLocked();
        } finally {
            dbQuery('SELECT pg_advisory_unlock(hashtext(:key))', ['key' => 'contact_digest']);
        }
    }

    private function sendIfDueLocked(): bool
    {
        // El intervalo se cuenta desde el último intento, aunque haya fallado,
        // para no reintentar en cada consulta si el proveedor está caído.
        $last = $this->budget->lastReservationAt(MailBudgetService::SCOPE_CONTACT);
        if ($last !== null && time() - strtotime($last) < $this->intervalMinutes() * 60) {
            return false;
        }

        $total = $this->repo->countUnnotified();
        if ($total === 0) {
            return false;
        }

        $contacts = $this->repo->findUnnotified(self::MAX_ITEMS);
        if (!$this->mailer->sendContactDigest($this->resolveNotifyEmail(), $contacts, $total)) {
            return false;
        }

        $this->repo->markNotifiedUpTo((int) max(array_column($contacts, 'id')));

        return true;
    }

    /**
     * Resuelve el correo del encargado: primero CONTACT_NOTIFY_EMAIL, luego el
     * email configurado en el footer y, como último recurso, un correo por defecto.
     */
    private function resolveNotifyEmail(): string
    {
        $env = getenv('CONTACT_NOTIFY_EMAIL');
        if ($env !== false && $env !== '' && filter_var($env, FILTER_VALIDATE_EMAIL)) {
            return $env;
        }

        try {
            $footer = $this->footer->getAll();
            $email = trim((string) ($footer['info']['email'] ?? ''));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        } catch (\Throwable) {
            // Si el footer falla, se usa el correo por defecto.
        }

        return 'contacto@techhub.cl';
    }
}
