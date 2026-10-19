<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CreditMemberService;
use App\Services\CreditsService;
use App\Services\FormGuardService;
use App\Services\RateLimiterService;

/**
 * API de los integrantes del apartado Créditos.
 *
 * GET    /api/credits          → listado (sin correo; con correo si hay sesión de Super Admin)
 * GET    /api/credits/{id}     → detalle (misma política de correo que el listado)
 * POST   /api/credits          → crear integrante (Super Admin)
 * PUT    /api/credits/{id}     → editar nombre/cargo/correo (Super Admin)
 * DELETE /api/credits/{id}     → eliminar integrante (Super Admin)
 * POST   /api/credits/contact  → mensaje de contacto a un integrante (público)
 */
class CreditController
{
    private CreditMemberService $service;
    private CreditsService $creditsService;
    private FormGuardService $guard;
    private RateLimiterService $rateLimiter;

    public function __construct(
        ?CreditMemberService $service = null,
        ?CreditsService $creditsService = null,
        ?FormGuardService $guard = null,
        ?RateLimiterService $rateLimiter = null
    ) {
        $this->service = $service ?? new CreditMemberService();
        $this->creditsService = $creditsService ?? new CreditsService();
        $this->guard = $guard ?? new FormGuardService();
        $this->rateLimiter = $rateLimiter ?? new RateLimiterService();
    }

    /**
     * POST /api/credits/contact — recepción segura del formulario de contacto
     * de créditos, con límite por IP y correo (SEC-03).
     */
    public function contact(): void
    {
        $input = getJsonInput();
        if ($input === []) {
            $input = $_POST;
        }

        $ip = $this->rateLimiter->getClientIp();
        $email = trim((string) ($input['email'] ?? ''));

        try {
            $this->guard->assertAllowed('credits_contact', $ip, $email);
        } catch (\RuntimeException) {
            respError('TOO_MANY_REQUESTS', 'Demasiados envíos en poco tiempo. Inténtalo más tarde.', 429);
            return;
        }

        $this->guard->record('credits_contact', $ip, $email);

        try {
            $result = $this->creditsService->sendContactMessage($input);
            respSuccess($result);
        } catch (\InvalidArgumentException) {
            respBadRequest('No se pudo procesar el mensaje de contacto. Verifica los datos ingresados.');
        } catch (\Throwable) {
            respServerError('Error al procesar el mensaje de contacto.');
        }
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
        } catch (\App\Exceptions\ValidationException $e) {
            respUnprocessable($e->getErrors());
        } catch (\InvalidArgumentException) {
            respUnprocessable(['message' => 'Datos inválidos. Verifica la información enviada.']);
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
        } catch (\App\Exceptions\ValidationException $e) {
            respUnprocessable($e->getErrors());
        } catch (\InvalidArgumentException) {
            respUnprocessable(['message' => 'Datos inválidos. Verifica la información enviada.']);
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
