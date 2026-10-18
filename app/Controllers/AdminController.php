<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CreditMemberService;
use App\Services\FooterService;
use App\Services\MediaService;
use App\Services\NewsService;
use App\Services\PasswordPolicy;
use App\Services\ProjectService;
use App\Services\QueryService;
use App\Services\ServiceService;
use App\Services\StaffService;
use App\Services\UserService;

/**
 * CAL-03: el panel ahora tiene un controlador por sección. Esta clase solo
 * atiende el tablero principal (/admin); las acciones de cada sección viven
 * en Admin*Controller (proyectos, staff, noticias, contenido, footer,
 * usuarios y créditos).
 */
final class AdminController extends AdminControllerBase
{
    private FooterService $footerService;
    private ProjectService $projectService;
    private ServiceService $serviceService;
    private StaffService $staffService;
    private NewsService $newsService;
    private QueryService $queryService;
    private UserService $userService;
    private CreditMemberService $creditMemberService;

    public function __construct(
        ?FooterService $footerService = null,
        ?ProjectService $projectService = null,
        ?ServiceService $serviceService = null,
        ?StaffService $staffService = null,
        ?NewsService $newsService = null,
        ?QueryService $queryService = null,
        ?UserService $userService = null,
        ?MediaService $mediaService = null,
        ?CreditMemberService $creditMemberService = null
    ) {
        parent::__construct();
        $this->footerService = $footerService ?? new FooterService();
        $this->projectService = $projectService ?? new ProjectService();
        $this->serviceService = $serviceService ?? new ServiceService();
        $this->staffService = $staffService ?? new StaffService();
        $this->newsService = $newsService ?? new NewsService();
        $this->queryService = $queryService ?? new QueryService();
        $this->userService = $userService ?? new UserService();
        $this->mediaService = $mediaService ?? new MediaService();
        $this->creditMemberService = $creditMemberService ?? new CreditMemberService();
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
                    if ($roleNormalized === 'redactor' && (int) ($editingNews['author_id'] ?? 0) !== (int) ($user['id'])) {
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

        $creditsList = [];
        $editingCredit = null;
        if ($tab === 'creditos' && $roleNormalized === 'superadmin') {
            try {
                $creditsData = $this->creditMemberService->getAll(1, 100, true);
                $creditsList = $creditsData['items'] ?? [];

                $editId = (int) ($_GET['edit_id'] ?? 0);
                if ($editId > 0) {
                    $editingCredit = $this->creditMemberService->getById($editId, true);
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
            'creditsList'        => $creditsList,
            'editingCredit'      => $editingCredit,
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
}
