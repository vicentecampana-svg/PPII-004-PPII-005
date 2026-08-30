<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FooterService;
use App\Services\NewsService;
use App\Services\ProjectService;
use App\Services\QueryService;
use App\Services\ServiceService;
use App\Services\StaffService;
use App\Services\UserService;

/**
 * Controlador principal del panel de administración.
 */
final class AdminController extends Controller
{
    private FooterService $footerService;
    private ProjectService $projectService;
    private ServiceService $serviceService;
    private StaffService $staffService;
    private NewsService $newsService;
    private QueryService $queryService;
    private UserService $userService;

    public function __construct(
        ?FooterService $footerService = null,
        ?ProjectService $projectService = null,
        ?ServiceService $serviceService = null,
        ?StaffService $staffService = null,
        ?NewsService $newsService = null,
        ?QueryService $queryService = null,
        ?UserService $userService = null
    ) {
        $this->footerService = $footerService ?? new FooterService();
        $this->projectService = $projectService ?? new ProjectService();
        $this->serviceService = $serviceService ?? new ServiceService();
        $this->staffService = $staffService ?? new StaffService();
        $this->newsService = $newsService ?? new NewsService();
        $this->queryService = $queryService ?? new QueryService();
        $this->userService = $userService ?? new UserService();
    }

    public function index(): void
    {
        $user = authUser();
        $roleNormalized = strtolower((string) ($user['role_name'] ?? 'usuario'));
        $defaultTab = ($roleNormalized === 'redactor' || $roleNormalized === 'editor') ? 'noticias' : 'proyectos';
        $tab = (string) ($_GET['tab'] ?? $defaultTab);

        $projects = [];
        $editingProject = null;

        // Si la pestaña es proyectos y el usuario tiene permisos, cargar listado
        if ($tab === 'proyectos' && in_array($roleNormalized, ['superadmin', 'admin'], true)) {
            try {
                $projectsData = $this->projectService->getAll(1, 100, true);
                $projects = $projectsData['items'] ?? [];

                $editId = (int) ($_GET['edit_id'] ?? 0);
                if ($editId > 0) {
                    $editingProject = $this->projectService->getById($editId);
                }
            } catch (\Throwable) {
                // Degradar graciosamente
            }
        }

        $footer = ['links' => [], 'info' => ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl']];
        try {
            $footer = $this->footerService->getAll();
        } catch (\Throwable) {
            // Degradar graciosamente
        }

        $flashSuccess = $_SESSION['_flash_success'] ?? null;
        $flashError   = $_SESSION['_flash_error'] ?? null;
        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        $this->render('admin', [
            'pageTitle'       => 'Panel de Administración — SFL ULS Lab',
            'metaDescription' => 'Panel de administración y gestión de contenidos del Software Factory Lab.',
            'extraCss'        => ['/assets/css/admin.css'],
            'user'            => $user,
            'activeTab'       => $tab,
            'projects'        => $projects,
            'editingProject'  => $editingProject,
            'flashSuccess'    => $flashSuccess,
            'flashError'      => $flashError,
            'enlacesFooter'   => $footer['links'] ?? [],
            'contacto'        => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
        ]);
    }

    /**
     * Crear o actualizar un proyecto desde el formulario del panel.
     */
    public function saveProject(): void
    {
        $this->checkAdminPermissions();

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $link = trim((string) ($_POST['link'] ?? ''));
        $active = isset($_POST['active']) ? (bool) $_POST['active'] : false;
        $image = trim((string) ($_POST['existing_image'] ?? ''));

        if ($name === '') {
            $_SESSION['_flash_error'] = 'El título del proyecto es obligatorio.';
            header('Location: /admin?tab=proyectos' . ($id > 0 ? '&edit_id=' . $id : ''));
            exit;
        }

        // Procesar subida de imagen si viene un archivo
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded = $this->handleImageUpload($_FILES['image_file']);
            if ($uploaded !== null) {
                $image = $uploaded;
            }
        }

        try {
            $data = [
                'name'        => $name,
                'description' => $description !== '' ? $description : null,
                'link'        => $link !== '' ? $link : null,
                'active'      => $active,
                'image'       => $image !== '' ? $image : null,
            ];

            if ($id > 0) {
                $this->projectService->update($id, $data);
                $_SESSION['_flash_success'] = 'Proyecto actualizado exitosamente.';
            } else {
                $this->projectService->create($data);
                $_SESSION['_flash_success'] = 'Proyecto creado exitosamente.';
            }
        } catch (\Throwable $e) {
            $_SESSION['_flash_error'] = 'Error al guardar el proyecto: ' . $e->getMessage();
        }

        header('Location: /admin?tab=proyectos');
        exit;
    }

    /**
     * Eliminar un proyecto.
     */
    public function deleteProject(): void
    {
        $this->checkAdminPermissions();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $this->projectService->delete($id);
                $_SESSION['_flash_success'] = 'Proyecto eliminado exitosamente.';
            } catch (\Throwable $e) {
                $_SESSION['_flash_error'] = 'Error al eliminar el proyecto: ' . $e->getMessage();
            }
        }

        header('Location: /admin?tab=proyectos');
        exit;
    }

    private function checkAdminPermissions(): void
    {
        $user = authUser();
        $role = strtolower((string) ($user['role_name'] ?? ''));
        if (!in_array($role, ['superadmin', 'admin'], true)) {
            http_response_code(403);
            echo 'Acceso no autorizado.';
            exit;
        }
    }

    private function handleImageUpload(array $file): ?string
    {
        $uploadDir = dirname(__DIR__, 2) . '/storage/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = basename((string) $file['name']);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        if (!in_array($ext, $allowed, true)) {
            return null;
        }

        $newFileName = 'project_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $uploadDir . '/' . $newFileName;

        if (move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
            return $newFileName;
        }

        return null;
    }
}
