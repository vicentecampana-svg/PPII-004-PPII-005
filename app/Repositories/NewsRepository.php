<?php

declare(strict_types=1);

namespace App\Repositories;

class NewsRepository
{
    public function findAll(int $limit = 20, int $offset = 0, string $query = '', ?int $tagId = null): array
    {
        [$whereSql, $params] = $this->buildWhere(null, $query, $tagId);
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        $sql = "SELECT n.id, n.author_id, n.editor_id, n.status_id, n.title, n.subtitle, n.content,
                       n.image, n.publication_date, n.created_at, n.updated_at,
                       ns.name AS status,
                       u.username AS author,
                       STRING_AGG(DISTINCT t.name, ', ') AS tag,
                       COALESCE(
                           json_agg(DISTINCT jsonb_build_object('id', t.id, 'name', t.name)) FILTER (WHERE t.id IS NOT NULL),
                           '[]'::json
                       ) AS tags
                FROM news n
                JOIN news_status ns ON n.status_id = ns.id
                JOIN app_user u ON n.author_id = u.id
                LEFT JOIN news_tag nt ON n.id = nt.news_id
                LEFT JOIN tag t ON (nt.tag_id = t.id OR n.tag_id = t.id)
                {$whereSql}
                GROUP BY n.id, ns.name, u.username
                ORDER BY n.created_at DESC
                LIMIT :limit OFFSET :offset";

        return $this->formatRows(dbFetchAll($sql, $params));
    }

    public function findPublished(int $limit = 20, int $offset = 0, string $query = '', ?int $tagId = null): array
    {
        [$whereSql, $params] = $this->buildWhere('publicada', $query, $tagId);
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        $sql = "SELECT n.id, n.author_id, n.editor_id, n.status_id, n.title, n.subtitle, n.content,
                       n.image, n.publication_date, n.created_at, n.updated_at,
                       ns.name AS status,
                       u.username AS author,
                       STRING_AGG(DISTINCT t.name, ', ') AS tag,
                       COALESCE(
                           json_agg(DISTINCT jsonb_build_object('id', t.id, 'name', t.name)) FILTER (WHERE t.id IS NOT NULL),
                           '[]'::json
                       ) AS tags
                FROM news n
                JOIN news_status ns ON n.status_id = ns.id
                JOIN app_user u ON n.author_id = u.id
                LEFT JOIN news_tag nt ON n.id = nt.news_id
                LEFT JOIN tag t ON (nt.tag_id = t.id OR n.tag_id = t.id)
                {$whereSql}
                GROUP BY n.id, ns.name, u.username
                ORDER BY n.created_at DESC
                LIMIT :limit OFFSET :offset";

        return $this->formatRows(dbFetchAll($sql, $params));
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT n.id, n.author_id, n.editor_id, n.status_id, n.title, n.subtitle, n.content,
                       n.image, n.publication_date, n.created_at, n.updated_at,
                       ns.name AS status,
                       u.username AS author,
                       STRING_AGG(DISTINCT t.name, ', ') AS tag,
                       COALESCE(
                           json_agg(DISTINCT jsonb_build_object('id', t.id, 'name', t.name)) FILTER (WHERE t.id IS NOT NULL),
                           '[]'::json
                       ) AS tags
                FROM news n
                JOIN news_status ns ON n.status_id = ns.id
                JOIN app_user u ON n.author_id = u.id
                LEFT JOIN news_tag nt ON n.id = nt.news_id
                LEFT JOIN tag t ON (nt.tag_id = t.id OR n.tag_id = t.id)
                WHERE n.id = :id
                GROUP BY n.id, ns.name, u.username";

        $row = dbFetchOne($sql, ['id' => $id]);
        if (!$row) {
            return null;
        }

        return $this->formatRow($row);
    }

    public function search(string $query, ?int $tagId = null, bool $onlyPublished = true, int $limit = 20, int $offset = 0): array
    {
        return $onlyPublished
            ? $this->findPublished($limit, $offset, $query, $tagId)
            : $this->findAll($limit, $offset, $query, $tagId);
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

    public function syncTags(int $newsId, array $tagIds): void
    {
        dbDelete('news_tag', 'news_id = :news_id', ['news_id' => $newsId]);
        foreach ($tagIds as $tagId) {
            $tId = (int) $tagId;
            if ($tId > 0) {
                dbQuery(
                    "INSERT INTO news_tag (news_id, tag_id) VALUES (:news_id, :tag_id) ON CONFLICT DO NOTHING",
                    ['news_id' => $newsId, 'tag_id' => $tId]
                );
            }
        }
    }

    public function getTags(int $newsId): array
    {
        return dbFetchAll(
            "SELECT t.id, t.name
             FROM tag t
             JOIN news_tag nt ON t.id = nt.tag_id
             WHERE nt.news_id = :news_id
             ORDER BY t.name",
            ['news_id' => $newsId]
        );
    }

    public function getStatusId(string $status): ?int
    {
        $row = dbFetchOne("SELECT id FROM news_status WHERE name = :name", ['name' => $status]);
        return $row ? (int) $row['id'] : null;
    }

    public function countAll(string $query = '', ?int $tagId = null): int
    {
        [$whereSql, $params] = $this->buildWhere(null, $query, $tagId);
        $sql = "SELECT COUNT(DISTINCT n.id) AS t
                FROM news n
                JOIN news_status ns ON n.status_id = ns.id
                LEFT JOIN news_tag nt ON n.id = nt.news_id
                {$whereSql}";

        $r = dbFetchOne($sql, $params);
        return (int) ($r['t'] ?? 0);
    }

    public function countPublished(string $query = '', ?int $tagId = null): int
    {
        [$whereSql, $params] = $this->buildWhere('publicada', $query, $tagId);
        $sql = "SELECT COUNT(DISTINCT n.id) AS t
                FROM news n
                JOIN news_status ns ON n.status_id = ns.id
                LEFT JOIN news_tag nt ON n.id = nt.news_id
                {$whereSql}";

        $r = dbFetchOne($sql, $params);
        return (int) ($r['t'] ?? 0);
    }

    private function buildWhere(?string $status, string $query, ?int $tagId): array
    {
        $conditions = [];
        $params = [];

        if ($status !== null) {
            $conditions[] = "ns.name = :status";
            $params['status'] = $status;
        }

        if ($query !== '') {
            $conditions[] = "(n.title ILIKE :q OR n.subtitle ILIKE :q OR n.content ILIKE :q)";
            $params['q'] = '%' . $query . '%';
        }

        if ($tagId !== null) {
            $conditions[] = "(EXISTS (SELECT 1 FROM news_tag nt2 WHERE nt2.news_id = n.id AND nt2.tag_id = :tag_id) OR n.tag_id = :tag_id)";
            $params['tag_id'] = $tagId;
        }

        $whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        return [$whereSql, $params];
    }

    private function formatRows(array $rows): array
    {
        return array_map([$this, 'formatRow'], $rows);
    }

    private function formatRow(array $row): array
    {
        if (isset($row['tags']) && is_string($row['tags'])) {
            $row['tags'] = json_decode($row['tags'], true) ?: [];
        }
        return $row;
    }
}
