<?php

declare(strict_types=1);

namespace App\Repositories;

class ProjectRepository
{
    public function findAllActive(int $limit = 20, int $offset = 0): array
    {
        return dbFetchAll(
            "SELECT id, name, description, image, link, active
             FROM project WHERE active = true
             ORDER BY id DESC
             LIMIT :limit OFFSET :offset",
            ['limit' => $limit, 'offset' => $offset]
        );
    }

    public function findAll(int $limit = 20, int $offset = 0): array
    {
        return dbFetchAll(
            "SELECT id, name, description, image, link, active
             FROM project
             ORDER BY id DESC
             LIMIT :limit OFFSET :offset",
            ['limit' => $limit, 'offset' => $offset]
        );
    }

    public function findById(int $id): ?array
    {
        return dbFetchOne(
            "SELECT id, name, description, image, link, active
             FROM project WHERE id = :id",
            ['id' => $id]
        );
    }

    public function create(array $data): int
    {
        return dbInsert('project', $data);
    }

    public function update(int $id, array $data): int
    {
        return dbUpdate('project', $data, 'id = :id', ['id' => $id]);
    }

    public function delete(int $id): int
    {
        return dbDelete('project', 'id = :id', ['id' => $id]);
    }

    public function countActive(): int
    {
        $r = dbFetchOne("SELECT COUNT(*) AS t FROM project WHERE active = true");
        return (int) ($r['t'] ?? 0);
    }

    public function countAll(): int
    {
        $r = dbFetchOne("SELECT COUNT(*) AS t FROM project");
        return (int) ($r['t'] ?? 0);
    }
}
