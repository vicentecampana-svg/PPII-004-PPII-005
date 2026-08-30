<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\QueryService;

class QueryController
{
    private QueryService $service;

    public function __construct()
    {
        $this->service = new QueryService();
    }

    public function store(): void
    {
        $data = getJsonInput();

        try {
            $item = $this->service->create($data);
            respCreated($item);
        } catch (\InvalidArgumentException $e) {
            respUnprocessable(json_decode($e->getMessage(), true));
        } catch (\Exception $e) {
            respServerError();
        }
    }

    public function index(): void
    {
        if (!$this->isAdminOrEditor()) {
            respForbidden();
            return;
        }

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 20)));

        $data = $this->service->getAll($page, $perPage);
        respSuccess($data);
    }

    public function show(int $id): void
    {
        if (!$this->isAdminOrEditor()) {
            respForbidden();
            return;
        }

        $item = $this->service->getById($id);

        if (!$item) {
            respNotFound();
            return;
        }

        respSuccess($item);
    }

    public function updateStatus(int $id): void
    {
        if (!$this->isAdminOrEditor()) {
            respForbidden();
            return;
        }

        $data = getJsonInput();
        $status = $data['status'] ?? '';

        if ($status === '') {
            respBadRequest(['status' => 'El campo status es obligatorio.']);
            return;
        }

        try {
            $item = $this->service->setStatus($id, $status);
            respSuccess($item);
        } catch (\InvalidArgumentException $e) {
            respUnprocessable(json_decode($e->getMessage(), true));
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
