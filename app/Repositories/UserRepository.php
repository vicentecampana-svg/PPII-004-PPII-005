<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Los usuarios eliminados (deleted_at no nulo) se conservan en la tabla, pero
 * findAll, findById, findWithPasswordById y count los excluyen. findByEmail y
 * findByUsername sí los incluyen, para bloquear el login y la reutilización.
 */
class UserRepository
{
    public function findAll(int $limit = 20, int $offset = 0): array
    {
        return dbFetchAll(
            "SELECT u.id, u.username, u.email, u.active, u.must_change_password,
                    r.id AS role_id, r.name AS role_name
             FROM app_user u
             JOIN role r ON u.role_id = r.id
             WHERE u.deleted_at IS NULL
             ORDER BY u.id
             LIMIT :limit OFFSET :offset",
            ['limit' => $limit, 'offset' => $offset]
        );
    }

    public function findById(int $id): ?array
    {
        return dbFetchOne(
            "SELECT u.id, u.username, u.email, u.active, u.must_change_password,
                    r.id AS role_id, r.name AS role_name
             FROM app_user u
             JOIN role r ON u.role_id = r.id
             WHERE u.id = :id AND u.deleted_at IS NULL",
            ['id' => $id]
        );
    }

    public function findWithPasswordById(int $id): ?array
    {
        return dbFetchOne(
            "SELECT u.id, u.username, u.email, u.password, u.active, u.must_change_password,
                    r.id AS role_id, r.name AS role_name
             FROM app_user u
             JOIN role r ON u.role_id = r.id
             WHERE u.id = :id AND u.deleted_at IS NULL",
            ['id' => $id]
        );
    }

    public function findByEmail(string $email): ?array
    {
        return dbFetchOne(
            "SELECT u.id, u.username, u.email, u.password, u.active, u.must_change_password,
                    u.deleted_at, u.role_id, r.name AS role_name
             FROM app_user u
             JOIN role r ON u.role_id = r.id
             WHERE u.email = :email",
            ['email' => $email]
        );
    }

    public function findByUsername(string $username): ?array
    {
        return dbFetchOne(
            "SELECT id, username, deleted_at FROM app_user WHERE username = :username",
            ['username' => $username]
        );
    }

    /** Sesión vigente solo si el usuario sigue activo y no fue eliminado. */
    public function isActive(int $id): bool
    {
        return dbFetchOne(
            "SELECT 1 FROM app_user WHERE id = :id AND active = true AND deleted_at IS NULL",
            ['id' => $id]
        ) !== null;
    }

    public function create(array $data): int
    {
        return dbInsert('app_user', $data);
    }

    public function update(int $id, array $data): int
    {
        return dbUpdate('app_user', $data, 'id = ?', [$id]);
    }

    /**
     * Eliminación lógica: la fila se conserva (auditoría, noticias, email único)
     * y queda desactivada.
     */
    public function delete(int $id): int
    {
        return dbQuery(
            "UPDATE app_user SET active = false, deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL",
            ['id' => $id]
        )->rowCount();
    }

    public function count(): int
    {
        $r = dbFetchOne("SELECT COUNT(*) AS t FROM app_user WHERE deleted_at IS NULL");
        return (int) ($r['t'] ?? 0);
    }

    public function findAllRoles(): array
    {
        return dbFetchAll("SELECT id, name, description FROM role ORDER BY id");
    }

    public function findRoleById(int $id): ?array
    {
        return dbFetchOne(
            "SELECT id, name, description FROM role WHERE id = :id",
            ['id' => $id]
        );
    }
}
