<?php

declare(strict_types=1);

namespace App\Repositories;

class NewsRepository
{
    public function findAll(int $limit = 20, int $offset = 0): array
    {
        return dbFetchAll(
            "SELECT n.id, n.title, n.subtitle, n.content, n.image, n.publication_date,
                    n.created_at, n.updated_at,
                    ns.name AS status,
                    u.username AS author,
                    t.name AS tag
             FROM news n
             JOIN news_status ns ON n.status_id = ns.id
             JOIN app_user u ON n.author_id = u.id
             LEFT JOIN tag t ON n.tag_id = t.id
             ORDER BY n.created_at DESC
             LIMIT :limit OFFSET :offset",
            ['limit' => $limit, 'offset' => $offset]
        );
    }

    public function findPublished(int $limit = 20, int $offset = 0): array
    {
        return dbFetchAll(
            "SELECT n.id, n.title, n.subtitle, n.content, n.image, n.publication_date,
                    n.created_at, n.updated_at,
                    ns.name AS status,
                    u.username AS author,
                    t.name AS tag
             FROM news n
             JOIN news_status ns ON n.status_id = ns.id
             JOIN app_user u ON n.author_id = u.id
             LEFT JOIN tag t ON n.tag_id = t.id
             WHERE ns.name = 'publicada'
             ORDER BY n.created_at DESC
             LIMIT :limit OFFSET :offset",
            ['limit' => $limit, 'offset' => $offset]
        );
    }

    public function findById(int $id): ?array
    {
        return dbFetchOne(
            "SELECT n.id, n.title, n.subtitle, n.content, n.image, n.publication_date,
                    n.created_at, n.updated_at,
                    ns.name AS status,
                    u.username AS author,
                    t.name AS tag
             FROM news n
             JOIN news_status ns ON n.status_id = ns.id
             JOIN app_user u ON n.author_id = u.id
             LEFT JOIN tag t ON n.tag_id = t.id
             WHERE n.id = :id",
            ['id' => $id]
        );
    }

    public function create(array $data): int
    {
        return dbInsert('news', $data);
    }

    public function update(int $id, array $data): int
    {
        return dbUpdate('news', $data, 'id = ?', [$id]);
    }

    public function delete(int $id): int
    {
        return dbDelete('news', 'id = :id', ['id' => $id]);
    }

    public function getStatusId(string $status): ?int
    {
        $row = dbFetchOne("SELECT id FROM news_status WHERE name = :name", ['name' => $status]);
        return $row ? (int) $row['id'] : null;
    }

    public function countAll(): int
    {
        $r = dbFetchOne("SELECT COUNT(*) AS t FROM news");
        return (int) ($r['t'] ?? 0);
    }

    public function countPublished(): int
    {
        $r = dbFetchOne(
            "SELECT COUNT(*) AS t FROM news n JOIN news_status ns ON n.status_id = ns.id WHERE ns.name = 'publicada'"
        );
        return (int) ($r['t'] ?? 0);
    }
}
