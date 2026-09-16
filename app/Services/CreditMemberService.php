<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CreditMemberRepository;

/**
 * CRUD de los integrantes del apartado Créditos.
 *
 * Endpoints administrados por el Super Admin (nombre, cargo y correo de
 * contacto). Las respuestas públicas nunca exponen el correo; solo las
 * operaciones de escritura y las lecturas con privilegios lo incluyen.
 */
class CreditMemberService
{
    private const NAME_MAX  = 150;
    private const ROLE_MAX  = 100;
    private const EMAIL_MAX = 150;

    private CreditMemberRepository $repo;
    private AuditService $audit;

    public function __construct(?CreditMemberRepository $repo = null, ?AuditService $audit = null)
    {
        $this->repo  = $repo  ?? new CreditMemberRepository();
        $this->audit = $audit ?? new AuditService();
    }

    public function getAll(int $page, int $perPage, bool $withEmail = false): array
    {
        $total = $this->repo->count();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        return [
            'items'       => $this->repo->findAll($perPage, $offset, $withEmail),
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * Lista completa para la vista pública, sin correo electrónico.
     */
    public function getAllPublic(): array
    {
        return array_map(static fn(array $m): array => [
            'key'  => $m['key'],
            'name' => $m['name'],
            'role' => $m['role'],
        ], $this->repo->findAllPublic());
    }

    public function getById(int $id, bool $withEmail = false): ?array
    {
        return $this->repo->findById($id, $withEmail);
    }

    public function create(array $data): array
    {
        $errors = $this->validate($data);
        if ($errors) {
            throw new \InvalidArgumentException(json_encode($errors));
        }

        $key = $this->resolveKey($data['name'], $data['key'] ?? '');

        $id = $this->repo->create([
            'key'   => $key,
            'name'  => $data['name'],
            'role'  => $data['role'],
            'email' => $data['email'],
            'orden' => $this->repo->nextOrden(),
        ]);

        $this->audit->log(null, 'crear', 'credit_member', $id, 'Integrante de créditos creado: ' . $data['name']);

        return $this->repo->findById($id, true);
    }

    public function update(int $id, array $data): array
    {
        $existing = $this->repo->findById($id, true);
        if (!$existing) {
            throw new \RuntimeException('Integrante de créditos no encontrado.');
        }

        $errors = $this->validate($data, true);
        if ($errors) {
            throw new \InvalidArgumentException(json_encode($errors));
        }

        $fields = [];
        foreach (['name', 'role', 'email'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[$field] = $data[$field];
            }
        }
        if (array_key_exists('orden', $data) && is_numeric($data['orden'])) {
            $fields['orden'] = max(0, (int) $data['orden']);
        }

        // La clave (key) no es editable para no romper enlaces del formulario
        // de contacto que ya estén guardados.

        if ($fields) {
            $this->repo->update($id, $fields);
        }

        $this->audit->log(null, 'actualizar', 'credit_member', $id, 'Integrante de créditos actualizado: ' . ($data['name'] ?? $existing['name']));

        return $this->repo->findById($id, true);
    }

    public function delete(int $id): void
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Integrante de créditos no encontrado.');
        }

        $this->repo->delete($id);

        $this->audit->log(null, 'eliminar', 'credit_member', $id, 'Integrante de créditos eliminado: ' . $existing['name']);
    }

    private function validate(array $data, bool $partial = false): array
    {
        $errors = [];

        if (array_key_exists('name', $data)) {
            $name = trim((string) $data['name']);
            if ($name === '') {
                $errors['name'] = $partial ? 'El nombre no puede estar vacío.' : 'El nombre es obligatorio.';
            } elseif (mb_strlen($name) > self::NAME_MAX) {
                $errors['name'] = 'El nombre no puede superar los ' . self::NAME_MAX . ' caracteres.';
            }
        } elseif (!$partial) {
            $errors['name'] = 'El nombre es obligatorio.';
        }

        if (array_key_exists('role', $data)) {
            $role = trim((string) $data['role']);
            if ($role === '') {
                $errors['role'] = 'El cargo es obligatorio.';
            } elseif (mb_strlen($role) > self::ROLE_MAX) {
                $errors['role'] = 'El cargo no puede superar los ' . self::ROLE_MAX . ' caracteres.';
            }
        } elseif (!$partial) {
            $errors['role'] = 'El cargo es obligatorio.';
        }

        if (array_key_exists('email', $data)) {
            $email = trim((string) $data['email']);
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Ingresa un correo de contacto válido.';
            } elseif (strlen($email) > self::EMAIL_MAX) {
                $errors['email'] = 'El correo no puede superar los ' . self::EMAIL_MAX . ' caracteres.';
            }
        } elseif (!$partial) {
            $errors['email'] = 'El correo de contacto es obligatorio.';
        }

        return $errors;
    }

    /**
     * Resuelve la clave única del integrante: usa la enviada o genera una a
     * partir del nombre, garantizando que no colisione con una existente.
     */
    private function resolveKey(string $name, string $requestedKey): string
    {
        $base = trim($requestedKey) !== ''
            ? $this->slugify($requestedKey)
            : $this->slugify($name);

        if ($base === '') {
            $base = 'integrantes';
        }

        if (!$this->repo->keyExists($base)) {
            return $base;
        }

        $counter = 2;
        while ($this->repo->keyExists($base . '-' . $counter)) {
            $counter++;
        }

        return $base . '-' . $counter;
    }

    /**
     * Convierte un texto en un slug URL-safe (sin tildes ni caracteres raros).
     */
    private function slugify(string $value): string
    {
        $table = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n', 'Á' => 'a', 'É' => 'e', 'Í' => 'i',
            'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ñ' => 'n',
        ];
        $value = strtolower(strtr($value, $table));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }
}
