<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\MediaService;

/**
 * CAL-03: base de los controladores del panel.
 *
 * Reúne la subida de imágenes y la validación de permisos que antes vivían
 * duplicadas dentro del enorme AdminController.
 */
abstract class AdminControllerBase extends Controller
{
    protected MediaService $mediaService;

    public function __construct()
    {
        $this->mediaService = new MediaService();
    }

    protected function checkSuperAdminPermissions(): void
    {
        $user = authUser();
        $role = strtolower((string) ($user['role_name'] ?? ''));
        if ($role !== 'superadmin') {
            http_response_code(403);
            echo 'Acceso no autorizado. Se requiere rol SuperAdmin.';
            exit;
        }
    }

    protected function checkAdminPermissions(): void
    {
        $user = authUser();
        $role = strtolower((string) ($user['role_name'] ?? ''));
        if (!in_array($role, ['superadmin', 'admin'], true)) {
            http_response_code(403);
            echo 'Acceso no autorizado.';
            exit;
        }
    }

    protected function handleImageUpload(array $file, string $prefix = 'project_'): ?string
    {
        try {
            return $this->mediaService->upload($file, $prefix);
        } catch (\Throwable) {
            return null;
        }
    }
}
