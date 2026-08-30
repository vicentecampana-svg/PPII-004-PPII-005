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
        $user = authUser();
        $role = $user['role_name'] ?? '';
        $isAdminOrEditor = authCheck() && in_array($role, ['superadmin', 'admin', 'editor'], true);

        $item = $this->service->getById($id);

        if (!$item) {
            respNotFound();
            return;
        }

        // Si es pública, cualquiera puede verla
        if ($item['status'] === 'publicada') {
            respSuccess($item);
            return;
        }

        // Si es admin/editor, puede verla en cualquier estado
        if ($isAdminOrEditor) {
            respSuccess($item);
            return;
        }

        // Si es redactor, solo puede verla si es el autor
        if ($role === 'redactor' && (int) ($item['author_id'] ?? 0) === (int) ($user['id'] ?? 0)) {
            respSuccess($item);
            return;
        }

        respNotFound();
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

        $user = authUser();
        $isRedactor = ($user['role_name'] ?? '') === 'redactor';

        $existing = $this->service->getById($id);
        if (!$existing) {
            respNotFound();
            return;
        }

        if ($isRedactor) {
            if ((int) ($existing['author_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
                respForbidden('No tienes permiso para modificar noticias de otros redactores.');
                return;
            }
            if (($existing['status'] ?? '') !== 'pendiente') {
                respForbidden('Solo puedes modificar noticias en estado pendiente.');
                return;
            }
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

        $user = authUser();
        $isRedactor = ($user['role_name'] ?? '') === 'redactor';

        $existing = $this->service->getById($id);
        if (!$existing) {
            respNotFound();
            return;
        }

        if ($isRedactor) {
            if ((int) ($existing['author_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
                respForbidden('No tienes permiso para eliminar noticias de otros redactores.');
                return;
            }
            if (($existing['status'] ?? '') !== 'pendiente') {
                respForbidden('Solo puedes eliminar noticias en estado pendiente.');
                return;
            }
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
