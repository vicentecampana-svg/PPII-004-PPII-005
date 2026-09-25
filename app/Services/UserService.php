<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;

class UserService
{
    private UserRepository $repo;
    private AuditService $audit;

    public function __construct(?UserRepository $repo = null, ?AuditService $audit = null)
    {
        $this->repo = $repo ?? new UserRepository();
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

        $this->audit->log(null, 'crear', 'user', $id, 'Usuario creado: ' . $data['username']);

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
        if (array_key_exists('username', $data)) {
            $fields['username'] = $data['username'];
        }
        if (array_key_exists('email', $data)) {
            $fields['email'] = $data['email'];
        }
        if (array_key_exists('role_id', $data)) {
            $fields['role_id'] = (int) $data['role_id'];
        }
        if (array_key_exists('active', $data)) {
            $fields['active'] = (bool) $data['active'];
        }
        if (array_key_exists('must_change_password', $data)) {
            $fields['must_change_password'] = (bool) $data['must_change_password'];
        }

        if (isset($data['password']) && $data['password'] !== '') {
            $fields['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if ($fields) {
            $this->repo->update($id, $fields);
        }

        $this->audit->log(null, 'actualizar', 'user', $id, 'Usuario actualizado: ' . ($data['username'] ?? $existing['username']));

        return $this->repo->findById($id);
    }

    public function resetPassword(int $id, string $newPassword): array
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Usuario no encontrado.');
        }

        if (strlen($newPassword) < 12) {
            throw new \InvalidArgumentException(json_encode(['password' => 'La contraseña debe tener al menos 12 caracteres.']));
        }

        $this->repo->update($id, [
            'password'             => password_hash($newPassword, PASSWORD_DEFAULT),
            'must_change_password' => false,
        ]);

        $this->audit->log(null, 'actualizar', 'user', $id, 'Contraseña restablecida para: ' . $existing['username']);

        return $this->repo->findById($id);
    }

    public function changePassword(int $id, string $currentPassword, string $newPassword): array
    {
        $user = $this->repo->findWithPasswordById($id);
        if (!$user) {
            throw new \RuntimeException('Usuario no encontrado.');
        }

        if (!password_verify($currentPassword, (string) ($user['password'] ?? ''))) {
            throw new \InvalidArgumentException(json_encode(['current_password' => 'La contraseña actual es incorrecta.']));
        }

        if (strlen($newPassword) < 12) {
            throw new \InvalidArgumentException(json_encode(['password' => 'La contraseña debe tener al menos 12 caracteres.']));
        }

        $this->repo->update($id, [
            'password'             => password_hash($newPassword, PASSWORD_DEFAULT),
            'must_change_password' => false,
        ]);

        $this->audit->log($id, 'actualizar', 'user', $id, 'Cambio de contraseña por el usuario: ' . $user['username']);

        return $this->repo->findById($id);
    }

    public function delete(int $id): void
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Usuario no encontrado.');
        }

        $this->repo->delete($id);

        $this->audit->log(null, 'eliminar', 'user', $id, 'Usuario eliminado: ' . ($existing['username'] ?? ''));
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
        if (empty($data['password']) || strlen($data['password']) < 12) {
            $errors['password'] = 'La contraseña debe tener al menos 12 caracteres.';
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
        if (array_key_exists('password', $data) && $data['password'] !== '' && strlen($data['password']) < 12) {
            $errors['password'] = 'La contraseña debe tener al menos 12 caracteres.';
        }
        if (array_key_exists('role_id', $data) && empty($data['role_id'])) {
            $errors['role_id'] = 'El rol es obligatorio.';
        }
        return $errors;
    }
}
