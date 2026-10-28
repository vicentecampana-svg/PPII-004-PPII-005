<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
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

    /**
     * SEC-12: costo fijo para todos los hashes. Así la verificación tarda lo
     * mismo para cualquier cuenta, sin depender de la versión de PHP (el
     * costo por defecto es 10 en PHP 8.2 y 12 desde PHP 8.4).
     */
    public const HASH_OPTIONS = ['cost' => 12];

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, self::HASH_OPTIONS);
    }

    /**
     * SEC-12: verifica una contraseña contra el hash guardado sin revelar,
     * por el tiempo de respuesta, si la cuenta existe. Sin hash (correo no
     * registrado) se calcula igual un hash con el mismo costo, que tarda lo
     * mismo que la verificación de una contraseña real.
     */
    public static function verifyPassword(string $password, ?string $hash): bool
    {
        if ($hash === null || $hash === '') {
            self::hashPassword($password);
            return false;
        }

        return password_verify($password, $hash);
    }

    /**
     * Tras un inicio de sesión correcto, actualiza el hash si se generó con
     * otro costo, para que todas las cuentas tarden lo mismo en verificarse.
     */
    public function rehashIfNeeded(int $id, string $password, string $hash): void
    {
        if (password_needs_rehash($hash, PASSWORD_BCRYPT, self::HASH_OPTIONS)) {
            $this->repo->update($id, ['password' => self::hashPassword($password)]);
        }
    }

    public function getByEmail(string $email): ?array
    {
        return $this->repo->findByEmail($email);
    }

    public function create(array $data): array
    {
        $errors = $this->validateCreate($data);
        if ($errors) {
            throw new ValidationException($errors);
        }

        // Los usuarios eliminados conservan su email y username: no se pueden reutilizar.
        $existing = $this->repo->findByEmail($data['email']);
        if ($existing) {
            $msg = !empty($existing['deleted_at'])
                ? 'El email pertenece a una cuenta eliminada y no puede volver a registrarse.'
                : 'El email ya está registrado.';
            throw new ValidationException(['email' => $msg]);
        }

        $existingUsername = $this->repo->findByUsername($data['username']);
        if ($existingUsername) {
            $msg = !empty($existingUsername['deleted_at'])
                ? 'El nombre de usuario pertenece a una cuenta eliminada y no puede volver a usarse.'
                : 'El nombre de usuario ya está registrado.';
            throw new ValidationException(['username' => $msg]);
        }

        $id = $this->repo->create([
            'username'             => $data['username'],
            'email'                => $data['email'],
            'password'             => self::hashPassword($data['password']),
            'role_id'              => (int) $data['role_id'],
            'active'               => $data['active'] ?? true,
            'must_change_password' => $data['must_change_password'] ?? true,
        ]);

        $this->audit->log(null, 'crear', 'user', $id, 'Usuario creado: ' . $data['username']);

        return $this->repo->findById($id);
    }

    /**
     * @param int|null $actorId Autor para la auditoría; null = usuario de la sesión.
     */
    public function update(int $id, array $data, ?int $actorId = null): array
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Usuario no encontrado.');
        }

        $errors = $this->validateUpdate($data);
        if ($errors) {
            throw new ValidationException($errors);
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
            $fields['password'] = self::hashPassword($data['password']);
            // RF-AUT-003: quien define una contraseña inicial o la restablece
            // (panel o API) obliga al usuario a cambiarla en el próximo acceso.
            // El cambio propio (PasswordController) envía el flag explícito en false.
            if (!array_key_exists('must_change_password', $data)) {
                $fields['must_change_password'] = true;
            }
        }

        if ($fields) {
            $this->repo->update($id, $fields);
        }

        $this->audit->log($actorId, 'actualizar', 'user', $id, 'Usuario actualizado: ' . ($data['username'] ?? $existing['username']));

        return $this->repo->findById($id);
    }

    public function resetPassword(int $id, string $newPassword): array
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Usuario no encontrado.');
        }

        $policyErrors = PasswordPolicy::errors($newPassword);
        if ($policyErrors) {
            throw new ValidationException(['password' => implode(' ', $policyErrors)]);
        }

        // RF-AUT-003: una contraseña restablecida por el Super Usuario obliga
        // a cambiarla en el próximo inicio de sesión.
        $this->repo->update($id, [
            'password'             => self::hashPassword($newPassword),
            'must_change_password' => true,
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
            throw new ValidationException(['current_password' => 'La contraseña actual es incorrecta.']);
        }

        $policyErrors = PasswordPolicy::errors($newPassword);
        if ($policyErrors) {
            throw new ValidationException(['password' => implode(' ', $policyErrors)]);
        }

        $this->repo->update($id, [
            'password'             => self::hashPassword($newPassword),
            'must_change_password' => false,
        ]);

        $this->audit->log($id, 'actualizar', 'user', $id, 'Cambio de contraseña por el usuario: ' . $user['username']);

        return $this->repo->findById($id);
    }

    /** Eliminación lógica: ver UserRepository::delete(). */
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
        if (empty($data['password'])) {
            $errors['password'] = 'La contraseña es obligatoria.';
        } else {
            $policyErrors = PasswordPolicy::errors((string) $data['password']);
            if ($policyErrors) {
                $errors['password'] = implode(' ', $policyErrors);
            }
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
        if (array_key_exists('password', $data) && $data['password'] !== '') {
            $policyErrors = PasswordPolicy::errors((string) $data['password']);
            if ($policyErrors) {
                $errors['password'] = implode(' ', $policyErrors);
            }
        }
        if (array_key_exists('role_id', $data) && empty($data['role_id'])) {
            $errors['role_id'] = 'El rol es obligatorio.';
        }
        return $errors;
    }
}
