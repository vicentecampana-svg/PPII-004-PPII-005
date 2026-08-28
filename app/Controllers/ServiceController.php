<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ServiceService;

class ServiceController
{
    private ServiceService $service;

    public function __construct()
    {
        $this->service = new ServiceService();
    }

    public function index(): void
    {
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 20)));
        $isAdmin = authCheck() && in_array(authUser()['role_name'] ?? '', ['superadmin', 'admin', 'editor'], true);

        $data = $this->service->getAll($page, $perPage, $isAdmin);
        respSuccess($data);
    }

    public function show(int $id): void
    {
        $item = $this->service->getById($id);

        if (!$item) {
            respNotFound();
            return;
        }

        if (!$item['active'] && !(authCheck() && in_array(authUser()['role_name'] ?? '', ['superadmin', 'admin', 'editor'], true))) {
            respNotFound();
            return;
        }

        respSuccess($item);
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

    public function update(int $id): void
    {
        $data = getJsonInput();

        try {
            $item = $this->service->update($id, $data);
            respSuccess($item);
        } catch (\RuntimeException $e) {
            respNotFound();
        } catch (\InvalidArgumentException $e) {
            respUnprocessable(json_decode($e->getMessage(), true));
        } catch (\Exception $e) {
            respServerError();
        }
    }

    public function updateStatus(int $id): void
    {
        $data = getJsonInput();
        $active = $data['active'] ?? null;

        if ($active === null) {
            respBadRequest(['active' => 'El campo active es obligatorio.']);
            return;
        }

        try {
            $item = $this->service->setStatus($id, (bool) $active);
            respSuccess($item);
        } catch (\RuntimeException $e) {
            respNotFound();
        } catch (\Exception $e) {
            respServerError();
        }
    }

    public function destroy(int $id): void
    {
        try {
            $this->service->delete($id);
            respNoContent();
        } catch (\RuntimeException $e) {
            respNotFound();
        } catch (\Exception $e) {
            respServerError();
        }
    }
}
