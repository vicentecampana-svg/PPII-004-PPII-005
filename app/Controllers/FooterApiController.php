<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FooterService;

class FooterApiController
{
    private FooterService $service;

    public function __construct()
    {
        $this->service = new FooterService();
    }

    public function show(): void
    {
        $data = $this->service->getAll();
        respSuccess($data);
    }

    public function update(): void
    {
        if (!$this->isAdminOrEditor()) {
            respForbidden();
            return;
        }

        $data = getJsonInput();

        try {
            $item = $this->service->updateInfo($data);
            respSuccess($item);
        } catch (\RuntimeException $e) {
            respNotFound();
        } catch (\Exception $e) {
            respServerError();
        }
    }

    private function isAdminOrEditor(): bool
    {
        if (!authCheck()) {
            return false;
        }
        return in_array(authUser()['role_name'] ?? '', ['superadmin', 'admin', 'editor'], true);
    }
}
