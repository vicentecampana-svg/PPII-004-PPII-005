<?php

declare(strict_types=1);

namespace App\Repositories;

class StaffRepository
{
    public function findAll(int $limit = 20, int $offset = 0): array
    {
        return dbFetchAll(
            "SELECT id, name, \"position\", photo, description
             FROM staff_member
             ORDER BY id DESC
             LIMIT :limit OFFSET :offset",
            ['limit' => $limit, 'offset' => $offset]
        );
    }

    public function findById(int $id): ?array
    {
        return dbFetchOne(
            "SELECT id, name, \"position\", photo, description
             FROM staff_member WHERE id = :id",
            ['id' => $id]
        );
    }

    public function create(array $data): int
    {
        return dbInsert('staff_member', $data);
    }

    public function update(int $id, array $data): int
    {
        return dbUpdate('staff_member', $data, 'id = ?', [$id]);
    }

    public function delete(int $id): int
    {
        return dbDelete('staff_member', 'id = :id', ['id' => $id]);
    }

    public function count(): int
    {
        $r = dbFetchOne("SELECT COUNT(*) AS t FROM staff_member");
        return (int) ($r['t'] ?? 0);
    }
}
