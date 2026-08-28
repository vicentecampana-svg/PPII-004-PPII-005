<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\NewsRepository;

class NewsService
{
    private NewsRepository $repo;
    private AuditService $audit;

    public function __construct(?NewsRepository $repo = null, ?AuditService $audit = null)
    {
        $this->repo = $repo ?? new NewsRepository();
        $this->audit = $audit ?? new AuditService();
    }

    public function getAll(int $page, int $perPage): array
    {
        $total = $this->repo->countAll();
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

    public function getPublished(int $page, int $perPage): array
    {
        $total = $this->repo->countPublished();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        return [
            'items'       => $this->repo->findPublished($perPage, $offset),
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

    public function getPublishedById(int $id): ?array
    {
        $item = $this->repo->findById($id);
        if ($item && $item['status'] !== 'publicada') {
            return null;
        }
        return $item;
    }

    public function create(array $data, int $authorId): array
    {
        $errors = $this->validateCreate($data);
        if ($errors) {
            throw new \InvalidArgumentException(json_encode($errors));
        }

        $statusId = $this->repo->getStatusId('pendiente');

        $id = $this->repo->create([
            'author_id'  => $authorId,
            'status_id'  => $statusId,
            'title'      => $data['title'],
            'subtitle'   => $data['subtitle'] ?? null,
            'content'    => $data['content'],
            'image'      => $data['image'] ?? null,
            'tag_id'     => $data['tag_id'] ?? null,
        ]);

        $this->audit->log($authorId, 'crear', 'news', $id, 'Noticia creada: ' . $data['title']);

        return $this->repo->findById($id);
    }

    public function update(int $id, array $data): array
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Noticia no encontrada.');
        }

        $errors = $this->validateUpdate($data);
        if ($errors) {
            throw new \InvalidArgumentException(json_encode($errors));
        }

        $fields = ['updated_at' => date('Y-m-d H:i:s')];
        if (array_key_exists('title', $data))       $fields['title'] = $data['title'];
        if (array_key_exists('subtitle', $data))    $fields['subtitle'] = $data['subtitle'] ?? null;
        if (array_key_exists('content', $data))     $fields['content'] = $data['content'];
        if (array_key_exists('image', $data))       $fields['image'] = $data['image'] ?? null;
        if (array_key_exists('tag_id', $data))      $fields['tag_id'] = $data['tag_id'] ?? null;

        $this->repo->update($id, $fields);

        $this->audit->log(null, 'actualizar', 'news', $id, 'Noticia actualizada: ' . ($data['title'] ?? $existing['title']));

        return $this->repo->findById($id);
    }

    public function updateStatus(int $id, string $status): array
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Noticia no encontrada.');
        }

        $validStatuses = ['pendiente', 'publicada', 'archivada'];
        if (!in_array($status, $validStatuses, true)) {
            throw new \InvalidArgumentException(json_encode([
                'status' => "Estado inválido. Valores permitidos: " . implode(', ', $validStatuses),
            ]));
        }

        $statusId = $this->repo->getStatusId($status);
        if (!$statusId) {
            throw new \RuntimeException('Estado no encontrado en la base de datos.');
        }

        $fields = ['status_id' => $statusId, 'updated_at' => date('Y-m-d H:i:s')];
        if ($status === 'publicada' && !$existing['publication_date']) {
            $fields['publication_date'] = date('Y-m-d H:i:s');
        }

        $this->repo->update($id, $fields);

        $action = $status === 'publicada' ? 'aprobar' : ($status === 'archivada' ? 'archivar' : 'cambiar_estado');
        $this->audit->log(null, $action, 'news', $id, "Estado cambiado a {$status}");

        return $this->repo->findById($id);
    }

    public function delete(int $id): void
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Noticia no encontrada.');
        }

        $this->repo->delete($id);

        $this->audit->log(null, 'eliminar', 'news', $id, 'Noticia eliminada: ' . ($existing['title'] ?? ''));
    }

    private function validateCreate(array $data): array
    {
        $errors = [];
        if (empty($data['title']) || trim($data['title']) === '') {
            $errors['title'] = 'El título es obligatorio.';
        }
        if (empty($data['content']) || trim($data['content']) === '') {
            $errors['content'] = 'El contenido es obligatorio.';
        }
        return $errors;
    }

    private function validateUpdate(array $data): array
    {
        $errors = [];
        if (array_key_exists('title', $data) && (empty($data['title']) || trim($data['title']) === '')) {
            $errors['title'] = 'El título no puede estar vacío.';
        }
        if (array_key_exists('content', $data) && (empty($data['content']) || trim($data['content']) === '')) {
            $errors['content'] = 'El contenido no puede estar vacío.';
        }
        return $errors;
    }
}
