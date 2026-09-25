<?php

declare(strict_types=1);

namespace App\Repositories;

class AuditRepository
{
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        return dbFetchAll(
            "SELECT a.id, a.action, a.entity, a.entity_id, a.details, a.created_at,
                    u.username AS user_name
             FROM audit_log a
             LEFT JOIN app_user u ON a.user_id = u.id
             ORDER BY a.created_at DESC
             LIMIT :limit OFFSET :offset",
            ['limit' => $limit, 'offset' => $offset]
        );
    }

    public function findByEntity(string $entity, int $entityId): array
    {
        return dbFetchAll(
            "SELECT a.id, a.action, a.details, a.created_at,
                    u.username AS user_name
             FROM audit_log a
             LEFT JOIN app_user u ON a.user_id = u.id
             WHERE a.entity = :entity AND a.entity_id = :entity_id
             ORDER BY a.created_at DESC",
            ['entity' => $entity, 'entity_id' => $entityId]
        );
    }

    public function log(int $userId, string $action, string $entity, int $entityId, string $details = ''): int
    {
        return dbInsert('audit_log', [
            'user_id'    => $userId,
            'action'     => $action,
            'entity'     => $entity,
            'entity_id'  => $entityId,
            'details'    => $details,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function count(): int
    {
        $r = dbFetchOne("SELECT COUNT(*) AS t FROM audit_log");
        return (int) ($r['t'] ?? 0);
    }
}
