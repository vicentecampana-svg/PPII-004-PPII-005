<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FooterService;
use App\Services\MediaService;
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
    private MediaService $mediaService;

    public function __construct(
        ?FooterService $footerService = null,
        ?ProjectService $projectService = null,
        ?ServiceService $serviceService = null,
        ?StaffService $staffService = null,
        ?NewsService $newsService = null,
        ?QueryService $queryService = null,
        ?UserService $userService = null,
        ?MediaService $mediaService = null
    ) {
        $this->footerService = $footerService ?? new FooterService();
        $this->projectService = $projectService ?? new ProjectService();
        $this->serviceService = $serviceService ?? new ServiceService();
        $this->staffService = $staffService ?? new StaffService();
        $this->newsService = $newsService ?? new NewsService();
        $this->queryService = $queryService ?? new QueryService();
        $this->userService = $userService ?? new UserService();
        $this->mediaService = $mediaService ?? new MediaService();
    }

    public function index(): void
    {
        $user = authUser();
        $roleNormalized = strtolower((string) ($user['role_name'] ?? 'usuario'));
        $defaultTab = ($roleNormalized === 'redactor' || $roleNormalized === 'editor') ? 'noticias' : 'proyectos';
        $tab = (string) ($_GET['tab'] ?? $defaultTab);

        $projects = [];
        $editingProject = null;
        $staffList = [];
        $editingStaff = null;
        $newsList = [];
        $editingNews = null;

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

        // Si la pestaña es staff y el usuario tiene permisos, cargar listado
        if ($tab === 'staff' && in_array($roleNormalized, ['superadmin', 'admin'], true)) {
            try {
                $staffData = $this->staffService->getAll(1, 100);
                $staffList = $staffData['items'] ?? [];

                $editId = (int) ($_GET['edit_id'] ?? 0);
                if ($editId > 0) {
                    $editingStaff = $this->staffService->getById($editId);
                }
            } catch (\Throwable) {
                // Degradar graciosamente
            }
        }

        // Si la pestaña es noticias y el usuario tiene permisos, cargar listado
        if ($tab === 'noticias' && in_array($roleNormalized, ['superadmin', 'admin', 'redactor', 'editor'], true)) {
            try {
                $newsData = $this->newsService->getAll(1, 100);
                $items = $newsData['items'] ?? [];

                // Redactor solo ve sus propias noticias si la regla de negocio lo exige
                if ($roleNormalized === 'redactor') {
                    $items = array_values(array_filter($items, fn(array $n) => (int) ($n['author_id'] ?? 0) === (int) ($user['id'] ?? 0)));
                }

                $newsList = $items;

                $editId = (int) ($_GET['edit_id'] ?? 0);
                if ($editId > 0) {
                    $editingNews = $this->newsService->getById($editId);
                    if ($roleNormalized === 'redactor' && (int) ($editingNews['author_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
                        $editingNews = null;
                    }
                }
            } catch (\Throwable) {
                // Degradar graciosamente
            }
        }

        $footer = ['links' => [], 'info' => ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl']];
        $editingFooterLink = null;
        try {
            $footer = $this->footerService->getAll();
            if ($tab === 'footer' && $roleNormalized === 'superadmin') {
                $editId = (int) ($_GET['edit_id'] ?? 0);
                if ($editId > 0) {
                    $editingFooterLink = $this->footerService->getLinkById($editId);
                }
            }
        } catch (\Throwable) {
            // Degradar graciosamente
        }

        $usersList = [];
        $rolesList = [];
        $editingUser = null;
        if ($tab === 'usuarios' && $roleNormalized === 'superadmin') {
            try {
                $usersData = $this->userService->getAll(1, 100);
                $usersList = $usersData['items'] ?? [];
                $rolesList = $this->userService->getRoles();

                $editId = (int) ($_GET['edit_id'] ?? 0);
                if ($editId > 0) {
                    $editingUser = $this->userService->getById($editId);
                }
            } catch (\Throwable) {
                // Degradar graciosamente
            }
        }

        $flashSuccess = $_SESSION['_flash_success'] ?? null;
        $flashError   = $_SESSION['_flash_error'] ?? null;
        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        $this->render('admin', [
            'pageTitle'          => 'Panel de Administración — SFL ULS Lab',
            'metaDescription'    => 'Panel de administración y gestión de contenidos del Software Factory Lab.',
            'extraCss'           => ['/assets/css/admin.css'],
            'user'               => $user,
            'activeTab'          => $tab,
            'projects'           => $projects,
            'editingProject'     => $editingProject,
            'staffList'          => $staffList,
            'editingStaff'       => $editingStaff,
            'newsList'           => $newsList,
            'editingNews'        => $editingNews,
            'siteContent'        => $footer['contenido'] ?? null,
            'footerLinks'        => $footer['links'] ?? [],
            'editingFooterLink'  => $editingFooterLink,
            'footerInfo'         => $footer['info'] ?? null,
            'usersList'          => $usersList,
            'rolesList'          => $rolesList,
            'editingUser'        => $editingUser,
            'flashSuccess'       => $flashSuccess,
            'flashError'         => $flashError,
            'enlacesFooter'      => $footer['links'] ?? [],
            'contacto'           => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
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
        if (isset($_FILES['image_file']) && !empty($_FILES['image_file']['name'])) {
            try {
                $image = $this->mediaService->upload($_FILES['image_file'], 'project_');
            } catch (\InvalidArgumentException $e) {
                $_SESSION['_flash_error'] = $e->getMessage();
                header('Location: /admin?tab=proyectos' . ($id > 0 ? '&edit_id=' . $id : ''));
                exit;
            } catch (\Throwable $e) {
                $_SESSION['_flash_error'] = 'Error al subir la imagen: ' . $e->getMessage();
                header('Location: /admin?tab=proyectos' . ($id > 0 ? '&edit_id=' . $id : ''));
                exit;
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

    /**
     * Crear o actualizar un miembro del staff desde el formulario del panel.
     */
    public function saveStaff(): void
    {
        $this->checkAdminPermissions();

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $position = trim((string) ($_POST['position'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $photo = trim((string) ($_POST['existing_photo'] ?? ''));

        if ($name === '') {
            $_SESSION['_flash_error'] = 'El nombre del miembro del staff es obligatorio.';
            header('Location: /admin?tab=staff' . ($id > 0 ? '&edit_id=' . $id : ''));
            exit;
        }

        // Procesar subida de foto si viene un archivo
        if (isset($_FILES['photo_file']) && !empty($_FILES['photo_file']['name'])) {
            try {
                $photo = $this->mediaService->upload($_FILES['photo_file'], 'staff_');
            } catch (\InvalidArgumentException $e) {
                $_SESSION['_flash_error'] = $e->getMessage();
                header('Location: /admin?tab=staff' . ($id > 0 ? '&edit_id=' . $id : ''));
                exit;
            } catch (\Throwable $e) {
                $_SESSION['_flash_error'] = 'Error al subir la foto: ' . $e->getMessage();
                header('Location: /admin?tab=staff' . ($id > 0 ? '&edit_id=' . $id : ''));
                exit;
            }
        }

        try {
            $data = [
                'name'        => $name,
                'position'    => $position !== '' ? $position : null,
                'description' => $description !== '' ? $description : null,
                'photo'       => $photo !== '' ? $photo : null,
            ];

            if ($id > 0) {
                $this->staffService->update($id, $data);
                $_SESSION['_flash_success'] = 'Miembro del staff actualizado exitosamente.';
            } else {
                $this->staffService->create($data);
                $_SESSION['_flash_success'] = 'Miembro del staff creado exitosamente.';
            }
        } catch (\Throwable $e) {
            $_SESSION['_flash_error'] = 'Error al guardar el miembro del staff: ' . $e->getMessage();
        }

        header('Location: /admin?tab=staff');
        exit;
    }

    /**
     * Eliminar un miembro del staff.
     */
    public function deleteStaff(): void
    {
        $this->checkAdminPermissions();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $this->staffService->delete($id);
                $_SESSION['_flash_success'] = 'Miembro del staff eliminado exitosamente.';
            } catch (\Throwable $e) {
                $_SESSION['_flash_error'] = 'Error al eliminar el miembro del staff: ' . $e->getMessage();
            }
        }

        header('Location: /admin?tab=staff');
        exit;
    }

    /**
     * Crear o actualizar una noticia desde el formulario del panel.
     */
    public function saveNews(): void
    {
        $user = authUser();
        $role = strtolower((string) ($user['role_name'] ?? ''));
        if (!in_array($role, ['superadmin', 'admin', 'editor', 'redactor'], true)) {
            http_response_code(403);
            echo 'Acceso no autorizado.';
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $subtitle = trim((string) ($_POST['subtitle'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $image = trim((string) ($_POST['existing_image'] ?? ''));
        $isApproved = isset($_POST['is_approved']) ? (bool) $_POST['is_approved'] : false;

        if ($title === '' || $content === '') {
            $_SESSION['_flash_error'] = 'El título y el contenido de la noticia son obligatorios.';
            header('Location: /admin?tab=noticias' . ($id > 0 ? '&edit_id=' . $id : ''));
            exit;
        }

        // Procesar subida de imagen si viene un archivo
        if (isset($_FILES['image_file']) && !empty($_FILES['image_file']['name'])) {
            try {
                $image = $this->mediaService->upload($_FILES['image_file'], 'news_');
            } catch (\InvalidArgumentException $e) {
                $_SESSION['_flash_error'] = $e->getMessage();
                header('Location: /admin?tab=noticias' . ($id > 0 ? '&edit_id=' . $id : ''));
                exit;
            } catch (\Throwable $e) {
                $_SESSION['_flash_error'] = 'Error al subir la imagen: ' . $e->getMessage();
                header('Location: /admin?tab=noticias' . ($id > 0 ? '&edit_id=' . $id : ''));
                exit;
            }
        }

        try {
            $data = [
                'title'    => $title,
                'subtitle' => $subtitle !== '' ? $subtitle : null,
                'content'  => $content,
                'image'    => $image !== '' ? $image : null,
            ];

            if ($id > 0) {
                $existing = $this->newsService->getById($id);
                if ($role === 'redactor' && (int) ($existing['author_id'] ?? 0) !== (int) $user['id']) {
                    http_response_code(403);
                    echo 'No tienes permiso para editar esta noticia.';
                    exit;
                }

                $this->newsService->update($id, $data);

                if (in_array($role, ['superadmin', 'admin', 'editor'], true)) {
                    $this->newsService->updateStatus($id, $isApproved ? 'publicada' : 'pendiente');
                }

                $_SESSION['_flash_success'] = 'Noticia actualizada exitosamente.';
            } else {
                $created = $this->newsService->create($data, (int) $user['id']);
                if ($isApproved && in_array($role, ['superadmin', 'admin', 'editor'], true)) {
                    $this->newsService->updateStatus((int) $created['id'], 'publicada');
                }
                $_SESSION['_flash_success'] = 'Noticia creada exitosamente.';
            }
        } catch (\Throwable $e) {
            $_SESSION['_flash_error'] = 'Error al guardar la noticia: ' . $e->getMessage();
        }

        header('Location: /admin?tab=noticias');
        exit;
    }

    /**
     * Eliminar una noticia.
     */
    public function deleteNews(): void
    {
        $user = authUser();
        $role = strtolower((string) ($user['role_name'] ?? ''));
        if (!in_array($role, ['superadmin', 'admin', 'editor', 'redactor'], true)) {
            http_response_code(403);
            echo 'Acceso no autorizado.';
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $existing = $this->newsService->getById($id);
                if ($role === 'redactor' && (int) ($existing['author_id'] ?? 0) !== (int) $user['id']) {
                    http_response_code(403);
                    echo 'No tienes permiso para eliminar esta noticia.';
                    exit;
                }

                $this->newsService->delete($id);
                $_SESSION['_flash_success'] = 'Noticia eliminada exitosamente.';
            } catch (\Throwable $e) {
                $_SESSION['_flash_error'] = 'Error al eliminar la noticia: ' . $e->getMessage();
            }
        }

        header('Location: /admin?tab=noticias');
        exit;
    }

    /**
     * Cambiar estado de publicación de una noticia.
     */
    public function toggleNewsStatus(): void
    {
        $user = authUser();
        $role = strtolower((string) ($user['role_name'] ?? ''));
        if (!in_array($role, ['superadmin', 'admin', 'editor'], true)) {
            http_response_code(403);
            echo 'Acceso no autorizado.';
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? 'publicada'));

        if ($id > 0) {
            try {
                $this->newsService->updateStatus($id, $status);
                $_SESSION['_flash_success'] = 'Estado de la noticia actualizado a ' . $status . '.';
            } catch (\Throwable $e) {
                $_SESSION['_flash_error'] = 'Error al cambiar estado: ' . $e->getMessage();
            }
        }

        header('Location: /admin?tab=noticias');
        exit;
    }

    /**
     * Guardar cambios en el contenido institucional (Sobre nosotros, misión y visión).
     */
    public function saveSobreNosotros(): void
    {
        $this->checkAdminPermissions();

        $sobreTitulo = trim((string) ($_POST['sobre_titulo'] ?? ''));
        $sobreTexto = trim((string) ($_POST['sobre_texto'] ?? ''));
        $misionTitulo = trim((string) ($_POST['mision_titulo'] ?? ''));
        $misionTexto = trim((string) ($_POST['mision_texto'] ?? ''));

        if ($sobreTitulo === '' || $sobreTexto === '') {
            $_SESSION['_flash_error'] = 'El título y texto de Sobre Nosotros son obligatorios.';
            header('Location: /admin?tab=sobre-nosotros');
            exit;
        }

        try {
            $data = [
                'sobre_titulo'  => $sobreTitulo,
                'sobre_texto'   => $sobreTexto,
                'mision_titulo' => $misionTitulo !== '' ? $misionTitulo : null,
                'mision_texto'  => $misionTexto !== '' ? $misionTexto : null,
            ];

            $this->footerService->updateContenido($data);
            $_SESSION['_flash_success'] = 'Contenido de Sobre Nosotros guardado exitosamente.';
        } catch (\Throwable $e) {
            $_SESSION['_flash_error'] = 'Error al guardar el contenido: ' . $e->getMessage();
        }

        header('Location: /admin?tab=sobre-nosotros');
        exit;
    }

    /**
     * Crear o actualizar un enlace del footer.
     */
    public function saveFooterLink(): void
    {
        $this->checkSuperAdminPermissions();

        $id = (int) ($_POST['id'] ?? 0);
        $grupo = trim((string) ($_POST['grupo'] ?? 'Sitio'));
        $etiqueta = trim((string) ($_POST['etiqueta'] ?? ''));
        $url = trim((string) ($_POST['url'] ?? '/'));
        $orden = (int) ($_POST['orden'] ?? 0);

        if ($etiqueta === '' || $url === '') {
            $_SESSION['_flash_error'] = 'La etiqueta y URL del enlace son obligatorios.';
            header('Location: /admin?tab=footer' . ($id > 0 ? '&edit_id=' . $id : ''));
            exit;
        }

        try {
            $data = [
                'grupo'    => $grupo !== '' ? $grupo : 'Sitio',
                'etiqueta' => $etiqueta,
                'url'      => $url,
                'orden'    => $orden,
            ];

            if ($id > 0) {
                $this->footerService->updateLink($id, $data);
                $_SESSION['_flash_success'] = 'Enlace del footer actualizado exitosamente.';
            } else {
                $this->footerService->createLink($data);
                $_SESSION['_flash_success'] = 'Enlace del footer creado exitosamente.';
            }
        } catch (\Throwable $e) {
            $_SESSION['_flash_error'] = 'Error al guardar el enlace: ' . $e->getMessage();
        }

        header('Location: /admin?tab=footer');
        exit;
    }

    /**
     * Eliminar un enlace del footer.
     */
    public function deleteFooterLink(): void
    {
        $this->checkSuperAdminPermissions();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $this->footerService->deleteLink($id);
                $_SESSION['_flash_success'] = 'Enlace del footer eliminado exitosamente.';
            } catch (\Throwable $e) {
                $_SESSION['_flash_error'] = 'Error al eliminar el enlace: ' . $e->getMessage();
            }
        }

        header('Location: /admin?tab=footer');
        exit;
    }

    /**
     * Guardar enlaces a redes sociales del footer (edición de existentes).
     */
    public function saveFooterSocial(): void
    {
        $this->checkSuperAdminPermissions();

        $linkedin = trim((string) ($_POST['social_linkedin'] ?? ''));
        $twitter = trim((string) ($_POST['social_twitter'] ?? ''));
        $instagram = trim((string) ($_POST['social_instagram'] ?? ''));

        try {
            $data = [
                'social_linkedin'  => $linkedin !== '' ? $linkedin : null,
                'social_twitter'   => $twitter !== '' ? $twitter : null,
                'social_instagram' => $instagram !== '' ? $instagram : null,
            ];

            $this->footerService->updateInfo($data);
            $_SESSION['_flash_success'] = 'Redes sociales del footer actualizadas exitosamente.';
        } catch (\Throwable $e) {
            $_SESSION['_flash_error'] = 'Error al actualizar redes sociales: ' . $e->getMessage();
        }

        header('Location: /admin?tab=footer');
        exit;
    }

    /**
     * Actualizar el rol asignado a un usuario.
     */
    public function updateUserRole(): void
    {
        $this->checkSuperAdminPermissions();

        $userId = (int) ($_POST['user_id'] ?? 0);
        $roleKey = strtolower(trim((string) ($_POST['role_key'] ?? '')));

        if ($userId <= 0 || $roleKey === '') {
            $_SESSION['_flash_error'] = 'Parámetros inválidos para actualización de rol.';
            header('Location: /admin?tab=usuarios');
            exit;
        }

        try {
            $roles = $this->userService->getRoles();
            $roleId = null;

            // Mapear rol_key al ID de la base de datos
            foreach ($roles as $r) {
                $rName = strtolower((string) ($r['name'] ?? ''));
                if ($roleKey === 'admin' && ($rName === 'admin' || $rName === 'superadmin')) {
                    $roleId = (int) $r['id'];
                    break;
                }
                if ($roleKey === $rName) {
                    $roleId = (int) $r['id'];
                    break;
                }
            }

            if ($roleId === null) {
                // Si no se encontró (ej. 'invitado' o fallback), buscar 'redactor' o el primer rol no admin
                $roleId = 4;
            }

            $this->userService->update($userId, ['role_id' => $roleId]);
            $_SESSION['_flash_success'] = 'Rol de usuario actualizado exitosamente.';
        } catch (\Throwable $e) {
            $_SESSION['_flash_error'] = 'Error al actualizar el rol del usuario: ' . $e->getMessage();
        }

        header('Location: /admin?tab=usuarios');
        exit;
    }

    /**
     * Crear o actualizar un usuario desde el formulario del panel.
     */
    public function saveUser(): void
    {
        $this->checkSuperAdminPermissions();

        $id       = (int) ($_POST['id'] ?? 0);
        $email    = trim((string) ($_POST['email'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $roleId   = (int) ($_POST['role_id'] ?? 4);
        $active   = !empty($_POST['active']);

        if ($email === '' || $username === '') {
            $_SESSION['_flash_error'] = 'El correo electrónico y nombre de usuario son obligatorios.';
            header('Location: /admin?tab=usuarios' . ($id > 0 ? '&edit_id=' . $id : ''));
            exit;
        }

        if ($id <= 0 && strlen($password) < 12) {
            $_SESSION['_flash_error'] = 'La contraseña debe tener al menos 12 caracteres.';
            header('Location: /admin?tab=usuarios');
            exit;
        }

        $data = [
            'email'    => $email,
            'username' => $username,
            'role_id'  => $roleId,
            'active'   => $active,
        ];

        if ($password !== '') {
            if (strlen($password) < 12) {
                $_SESSION['_flash_error'] = 'La contraseña debe tener al menos 12 caracteres.';
                header('Location: /admin?tab=usuarios' . ($id > 0 ? '&edit_id=' . $id : ''));
                exit;
            }
            $data['password'] = $password;
        }

        try {
            if ($id > 0) {
                $this->userService->update($id, $data);
                $_SESSION['_flash_success'] = 'Usuario actualizado exitosamente.';
            } else {
                $this->userService->create($data);
                $_SESSION['_flash_success'] = 'Usuario creado exitosamente.';
            }
        } catch (\InvalidArgumentException $e) {
            $errors = json_decode($e->getMessage(), true);
            $_SESSION['_flash_error'] = is_array($errors) ? implode(' ', $errors) : $e->getMessage();
        } catch (\Throwable $e) {
            $_SESSION['_flash_error'] = 'Error al guardar el usuario: ' . $e->getMessage();
        }

        header('Location: /admin?tab=usuarios');
        exit;
    }

    /**
     * Eliminar un usuario.
     */
    public function deleteUser(): void
    {
        $this->checkSuperAdminPermissions();

        $id = (int) ($_POST['id'] ?? 0);
        $currentUser = authUser();

        if ($id <= 0) {
            $_SESSION['_flash_error'] = 'ID de usuario inválido.';
            header('Location: /admin?tab=usuarios');
            exit;
        }

        if ($id === (int) ($currentUser['id'] ?? 0)) {
            $_SESSION['_flash_error'] = 'No puedes eliminar tu propia cuenta en sesión.';
            header('Location: /admin?tab=usuarios');
            exit;
        }

        try {
            $this->userService->delete($id);
            $_SESSION['_flash_success'] = 'Usuario eliminado exitosamente.';
        } catch (\Throwable $e) {
            $_SESSION['_flash_error'] = 'Error al eliminar el usuario: ' . $e->getMessage();
        }

        header('Location: /admin?tab=usuarios');
        exit;
    }

    private function checkSuperAdminPermissions(): void
    {
        $user = authUser();
        $role = strtolower((string) ($user['role_name'] ?? ''));
        if ($role !== 'superadmin') {
            http_response_code(403);
            echo 'Acceso no autorizado. Se requiere rol SuperAdmin.';
            exit;
        }
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

    private function handleImageUpload(array $file, string $prefix = 'project_'): ?string
    {
        try {
            return $this->mediaService->upload($file, $prefix);
        } catch (\Throwable) {
            return null;
        }
    }
}
