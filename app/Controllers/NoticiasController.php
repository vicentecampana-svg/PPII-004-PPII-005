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
        try {
            $footer = $this->footerService->getAll();
        } catch (\Throwable) {
            $footer = ['links' => [], 'info' => null];
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 12;
        $query = trim((string) ($_GET['q'] ?? $_GET['search'] ?? ''));
        $tagId = isset($_GET['tag_id']) && is_numeric($_GET['tag_id']) ? (int) $_GET['tag_id'] : null;

        try {
            $data = $this->newsService->getPublished($page, $perPage, $query, $tagId);
        } catch (\Throwable) {
            $data = ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage, 'total_pages' => 0];
        }

        try {
            $tags = $this->tagService->getAll();
        } catch (\Throwable) {
            $tags = [];
        }

        $this->render('noticias', [
            'pageTitle'       => 'Noticias — SFL ULS Lab',
            'metaDescription' => 'Noticias, eventos y novedades del Software Factory Lab de la Universidad de La Serena.',
            'noticias'        => $data['items'] ?? [],
            'pagination'      => [
                'total'       => $data['total'] ?? 0,
                'page'        => $data['page'] ?? $page,
                'perPage'     => $data['per_page'] ?? $perPage,
                'totalPages'  => $data['total_pages'] ?? 0,
            ],
            'tags'            => $tags,
            'selectedTagId'   => $tagId,
            'query'           => $query,
            'enlacesFooter'   => $footer['links'] ?? [],
            'contacto'        => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
        ]);
    }

    public function show(string|int $id): void
    {
        try {
            $footer = $this->footerService->getAll();
        } catch (\Throwable) {
            $footer = ['links' => [], 'info' => null];
        }

        $newsId = (int) $id;
        try {
            $noticia = $this->newsService->getPublishedById($newsId);
        } catch (\Throwable) {
            $noticia = null;
        }

        if (!$noticia) {
            http_response_code(404);
            header('Location: /noticias');
            exit;
        }

        $this->render('noticia', [
            'pageTitle'       => ($noticia['title'] ?? 'Noticia') . ' — SFL ULS Lab',
            'metaDescription' => ($noticia['subtitle'] ?? '') ?: mb_strimwidth(strip_tags((string) ($noticia['content'] ?? '')), 0, 160, '…'),
            'noticia'         => $noticia,
            'enlacesFooter'   => $footer['links'] ?? [],
            'contacto'        => $footer['info'] ?? ['address' => 'La Serena, Chile', 'email' => 'contacto@sfl.uls.cl'],
        ]);
    }
}
