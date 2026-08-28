<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\QueryRepository;

class QueryService
{
    private QueryRepository $repo;

    public function __construct(?QueryRepository $repo = null)
    {
        $this->repo = $repo ?? new QueryRepository();
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
            throw new \InvalidArgumentException(json_encode($errors));
        }

        $id = $this->repo->create([
            'name'    => $data['name'],
            'email'   => $data['email'],
            'phone'   => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
            'status'  => 'pendiente',
        ]);

        return $this->repo->findById($id);
    }

    public function setStatus(int $id, string $status): array
    {
        $validStatuses = ['pendiente', 'en_proceso', 'resuelto', 'archivado'];
        if (!in_array($status, $validStatuses, true)) {
            throw new \InvalidArgumentException(json_encode([
                'status' => "Estado inválido. Valores permitidos: " . implode(', ', $validStatuses),
            ]));
        }

        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Consulta no encontrada.');
        }

        $this->repo->updateStatus($id, $status);
        return $this->repo->findById($id);
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['name']) || trim($data['name']) === '') {
            $errors['name'] = 'El nombre es obligatorio.';
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email es obligatorio y debe ser válido.';
        }
        if (empty($data['message']) || trim($data['message']) === '') {
            $errors['message'] = 'El mensaje es obligatorio.';
        }
        return $errors;
    }
}
