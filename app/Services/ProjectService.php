<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProjectRepository;

class ProjectService
{
    private ProjectRepository $repo;
    private AuditService $audit;

    public function __construct(?ProjectRepository $repo = null, ?AuditService $audit = null)
    {
        $this->repo = $repo ?? new ProjectRepository();
        $this->audit = $audit ?? new AuditService();
    }

    public function getAll(int $page, int $perPage, bool $showInactive = false): array
    {
        $total = $showInactive ? $this->repo->countAll() : $this->repo->countActive();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $items = $showInactive
            ? $this->repo->findAll($perPage, $offset)
            : $this->repo->findAllActive($perPage, $offset);

        return [
            'items'       => $items,
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
            'description' => $data['description'] ?? null,
            'image'       => $data['image'] ?? null,
            'link'        => $data['link'] ?? null,
            'active'      => $data['active'] ?? true,
        ]);

        $this->audit->log(null, 'crear', 'project', $id, 'Proyecto creado: ' . $data['name']);

        return $this->repo->findById($id);
    }

    public function update(int $id, array $data): array
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Proyecto no encontrado.');
        }

        $errors = $this->validate($data, true);
        if ($errors) {
            throw new \InvalidArgumentException(json_encode($errors));
        }

        $fields = [];
        if (array_key_exists('name', $data)) {
            $fields['name'] = $data['name'];
        }
        if (array_key_exists('description', $data)) {
            $fields['description'] = $data['description'] ?? null;
        }
        if (array_key_exists('image', $data)) {
            $fields['image'] = $data['image'] ?? null;
        }
        if (array_key_exists('link', $data)) {
            $fields['link'] = $data['link'] ?? null;
        }
        if (array_key_exists('active', $data)) {
            $fields['active'] = (bool) $data['active'];
        }

        if ($fields) {
            $this->repo->update($id, $fields);
        }

        $this->audit->log(null, 'actualizar', 'project', $id, 'Proyecto actualizado: ' . ($data['name'] ?? $existing['name']));

        return $this->repo->findById($id);
    }

    public function setStatus(int $id, bool $active): array
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Proyecto no encontrado.');
        }

        $this->repo->update($id, ['active' => $active]);

        $this->audit->log(null, 'cambiar_estado', 'project', $id, 'Estado cambiado a ' . ($active ? 'activo' : 'inactivo'));

        return $this->repo->findById($id);
    }

    public function delete(int $id): void
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Proyecto no encontrado.');
        }

        $this->repo->delete($id);

        $this->audit->log(null, 'eliminar', 'project', $id, 'Proyecto eliminado: ' . ($existing['name'] ?? ''));
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
