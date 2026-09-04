<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\MediaService;

class MediaController
{
    private MediaService $mediaService;

    public function __construct(?MediaService $mediaService = null)
    {
        $this->mediaService = $mediaService ?? new MediaService();
    }

    /**
     * Endpoint API para subir un archivo de imagen.
     * POST /api/media/upload o POST /api/upload
     */
    public function upload(): void
    {
        if (!authCheck()) {
            respUnauthorized('Debes iniciar sesión para subir archivos.');
            return;
        }

        // Detectar archivo en $_FILES
        $file = $_FILES['file'] ?? $_FILES['image'] ?? $_FILES['photo'] ?? null;

        if ($file === null) {
            respBadRequest(['file' => 'No se ha enviado ningún archivo en la petición.']);
            return;
        }

        $prefix = (string) ($_POST['prefix'] ?? 'media_');

        try {
            $filename = $this->mediaService->upload($file, $prefix);
            $url = $this->mediaService->getUrl($filename);

            respCreated([
                'filename' => $filename,
                'url'      => $url,
                'path'     => $filename,
            ]);
        } catch (\InvalidArgumentException $e) {
            respUnprocessable(['file' => $e->getMessage()]);
        } catch (\Throwable $e) {
            respServerError('Error interno al procesar el archivo: ' . $e->getMessage());
        }
    }

    /**
     * Endpoint API para eliminar un archivo subido.
     * DELETE /api/media
     */
    public function destroy(): void
    {
        if (!authCheck()) {
            respUnauthorized('No autenticado.');
            return;
        }

        $data = getJsonInput();
        $filename = (string) ($data['filename'] ?? $_GET['filename'] ?? '');

        if ($filename === '') {
            respBadRequest(['filename' => 'El nombre de archivo es requerido.']);
            return;
        }

        $deleted = $this->mediaService->delete($filename);
        if ($deleted) {
            respSuccess(['deleted' => true, 'filename' => $filename]);
        } else {
            respNotFound(['filename' => 'Archivo no encontrado.']);
        }
    }
}
