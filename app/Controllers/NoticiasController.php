<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FooterService;
use App\Services\NewsService;
use App\Services\TagService;

/**
 * Página pública de noticias y buscador.
 */
final class NoticiasController extends Controller
{
    private NewsService $newsService;
    private TagService $tagService;
    private FooterService $footerService;

    public function __construct()
    {
        $this->newsService = new NewsService();
        $this->tagService = new TagService();
        $this->footerService = new FooterService();
    }

    public function index(): void
    {
        $footer = $this->footerService->getAll();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 12;
        $query = trim((string) ($_GET['q'] ?? $_GET['search'] ?? ''));
        $tagId = isset($_GET['tag_id']) && is_numeric($_GET['tag_id']) ? (int) $_GET['tag_id'] : null;

        $data = $this->newsService->getPublished($page, $perPage, $query, $tagId);
        $tags = $this->tagService->getAll();

        $this->render('noticias', [
            'pageTitle'       => 'Noticias — SFL ULS Lab',
            'metaDescription' => 'Noticias, eventos y novedades del Software Factory Lab de la Universidad de La Serena.',
            'noticias'        => $data['items'],
            'pagination'      => [
                'total'       => $data['total'],
                'page'        => $data['page'],
                'perPage'     => $data['per_page'],
                'totalPages'  => $data['total_pages'],
            ],
            'tags'            => $tags,
            'selectedTagId'   => $tagId,
            'query'           => $query,
            'enlacesFooter'   => $footer['links'],
            'contacto'        => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
        ]);
    }

    public function show(string|int $id): void
    {
        $footer = $this->footerService->getAll();
        $newsId = (int) $id;
        $noticia = $this->newsService->getPublishedById($newsId);

        if (!$noticia) {
            http_response_code(404);
            header('Location: /noticias');
            exit;
        }

        $this->render('noticia', [
            'pageTitle'       => $noticia['title'] . ' — SFL ULS Lab',
            'metaDescription' => $noticia['subtitle'] ?: mb_strimwidth(strip_tags($noticia['content']), 0, 160, '…'),
            'noticia'         => $noticia,
            'enlacesFooter'   => $footer['links'],
            'contacto'        => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
        ]);
    }
}
