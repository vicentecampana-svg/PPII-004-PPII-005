<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\StaffRepository;

class StaffService
{
    private StaffRepository $repo;
    private AuditService $audit;

    public function __construct(?StaffRepository $repo = null, ?AuditService $audit = null)
    {
        $this->repo = $repo ?? new StaffRepository();
        $this->audit = $audit ?? new AuditService();
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
            'name'        => $data['name'],
            'position'    => $data['position'] ?? null,
            'photo'       => $data['photo'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        $this->audit->log(null, 'crear', 'staff_member', $id, 'Miembro del equipo creado: ' . $data['name']);

        return $this->repo->findById($id);
    }

    public function update(int $id, array $data): array
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Miembro del equipo no encontrado.');
        }

        $errors = $this->validate($data, true);
        if ($errors) {
            throw new \InvalidArgumentException(json_encode($errors));
        }

        $fields = [];
        if (array_key_exists('name', $data))        $fields['name'] = $data['name'];
        if (array_key_exists('position', $data))     $fields['position'] = $data['position'] ?? null;
        if (array_key_exists('photo', $data))        $fields['photo'] = $data['photo'] ?? null;
        if (array_key_exists('description', $data))  $fields['description'] = $data['description'] ?? null;

        if ($fields) {
            $this->repo->update($id, $fields);
        }

        $this->audit->log(null, 'actualizar', 'staff_member', $id, 'Miembro del equipo actualizado: ' . ($data['name'] ?? $existing['name']));

        return $this->repo->findById($id);
    }

    public function delete(int $id): void
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Miembro del equipo no encontrado.');
        }

        $this->repo->delete($id);

        $this->audit->log(null, 'eliminar', 'staff_member', $id, 'Miembro del equipo eliminado: ' . ($existing['name'] ?? ''));
    }

    private function validate(array $data, bool $partial = false): array
    {
        $errors = [];
        if (!$partial && (empty($data['name']) || trim($data['name']) === '')) {
            $errors['name'] = 'El nombre es obligatorio.';
        }
        if ($partial && array_key_exists('name', $data) && (empty($data['name']) || trim($data['name']) === '')) {
            $errors['name'] = 'El nombre no puede estar vacío.';
        }
        return $errors;
    }
}
