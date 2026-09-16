<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CreditMemberService;

/**
 * API de los integrantes del apartado Créditos.
 *
 * GET    /api/credits        → listado (sin correo; con correo si hay sesión de Super Admin)
 * GET    /api/credits/{id}   → detalle (misma política de correo que el listado)
 * POST   /api/credits        → crear integrante (Super Admin)
 * PUT    /api/credits/{id}   → editar nombre/cargo/correo (Super Admin)
 * DELETE /api/credits/{id}   → eliminar integrante (Super Admin)
 */
class CreditController
{
    private CreditMemberService $service;

    public function __construct(?CreditMemberService $service = null)
    {
        $this->service = $service ?? new CreditMemberService();
    }

    public function index(): void
    {
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 20)));

        respSuccess($this->service->getAll($page, $perPage, $this->isSuperAdmin()));
    }

    public function show(int $id): void
    {
        $item = $this->service->getById($id, $this->isSuperAdmin());

        if (!$item) {
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
        } catch (\Throwable) {
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
        } catch (\Throwable) {
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
        } catch (\Throwable) {
            respServerError();
        }
    }

    private function isSuperAdmin(): bool
    {
        $user = authUser();
        return ($user['role_name'] ?? '') === 'superadmin';
    }
}
