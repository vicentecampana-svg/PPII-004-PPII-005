<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TagRepository;

class TagService
{
    private TagRepository $repo;
    private AuditService $audit;

    public function __construct(?TagRepository $repo = null, ?AuditService $audit = null)
    {
        $this->repo = $repo ?? new TagRepository();
        $this->audit = $audit ?? new AuditService();
    }

    public function getAll(): array
    {
        return $this->repo->findAll();
    }

    public function getById(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function create(array $data): array
    {
        if (empty($data['name']) || trim($data['name']) === '') {
            throw new \InvalidArgumentException(json_encode(['name' => 'El nombre es obligatorio.']));
        }

        $id = $this->repo->create(['name' => $data['name']]);

        $this->audit->log(null, 'crear', 'tag', $id, 'Etiqueta creada: ' . $data['name']);

        return $this->repo->findById($id);
    }

    public function delete(int $id): void
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Etiqueta no encontrada.');
        }

        $this->repo->delete($id);

        $this->audit->log(null, 'eliminar', 'tag', $id, 'Etiqueta eliminada: ' . ($existing['name'] ?? ''));
    }
}
