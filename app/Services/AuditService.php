<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditRepository;

class AuditService
{
    private AuditRepository $repo;

    public function __construct(?AuditRepository $repo = null)
    {
        $this->repo = $repo ?? new AuditRepository();
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

    public function log(?int $userId, string $action, string $entity, int $entityId, string $details = ''): int
    {
        if ($userId === null) {
            $user = authUser();
            $userId = $user ? (int) $user['id'] : 1;
        }

        return $this->repo->log($userId, $action, $entity, $entityId, $details);
    }
}
