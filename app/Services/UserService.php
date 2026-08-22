<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;

class UserService
{
    private UserRepository $repo;

    public function __construct()
    {
        $this->repo = new UserRepository();
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

    public function getByEmail(string $email): ?array
    {
        return $this->repo->findByEmail($email);
    }

    public function create(array $data): array
    {
        $errors = $this->validateCreate($data);
        if ($errors) {
            throw new \InvalidArgumentException(json_encode($errors));
        }

        $existing = $this->repo->findByEmail($data['email']);
        if ($existing) {
            throw new \InvalidArgumentException(json_encode(['email' => 'El email ya está registrado.']));
        }

        $id = $this->repo->create([
            'username'             => $data['username'],
            'email'                => $data['email'],
            'password'             => password_hash($data['password'], PASSWORD_DEFAULT),
            'role_id'              => (int) $data['role_id'],
            'active'               => $data['active'] ?? true,
            'must_change_password' => $data['must_change_password'] ?? false,
        ]);

        return $this->repo->findById($id);
    }

    public function update(int $id, array $data): array
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Usuario no encontrado.');
        }

        $errors = $this->validateUpdate($data);
        if ($errors) {
            throw new \InvalidArgumentException(json_encode($errors));
        }

        $fields = [];
        if (array_key_exists('username', $data))             $fields['username'] = $data['username'];
        if (array_key_exists('email', $data))                $fields['email'] = $data['email'];
        if (array_key_exists('role_id', $data))              $fields['role_id'] = (int) $data['role_id'];
        if (array_key_exists('active', $data))               $fields['active'] = (bool) $data['active'];
        if (array_key_exists('must_change_password', $data)) $fields['must_change_password'] = (bool) $data['must_change_password'];

        if (isset($data['password']) && $data['password'] !== '') {
            $fields['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if ($fields) {
            $this->repo->update($id, $fields);
        }

        return $this->repo->findById($id);
    }

    public function delete(int $id): void
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Usuario no encontrado.');
        }

        $this->repo->delete($id);
    }

    public function getRoles(): array
    {
        return $this->repo->findAllRoles();
    }

    private function validateCreate(array $data): array
    {
        $errors = [];
        if (empty($data['username']) || trim($data['username']) === '') {
            $errors['username'] = 'El nombre de usuario es obligatorio.';
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email es obligatorio y debe ser válido.';
        }
        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors['password'] = 'La contraseña debe tener al menos 6 caracteres.';
        }
        if (empty($data['role_id'])) {
            $errors['role_id'] = 'El rol es obligatorio.';
        }
        return $errors;
    }

    private function validateUpdate(array $data): array
    {
        $errors = [];
        if (array_key_exists('username', $data) && (empty($data['username']) || trim($data['username']) === '')) {
            $errors['username'] = 'El nombre de usuario no puede estar vacío.';
        }
        if (array_key_exists('email', $data) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email debe ser válido.';
        }
        if (array_key_exists('password', $data) && $data['password'] !== '' && strlen($data['password']) < 6) {
            $errors['password'] = 'La contraseña debe tener al menos 6 caracteres.';
        }
        if (array_key_exists('role_id', $data) && empty($data['role_id'])) {
            $errors['role_id'] = 'El rol es obligatorio.';
        }
        return $errors;
    }
}
