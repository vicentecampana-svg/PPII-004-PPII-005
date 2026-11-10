<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\NewsConflictException;
use App\Exceptions\ValidationException;
use App\Services\NewsService;

/**
 * CAL-03: controlador del panel para la sección de Noticias.
 */
final class AdminNewsController extends AdminControllerBase
{
    private NewsService $newsService;

    public function __construct()
    {
        parent::__construct();
        $this->newsService = new NewsService();
    }

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
        $authorDisplay = in_array($role, ['superadmin', 'admin', 'editor'], true)
            ? trim((string) ($_POST['author'] ?? ''))
            : trim((string) ($user['username'] ?? ''));
        $tags = trim((string) ($_POST['tags'] ?? ''));
        $isPublic = isset($_POST['is_public']);
        $isApproved = isset($_POST['is_approved']) ? (bool) $_POST['is_approved'] : false;
        $backUrl = '/admin?tab=noticias' . ($id > 0 ? '&edit_id=' . $id : '');

        if (isset($_FILES['image_file']) && !empty($_FILES['image_file']['name'])) {
            try {
                $image = $this->mediaService->upload($_FILES['image_file'], 'news_');
            } catch (\InvalidArgumentException) {
                $_SESSION['_flash_error'] = 'La imagen no es válida: solo se permiten JPG/PNG y máximo 5MB.';
                header('Location: /admin?tab=noticias' . ($id > 0 ? '&edit_id=' . $id : ''));
                exit;
            } catch (\Throwable) {
                $_SESSION['_flash_error'] = 'No se pudo subir la imagen. Inténtalo nuevamente.';
                header('Location: /admin?tab=noticias' . ($id > 0 ? '&edit_id=' . $id : ''));
                exit;
            }
        }

        try {
            $data = [
                'title'          => $title,
                'author_display' => $authorDisplay !== '' ? $authorDisplay : null,
                'subtitle'       => $subtitle !== '' ? $subtitle : null,
                'content'        => $content,
                'image'          => $image !== '' ? $image : null,
                'is_public' => $isPublic,
                'tags'           => $tags,
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
        } catch (ValidationException $e) {
            // RF-NEW-001: se indica el campo y el motivo, y no se guarda nada.
            $_SESSION['_flash_error'] = implode(' ', $e->getErrors());
            header('Location: ' . $backUrl);
            exit;
        } catch (\Throwable) {
            $_SESSION['_flash_error'] = 'No se pudo guardar la noticia. Inténtalo nuevamente.';
        }

        header('Location: /admin?tab=noticias');
        exit;
    }

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
            } catch (\Throwable) {
                $_SESSION['_flash_error'] = 'No se pudo eliminar la noticia. Inténtalo nuevamente.';
            }
        }

        header('Location: /admin?tab=noticias');
        exit;
    }

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
        $visibility = trim((string) ($_POST['visibility'] ?? ''));

        if ($id > 0) {
            try {
                if ($status === 'pendiente') {
                    // Despublicar una noticia ya aprobada.
                    $this->newsService->updateStatus($id, $status);
                    $_SESSION['_flash_success'] = 'La noticia volvió a quedar pendiente.';
                } else {
                    // RF-NEW-002 / RF-NEW-004: aprobar (con visibilidad) o rechazar una pendiente.
                    $this->newsService->resolve($id, $status, $visibility !== '' ? $visibility : null);
                    $_SESSION['_flash_success'] = $status === 'publicada'
                        ? 'Noticia aprobada y publicada.'
                        : 'Noticia rechazada: quedó archivada.';
                }
            } catch (ValidationException $e) {
                $_SESSION['_flash_error'] = implode(' ', $e->getErrors());
            } catch (NewsConflictException $e) {
                $_SESSION['_flash_error'] = $e->getMessage();
            } catch (\Throwable) {
                $_SESSION['_flash_error'] = 'No se pudo cambiar el estado de la noticia. Inténtalo nuevamente.';
            }
        }

        header('Location: /admin?tab=noticias');
        exit;
    }
}
