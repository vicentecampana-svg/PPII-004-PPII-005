<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UserService;

class UserController
{
    private UserService $service;

    public function __construct()
    {
        $this->service = new UserService();
    }

    public function index(): void
    {
        if (!$this->isSuperAdmin()) {
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
        if (!$this->isSuperAdmin()) {
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

    public function store(): void
    {
        if (!$this->isSuperAdmin()) {
            respForbidden();
            return;
        }

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
        if (!$this->isSuperAdmin()) {
            respForbidden();
            return;
        }

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

    public function destroy(int $id): void
    {
        if (!$this->isSuperAdmin()) {
            respForbidden();
            return;
        }

        try {
            $this->service->delete($id);
            respNoContent();
        } catch (\RuntimeException $e) {
            respNotFound();
        } catch (\Exception $e) {
            respServerError();
        }
    }

    public function roles(): void
    {
        if (!$this->isSuperAdmin()) {
            respForbidden();
            return;
        }

        $roles = $this->service->getRoles();
        respSuccess($roles);
    }

    private function isSuperAdmin(): bool
    {
        if (!authCheck()) return false;
        return (authUser()['role_name'] ?? '') === 'superadmin';
    }
}
