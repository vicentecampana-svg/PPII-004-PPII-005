<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Repositories\QueryRepository;

class QueryService
{
    /** RF-CON-001: tipos de interacción del formulario de contacto. */
    public const TYPES = ['Sugerencia', 'Consulta', 'Solicitud'];
    public const NAME_MIN = 2;
    public const NAME_MAX = 100;
    public const MESSAGE_MIN = 10;
    public const MESSAGE_MAX = 2000;

    private QueryRepository $repo;
    private AuditService $audit;
    private ContactDigestService $digest;

    public function __construct(
        ?QueryRepository $repo = null,
        ?AuditService $audit = null,
        ?ContactDigestService $digest = null
    ) {
        $this->repo   = $repo   ?? new QueryRepository();
        $this->audit  = $audit  ?? new AuditService();
        $this->digest = $digest ?? new ContactDigestService($this->repo);
    }

    public function getAll(int $page, int $perPage): array
    {
        $total = $this->repo->count();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        return [
            'items'       => $this->repo->findAll($perPage, $offset),
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
            'pending'     => $this->repo->countPending(),
        ];
    }

    public function getById(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function create(array $data): array
    {
        $errors = $this->validate($data);
        if ($errors) {
            throw new ValidationException($errors);
        }

        $id = $this->repo->create([
            'name'    => $data['name'],
            'email'   => $data['email'],
            'phone'   => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
            'status'  => 'pendiente',
        ]);

        $this->audit->log(null, 'crear', 'contact_request', $id, 'Consulta recibida de: ' . $data['name']);

        $this->notify();

        return $this->repo->findById($id);
    }

    public function setStatus(int $id, string $status): array
    {
        $validStatuses = ['pendiente', 'en_proceso', 'resuelto', 'archivado'];
        if (!in_array($status, $validStatuses, true)) {
            throw new ValidationException([
                'status' => "Estado inválido. Valores permitidos: " . implode(', ', $validStatuses),
            ]);
        }

        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Consulta no encontrada.');
        }

        $this->repo->updateStatus($id, $status);

        $action = $status === 'archivado' ? 'archivar' : 'cambiar_estado';
        $this->audit->log(null, $action, 'contact_request', $id, "Estado de consulta cambiado a {$status}");

        return $this->repo->findById($id);
    }

    // RF-CON-001: nombre de 2 a 100 caracteres, correo válido, tipo de
    // interacción obligatorio y mensaje de 10 a 2.000 caracteres.
    private function validate(array $data): array
    {
        $errors = [];
        $name = mb_strlen(trim((string) ($data['name'] ?? '')));
        if ($name === 0) {
            $errors['name'] = 'El nombre es obligatorio.';
        } elseif ($name < self::NAME_MIN || $name > self::NAME_MAX) {
            $errors['name'] = 'El nombre debe tener entre ' . self::NAME_MIN . ' y ' . self::NAME_MAX . ' caracteres.';
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El correo es obligatorio y debe ser válido.';
        }
        if (!in_array($data['subject'] ?? null, self::TYPES, true)) {
            $errors['subject'] = 'Selecciona el tipo de interacción: ' . implode(', ', self::TYPES) . '.';
        }
        $message = mb_strlen(trim((string) ($data['message'] ?? '')));
        if ($message === 0) {
            $errors['message'] = 'El mensaje es obligatorio.';
        } elseif ($message < self::MESSAGE_MIN || $message > self::MESSAGE_MAX) {
            $errors['message'] = 'El mensaje debe tener entre ' . self::MESSAGE_MIN . ' y '
                . number_format(self::MESSAGE_MAX, 0, ',', '.') . ' caracteres.';
        }
        return $errors;
    }

    /**
     * IS-002: el encargado recibe el aviso en el momento, sin esperar una
     * agrupación. Un fallo de correo no debe impedir registrar la consulta:
     * queda pendiente de aviso y bin/contact-digest.php la reintenta.
     */
    private function notify(): void
    {
        try {
            $this->digest->sendPending();
        } catch (\Throwable $e) {
            // La notificación es accesoria; la consulta ya quedó registrada.
            error_log('[SFL ULS] No se pudo enviar el aviso de consultas: ' . $e->getMessage());
        }
    }
}
