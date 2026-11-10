<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NewsConflictException;
use App\Exceptions\ValidationException;
use App\Repositories\NewsRepository;
use App\Repositories\TagRepository;

class NewsService
{
    public const TITLE_MIN = 5;
    public const TITLE_MAX = 150;
    public const CONTENT_MIN = 20;
    public const CONTENT_MAX = 10000;
    public const TAGS_MAX = 5;
    public const TAG_MIN = 2;
    public const TAG_MAX = 30;

    private NewsRepository $repo;
    private AuditService $audit;
    private TagRepository $tags;

    public function __construct(?NewsRepository $repo = null, ?AuditService $audit = null, ?TagRepository $tags = null)
    {
        $this->repo = $repo ?? new NewsRepository();
        $this->audit = $audit ?? new AuditService();
        $this->tags = $tags ?? new TagRepository();
    }

    public function getAll(int $page, int $perPage, string $query = '', ?int $tagId = null): array
    {
        $total = $this->repo->countAll($query, $tagId);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        return [
            'items'       => $this->repo->findAll($perPage, $offset, $query, $tagId),
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * @param bool $includePrivate RF-NEW-002: las publicadas Privadas solo se
     *                             listan para usuarios autenticados.
     */
    public function getPublished(int $page, int $perPage, string $query = '', ?int $tagId = null, bool $includePrivate = false): array
    {
        $total = $this->repo->countPublished($query, $tagId, $includePrivate);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        return [
            'items'       => $this->repo->findPublished($perPage, $offset, $query, $tagId, $includePrivate),
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    public function search(string $query, ?int $tagId = null, int $page = 1, int $perPage = 20, bool $onlyPublished = true): array
    {
        return $onlyPublished
            ? $this->getPublished($page, $perPage, $query, $tagId)
            : $this->getAll($page, $perPage, $query, $tagId);
    }

    public function getById(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function getPublishedById(int $id, bool $includePrivate = false): ?array
    {
        $item = $this->repo->findById($id);
        if (!$item || $item['status'] !== 'publicada') {
            return null;
        }
        if (!$includePrivate && empty($item['is_public'])) {
            return null;
        }
        return $item;
    }

    public function create(array $data, int $authorId): array
    {
        $errors = $this->validateCreate($data);
        if ($errors) {
            throw new ValidationException($errors);
        }

        $authorDisplay = $data['author_display'] ?? null;
        if (is_string($authorDisplay)) {
            $authorDisplay = trim($authorDisplay);
            $authorDisplay = $authorDisplay !== '' ? $authorDisplay : null;
        }

        // RF-AUD-001: la noticia solo queda guardada si también queda auditada.
        $id = dbTransaction(function () use ($data, $authorId, $authorDisplay): int {
            $tagIds = $this->resolveTagIds($data);

            $id = $this->repo->create([
                'author_id'      => $authorId,
                'status_id'      => $this->repo->getStatusId('pendiente'),
                'author_display' => $authorDisplay,
                'title'          => $data['title'],
                'subtitle'       => $data['subtitle'] ?? null,
                'content'        => $data['content'],
                'image'           => $data['image'] ?? null,
                'is_public'      => $data['is_public'] ?? true,
                'tag_id'         => $tagIds[0] ?? ($data['tag_id'] ?? null),
            ]);

            if (!empty($tagIds)) {
                $this->repo->syncTags($id, $tagIds);
            }

            $this->audit->log($authorId, 'crear', 'news', $id, 'Noticia creada: ' . $data['title']);

            return $id;
        });

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
            throw new ValidationException($errors);
        }

        $fields = ['updated_at' => date('Y-m-d H:i:s')];
        if (array_key_exists('author_display', $data)) {
            $authorDisplay = $data['author_display'];
            if (is_string($authorDisplay)) {
                $authorDisplay = trim($authorDisplay);
                $authorDisplay = $authorDisplay !== '' ? $authorDisplay : null;
            }
            $fields['author_display'] = $authorDisplay;
        }
        if (array_key_exists('title', $data)) {
            $fields['title'] = $data['title'];
        }
        if (array_key_exists('subtitle', $data)) {
            $fields['subtitle'] = $data['subtitle'] ?? null;
        }
        if (array_key_exists('content', $data)) {
            $fields['content'] = $data['content'];
        }
        if (array_key_exists('image', $data)) {
            $fields['image'] = $data['image'] ?? null;
        }
        if (array_key_exists('is_public', $data)) {
            $fields['is_public'] = (bool) $data['is_public'];
        }

        dbTransaction(function () use ($id, $data, $fields, $existing): void {
            if (array_key_exists('tag_ids', $data) || array_key_exists('tag_id', $data) || array_key_exists('tags', $data)) {
                $tagIds = $this->resolveTagIds($data);
                $fields['tag_id'] = $tagIds[0] ?? null;
                $this->repo->syncTags($id, $tagIds);
            }

            $this->repo->update($id, $fields);

            $this->audit->log(null, 'actualizar', 'news', $id, 'Noticia actualizada: ' . ($data['title'] ?? $existing['title']));
        });

        return $this->repo->findById($id);
    }

    /**
     * RF-NEW-002 y RF-NEW-004: aprueba o rechaza una noticia Pendiente.
     *
     * Aprobar exige la visibilidad (publica o privada) en el mismo acto. Solo
     * la primera resolución se aplica: si otro editor ya la resolvió, se
     * lanza NewsConflictException y no se registra una segunda resolución.
     */
    public function resolve(int $id, string $status, ?string $visibility = null): array
    {
        if (!in_array($status, ['publicada', 'archivada'], true)) {
            throw new ValidationException(['status' => 'La resolución debe ser aprobar o rechazar.']);
        }

        $isPublic = null;
        if ($status === 'publicada') {
            if (!in_array($visibility, ['publica', 'privada'], true)) {
                throw new ValidationException([
                    'visibility' => 'La visibilidad es obligatoria para aprobar: elige Pública o Privada.',
                ]);
            }
            $isPublic = $visibility === 'publica';
        }

        dbTransaction(function () use ($id, $status, $isPublic, $visibility): void {
            $resolved = $this->repo->resolvePending(
                $id,
                (int) $this->repo->getStatusId('pendiente'),
                (int) $this->repo->getStatusId($status),
                $isPublic
            );

            if ($resolved === 0) {
                if (!$this->repo->findById($id)) {
                    throw new \RuntimeException('Noticia no encontrada.');
                }
                throw new NewsConflictException('Esta noticia ya fue resuelta por otro editor.');
            }

            $details = $status === 'publicada'
                ? 'Noticia aprobada con visibilidad ' . ($visibility === 'publica' ? 'Pública' : 'Privada')
                : 'Noticia rechazada y archivada';
            $this->audit->log(null, $status === 'publicada' ? 'aprobar' : 'archivar', 'news', $id, $details);
        });

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
            throw new ValidationException([
                'status' => "Estado inválido. Valores permitidos: " . implode(', ', $validStatuses),
            ]);
        }

        $statusId = $this->repo->getStatusId($status);
        if (!$statusId) {
            throw new \RuntimeException('Estado no encontrado en la base de datos.');
        }

        $fields = ['status_id' => $statusId, 'updated_at' => date('Y-m-d H:i:s')];
        if ($status === 'publicada' && empty($existing['publication_date'])) {
            $fields['publication_date'] = date('Y-m-d H:i:s');
        }

        $action = $status === 'publicada' ? 'aprobar' : ($status === 'archivada' ? 'archivar' : 'cambiar_estado');

        dbTransaction(function () use ($id, $fields, $action, $status): void {
            $this->repo->update($id, $fields);
            $this->audit->log(null, $action, 'news', $id, "Estado cambiado a {$status}");
        });

        return $this->repo->findById($id);
    }

    public function delete(int $id): void
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Noticia no encontrada.');
        }

        dbTransaction(function () use ($id, $existing): void {
            $this->repo->delete($id);
            $this->audit->log(null, 'eliminar', 'news', $id, 'Noticia eliminada: ' . ($existing['title'] ?? ''));
        });
    }

    private function extractTagIds(array $data): array
    {
        $tagIds = [];
        if (!empty($data['tag_ids']) && is_array($data['tag_ids'])) {
            foreach ($data['tag_ids'] as $tid) {
                if (is_numeric($tid)) {
                    $tagIds[] = (int) $tid;
                }
            }
        } elseif (!empty($data['tag_id']) && is_numeric($data['tag_id'])) {
            $tagIds[] = (int) $data['tag_id'];
        }

        return array_values(array_unique($tagIds));
    }

    /**
     * Ids de tags indicados por id (tag_ids / tag_id) más los escritos por
     * nombre en 'tags' (separados por comas), que se crean si no existen.
     */
    private function resolveTagIds(array $data): array
    {
        $tagIds = $this->extractTagIds($data);
        foreach (self::parseTagNames($data['tags'] ?? null) as $name) {
            $tagIds[] = $this->tags->findOrCreate($name);
        }

        return array_values(array_unique($tagIds));
    }

    /** @return string[] */
    public static function parseTagNames(mixed $tags): array
    {
        if (is_array($tags)) {
            $tags = implode(',', $tags);
        }
        if (!is_string($tags)) {
            return [];
        }

        $names = array_map(static fn(string $t): string => trim($t), explode(',', $tags));

        return array_values(array_unique(array_filter($names, static fn(string $t): bool => $t !== '')));
    }

    private function validateCreate(array $data): array
    {
        $errors = [];
        $this->validateTitle($data['title'] ?? '', $errors);
        $this->validateContent($data['content'] ?? '', $errors);
        $this->validateTags($data, $errors);
        $this->validateAuthorDisplay($data, $errors);
        return $errors;
    }

    private function validateUpdate(array $data): array
    {
        $errors = [];
        if (array_key_exists('title', $data)) {
            $this->validateTitle($data['title'], $errors);
        }
        if (array_key_exists('content', $data)) {
            $this->validateContent($data['content'], $errors);
        }
        $this->validateTags($data, $errors);
        $this->validateAuthorDisplay($data, $errors);
        return $errors;
    }

    // RF-NEW-001: título de 5 a 150 caracteres y cuerpo de 20 a 10.000.
    private function validateTitle(mixed $title, array &$errors): void
    {
        $length = mb_strlen(trim((string) $title));
        if ($length === 0) {
            $errors['title'] = 'El título es obligatorio.';
        } elseif ($length < self::TITLE_MIN || $length > self::TITLE_MAX) {
            $errors['title'] = 'El título debe tener entre ' . self::TITLE_MIN . ' y ' . self::TITLE_MAX . ' caracteres.';
        }
    }

    private function validateContent(mixed $content, array &$errors): void
    {
        $length = mb_strlen(trim((string) $content));
        if ($length === 0) {
            $errors['content'] = 'El cuerpo de la noticia es obligatorio.';
        } elseif ($length < self::CONTENT_MIN || $length > self::CONTENT_MAX) {
            $errors['content'] = 'El cuerpo de la noticia debe tener entre ' . self::CONTENT_MIN . ' y '
                . number_format(self::CONTENT_MAX, 0, ',', '.') . ' caracteres.';
        }
    }

    // RF-NEW-001: hasta 5 tags, de 2 a 30 caracteres cada uno.
    private function validateTags(array $data, array &$errors): void
    {
        $names = self::parseTagNames($data['tags'] ?? null);
        if (count($names) > self::TAGS_MAX) {
            $errors['tags'] = 'Se permiten como máximo ' . self::TAGS_MAX . ' tags por noticia.';
            return;
        }
        foreach ($names as $name) {
            $length = mb_strlen($name);
            if ($length < self::TAG_MIN || $length > self::TAG_MAX) {
                $errors['tags'] = 'Cada tag debe tener entre ' . self::TAG_MIN . ' y ' . self::TAG_MAX . ' caracteres.';
                return;
            }
        }
    }

    private function validateAuthorDisplay(array $data, array &$errors): void
    {
        if (!array_key_exists('author_display', $data) || $data['author_display'] === null) {
            return;
        }

        if (!is_string($data['author_display']) || mb_strlen(trim($data['author_display'])) > 255) {
            $errors['author_display'] = 'El nombre del redactor debe tener como máximo 255 caracteres.';
        }
    }
}
