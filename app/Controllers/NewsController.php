<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\NewsService;

class NewsController
{
    private NewsService $service;

    public function __construct()
    {
        $this->service = new NewsService();
    }

    public function index(): void
    {
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 20)));
        $query   = trim((string) ($_GET['q'] ?? $_GET['search'] ?? ''));
        $tagId   = isset($_GET['tag_id']) && is_numeric($_GET['tag_id']) ? (int) $_GET['tag_id'] : (isset($_GET['tag']) && is_numeric($_GET['tag']) ? (int) $_GET['tag'] : null);
        $isAdmin = authCheck() && in_array(authUser()['role_name'] ?? '', ['superadmin', 'admin', 'editor'], true);

        $data = $isAdmin
            ? $this->service->getAll($page, $perPage, $query, $tagId)
            : $this->service->getPublished($page, $perPage, $query, $tagId);

        respSuccess($data);
    }

    public function show(int $id): void
    {
        $isAdmin = authCheck() && in_array(authUser()['role_name'] ?? '', ['superadmin', 'admin', 'editor'], true);

        $item = $isAdmin
            ? $this->service->getById($id)
            : $this->service->getPublishedById($id);

        if (!$item) {
            respNotFound();
            return;
        }

        respSuccess($item);
    }

    public function store(): void
    {
        if (!authCheck()) {
            respUnauthorized();
            return;
        }

        $data = getJsonInput();

        try {
            $item = $this->service->create($data, (int) authUser()['id']);
            respCreated($item);
        } catch (\InvalidArgumentException $e) {
            respUnprocessable(json_decode($e->getMessage(), true));
        } catch (\Exception $e) {
            respServerError();
        }
    }

    public function update(int $id): void
    {
        if (!authCheck()) {
            respUnauthorized();
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

    public function updateStatus(int $id): void
    {
        if (!authCheck()) {
            respUnauthorized();
            return;
        }

        $data = getJsonInput();
        $status = $data['status'] ?? '';

        if ($status === '') {
            respBadRequest(['status' => 'El campo status es obligatorio.']);
            return;
        }

        try {
            $item = $this->service->updateStatus($id, $status);
            respSuccess($item);
        } catch (\InvalidArgumentException $e) {
            respUnprocessable(json_decode($e->getMessage(), true));
        } catch (\RuntimeException $e) {
            respNotFound();
        } catch (\Exception $e) {
            respServerError();
        }
    }

    public function destroy(int $id): void
    {
        if (!authCheck()) {
            respUnauthorized();
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
}
