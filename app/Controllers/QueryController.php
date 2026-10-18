<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FormGuardService;
use App\Services\QueryService;
use App\Services\RateLimiterService;

class QueryController
{
    private QueryService $service;
    private FormGuardService $guard;
    private RateLimiterService $rateLimiter;

    public function __construct()
    {
        $this->service = new QueryService();
        $this->guard = new FormGuardService();
        $this->rateLimiter = new RateLimiterService();
    }

    public function store(): void
    {
        $data = getJsonInput();

        // SEC-03: límite por IP y correo para evitar spam de consultas.
        $ip = $this->rateLimiter->getClientIp();
        $email = trim((string) ($data['email'] ?? ''));

        try {
            $this->guard->assertAllowed('api_queries', $ip, $email);
        } catch (\RuntimeException) {
            respError('TOO_MANY_REQUESTS', 'Demasiados envíos en poco tiempo. Inténtalo más tarde.', 429);
            return;
        }

        $this->guard->record('api_queries', $ip, $email);

        try {
            $item = $this->service->create($data);
            respCreated($item);
        } catch (\App\Exceptions\ValidationException $e) {
            respUnprocessable($e->getErrors());
        } catch (\InvalidArgumentException) {
            respUnprocessable(['message' => 'Datos inválidos. Verifica la información enviada.']);
        } catch (\Exception $e) {
            respServerError();
        }
    }

    public function index(): void
    {
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 20)));

        $data = $this->service->getAll($page, $perPage);
        respSuccess($data);
    }

    public function show(int $id): void
    {
        $item = $this->service->getById($id);

        if (!$item) {
            respNotFound();
            return;
        }

        respSuccess($item);
    }

    public function updateStatus(int $id): void
    {
        $data = getJsonInput();
        $status = $data['status'] ?? '';

        if ($status === '') {
            respBadRequest(['status' => 'El campo status es obligatorio.']);
            return;
        }

        try {
            $item = $this->service->setStatus($id, $status);
            respSuccess($item);
        } catch (\App\Exceptions\ValidationException $e) {
            respUnprocessable($e->getErrors());
        } catch (\InvalidArgumentException) {
            respUnprocessable(['message' => 'Datos inválidos. Verifica la información enviada.']);
        } catch (\RuntimeException $e) {
            respNotFound();
        } catch (\Exception $e) {
            respServerError();
        }
    }
}
