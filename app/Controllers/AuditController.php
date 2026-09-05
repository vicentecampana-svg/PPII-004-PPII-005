<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuditService;

class AuditController
{
    private AuditService $service;

    public function __construct()
    {
        $this->service = new AuditService();
    }

    public function index(): void
    {
        if (!$this->isSuperAdmin()) {
            respForbidden();
            return;
        }

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 50)));

        $data = $this->service->getAll($page, $perPage);
        respSuccess($data);
    }

    private function isSuperAdmin(): bool
    {
        if (!authCheck()) return false;
        return (authUser()['role_name'] ?? '') === 'superadmin';
    }
}
