<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CreditsService;

/**
 * Controlador API para la recepción segura de formularios de contacto de créditos.
 */
final class CreditsApiController
{
    private CreditsService $creditsService;

    public function __construct()
    {
        $this->creditsService = new CreditsService();
    }

    public function contact(): void
    {
        $input = getJsonInput();
        if ($input === []) {
            $input = $_POST;
        }

        try {
            $result = $this->creditsService->sendContactMessage($input);
            respSuccess($result);
        } catch (\InvalidArgumentException $e) {
            respBadRequest($e->getMessage());
        } catch (\Throwable $e) {
            respServerError('Error al procesar el mensaje de contacto.');
        }
    }
}
