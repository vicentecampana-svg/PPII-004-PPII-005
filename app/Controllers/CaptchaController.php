<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CaptchaService;

class CaptchaController
{
    private CaptchaService $service;

    public function __construct()
    {
        $this->service = new CaptchaService();
    }

    public function show(): void
    {
        $captcha = $this->service->generate();

        header('Content-Type: image/svg+xml; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        echo $captcha['svg'];
        exit;
    }

    public function api(): void
    {
        $captcha = $this->service->generate();

        respSuccess([
            'svg' => $captcha['svg'],
        ]);
    }
}
