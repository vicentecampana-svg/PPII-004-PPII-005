<?php

declare(strict_types=1);

namespace App\Repositories;

class QueryRepository
{
    public function findAll(int $limit = 20, int $offset = 0): array
    {
        return dbFetchAll(
            "SELECT id, name, email, phone, subject, message, sent_at, status
             FROM contact_request
             ORDER BY sent_at DESC
             LIMIT :limit OFFSET :offset",
            ['limit' => $limit, 'offset' => $offset]
        );
    }

    public function findById(int $id): ?array
    {
        return dbFetchOne(
            "SELECT id, name, email, phone, subject, message, sent_at, status
             FROM contact_request WHERE id = :id",
            ['id' => $id]
        );
    }

    public function create(array $data): int
    {
        return dbInsert('contact_request', $data);
    }

    public function updateStatus(int $id, string $status): int
    {
        return dbUpdate('contact_request', ['status' => $status], 'id = ?', [$id]);
    }

    public function count(): int
    {
        $r = dbFetchOne("SELECT COUNT(*) AS t FROM contact_request");
        return (int) ($r['t'] ?? 0);
    }

    public function countPending(): int
    {
        $r = dbFetchOne("SELECT COUNT(*) AS t FROM contact_request WHERE status = 'pendiente'");
        return (int) ($r['t'] ?? 0);
    }

    /** SEC-09: consultas aún no incluidas en un aviso al encargado, de la más antigua a la más nueva. */
    public function findUnnotified(int $limit): array
    {
        return dbFetchAll(
            "SELECT id, name, email, phone, subject, message, sent_at, status
             FROM contact_request
             WHERE notified_at IS NULL
             ORDER BY id
             LIMIT :limit",
            ['limit' => $limit]
        );
    }

    public function countUnnotified(): int
    {
        $r = dbFetchOne("SELECT COUNT(*) AS t FROM contact_request WHERE notified_at IS NULL");
        return (int) ($r['t'] ?? 0);
    }

    /** Marca como avisadas las consultas pendientes con id hasta $maxId inclusive. */
    public function markNotifiedUpTo(int $maxId): int
    {
        return dbQuery(
            "UPDATE contact_request SET notified_at = NOW() WHERE notified_at IS NULL AND id <= :max_id",
            ['max_id' => $maxId]
        )->rowCount();
    }
}
