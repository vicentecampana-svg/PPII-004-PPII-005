<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\TagService;

class TagController
{
    private TagService $service;

    public function __construct()
    {
        $this->service = new TagService();
    }

    public function index(): void
    {
        $tags = $this->service->getAll();
        respSuccess($tags);
    }

    public function store(): void
    {
        if (!authCheck()) {
            respUnauthorized();
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
