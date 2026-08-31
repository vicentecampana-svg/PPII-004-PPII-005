<?php

declare(strict_types=1);

/**
 * @var array $noticias
 * @var array $pagination
 * @var array $tags
 * @var int|null $selectedTagId
 * @var string $query
 */
$noticias ??= [];
$pagination ??= ['total' => 0, 'page' => 1, 'totalPages' => 1];
$tags ??= [];
$selectedTagId ??= null;
$query ??= '';
?>
<section class="section noticias-page-section">
  <div class="container">
    <!-- Buscador superior -->
    <div class="noticias-search-wrapper">
      <form method="get" action="/noticias" class="noticias-search-form">
        <div class="noticias-search-input-group">
          <svg class="search-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="8"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          <input
            type="text"
            name="q"
            value="<?= e($query) ?>"
            placeholder="Buscar"
            class="noticias-search-input"
            aria-label="Buscar noticias">
          <?php if ($selectedTagId): ?>
            <input type="hidden" name="tag_id" value="<?= (int) $selectedTagId ?>">
          <?php endif; ?>
          <?php if ($query !== ''): ?>
            <a href="/noticias<?= $selectedTagId ? '?tag_id=' . (int) $selectedTagId : '' ?>" class="search-clear-btn" aria-label="Limpiar búsqueda">&times;</a>
          <?php endif; ?>
        </div>
      </form>
      <div class="noticias-search-divider"></div>
    </div>

    <!-- Filtros de tags si existen -->
    <?php if (!empty($tags)): ?>
      <div class="noticias-tag-filters">
        <span class="tag-filter-label">Temas:</span>
        <a href="/noticias<?= $query !== '' ? '?q=' . urlencode($query) : '' ?>"
           class="tag-filter-pill <?= $selectedTagId === null ? 'active' : '' ?>">
          Todos
        </a>
        <?php foreach ($tags as $t): ?>
          <?php
            $params = [];
            if ($query !== '') $params['q'] = $query;
            $params['tag_id'] = $t['id'];
            $linkUrl = '/noticias?' . http_build_query($params);
            $isActive = $selectedTagId === (int) $t['id'];
          ?>
          <a href="<?= e($linkUrl) ?>" class="tag-filter-pill <?= $isActive ? 'active' : '' ?>">
            <?= e($t['name']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Título de sección -->
    <div class="noticias-section-header">
      <h2 class="noticias-heading">
        <?= $query !== '' ? 'Resultados de búsqueda: "' . e($query) . '"' : 'Noticias más recientes' ?>
      </h2>
    </div>

    <!-- Listado de Noticias -->
    <?php if (empty($noticias)): ?>
      <div class="noticias-empty-card">
        <p>No se encontraron noticias que coincidan con la búsqueda.</p>
        <a href="/noticias" class="btn btn-destructive" style="margin-top: 16px;">Ver todas las noticias</a>
      </div>
    <?php else: ?>
      <div class="noticias-grid">
        <?php foreach ($noticias as $n): ?>
          <?php
            $newsId = (int) ($n['id'] ?? 1);
            $title = $n['title'] ?? '';
            $author = $n['author'] ?? 'Periodista';
            $image = $n['image'] ?? null;
            $summary = $n['subtitle'] ?: mb_strimwidth(strip_tags($n['content'] ?? ''), 0, 140, '…');
          ?>
          <article class="noticia-card">
            <a href="/noticias/<?= $newsId ?>" class="noticia-card-img-link" tabindex="-1">
              <img src="<?= htmlspecialchars(mediaUrl($image, 'noticia'), ENT_QUOTES, 'UTF-8') ?>"
                   alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
                   loading="lazy"
                   class="noticia-card-img">
            </a>
            <div class="noticia-card-content">
              <h3 class="noticia-card-title">
                <a href="/noticias/<?= $newsId ?>">
                  <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                </a>
              </h3>
              <p class="noticia-card-author">por: <?= htmlspecialchars($author, ENT_QUOTES, 'UTF-8') ?></p>
              <p class="noticia-card-summary"><?= htmlspecialchars($summary, ENT_QUOTES, 'UTF-8') ?></p>
              <div class="noticia-card-actions">
                <a href="/noticias/<?= $newsId ?>" class="btn btn-destructive btn-sm">
                  Ver más
                </a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
