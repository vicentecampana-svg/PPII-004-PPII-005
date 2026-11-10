<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Repositories\ServiceRepository;

class ServiceService
{
    public const NAME_MIN = 3;
    public const NAME_MAX = 100;
    public const DESCRIPTION_MIN = 10;
    public const DESCRIPTION_MAX = 1000;

    private ServiceRepository $repo;
    private AuditService $audit;

    public function __construct(?ServiceRepository $repo = null, ?AuditService $audit = null)
    {
        $this->repo = $repo ?? new ServiceRepository();
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
            throw new ValidationException($errors);
        }

        $id = dbTransaction(function () use ($data): int {
            $id = $this->repo->create([
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'image'       => $data['image'] ?? null,
                'link'        => $data['link'] ?? null,
                'active'      => $data['active'] ?? true,
            ]);

            $this->audit->log(null, 'crear', 'service', $id, 'Servicio creado: ' . $data['name']);

            return $id;
        });

        return $this->repo->findById($id);
    }

    public function update(int $id, array $data): array
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Servicio no encontrado.');
        }

        $errors = $this->validate($data, true);
        if ($errors) {
            throw new ValidationException($errors);
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

        dbTransaction(function () use ($id, $fields, $data, $existing): void {
            if ($fields) {
                $this->repo->update($id, $fields);
            }

            $this->audit->log(null, 'actualizar', 'service', $id, 'Servicio actualizado: ' . ($data['name'] ?? $existing['name']));
        });

        return $this->repo->findById($id);
    }

    public function setStatus(int $id, bool $active): array
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Servicio no encontrado.');
        }

        $this->repo->update($id, ['active' => $active]);

        $this->audit->log(null, 'cambiar_estado', 'service', $id, 'Estado cambiado a ' . ($active ? 'activo' : 'inactivo'));

        return $this->repo->findById($id);
    }

    public function delete(int $id): void
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Servicio no encontrado.');
        }

        dbTransaction(function () use ($id, $existing): void {
            $this->repo->delete($id);
            $this->audit->log(null, 'eliminar', 'service', $id, 'Servicio eliminado: ' . ($existing['name'] ?? ''));
        });
    }

    // RF-SRV-001: nombre de 3 a 100 caracteres, descripción de 10 a 1.000 y
    // enlace opcional, que de existir debe ser una URL http o https.
    private function validate(array $data, bool $partial = false): array
    {
        $errors = [];

        if (!$partial || array_key_exists('name', $data)) {
            $length = mb_strlen(trim((string) ($data['name'] ?? '')));
            if ($length === 0) {
                $errors['name'] = 'El nombre del servicio es obligatorio.';
            } elseif ($length < self::NAME_MIN || $length > self::NAME_MAX) {
                $errors['name'] = 'El nombre del servicio debe tener entre ' . self::NAME_MIN . ' y ' . self::NAME_MAX . ' caracteres.';
            }
        }

        if (!$partial || array_key_exists('description', $data)) {
            $length = mb_strlen(trim((string) ($data['description'] ?? '')));
            if ($length === 0) {
                $errors['description'] = 'La descripción del servicio es obligatoria.';
            } elseif ($length < self::DESCRIPTION_MIN || $length > self::DESCRIPTION_MAX) {
                $errors['description'] = 'La descripción del servicio debe tener entre ' . self::DESCRIPTION_MIN
                    . ' y ' . number_format(self::DESCRIPTION_MAX, 0, ',', '.') . ' caracteres.';
            }
        }

        $link = trim((string) ($data['link'] ?? ''));
        if ($link !== '' && !self::isHttpUrl($link)) {
            $errors['link'] = 'El enlace debe ser una URL válida que comience con http:// o https://.';
        }

        return $errors;
    }

    public static function isHttpUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}
