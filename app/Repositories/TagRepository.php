<?php

declare(strict_types=1);

namespace App\Repositories;

class TagRepository
{
    public function findAll(): array
    {
        return dbFetchAll("SELECT id, name FROM tag ORDER BY id");
    }

    public function findById(int $id): ?array
    {
        return dbFetchOne(
            "SELECT id, name FROM tag WHERE id = :id",
            ['id' => $id]
        );
    }

    public function create(array $data): int
    {
        return dbInsert('tag', $data);
    }

    public function delete(int $id): int
    {
        return dbDelete('tag', 'id = :id', ['id' => $id]);
    }
}
