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
<section class="section section-surface" style="padding: 48px 0; min-height: 70vh;">
  <div class="container">
    <div style="margin-bottom: 32px;">
      <h1 class="section-title" style="margin-bottom: 12px;">Noticias y Novedades</h1>
      <p style="color: var(--muted-foreground); max-width: 600px;">
        Entérate de las últimas actividades, lanzamientos y proyectos realizados por el Software Factory Lab.
      </p>
    </div>

    <!-- Buscador y Filtros -->
    <form method="get" action="/noticias" style="margin-bottom: 32px; display: flex; flex-direction: column; gap: 16px;">
      <div style="display: flex; gap: 12px; max-width: 600px;">
        <input
          type="text"
          name="q"
          value="<?= e($query) ?>"
          placeholder="Buscar por título o contenido..."
          style="flex: 1; height: 42px; padding: 0 16px; border: 1px solid var(--border); border-radius: var(--radius); font-family: inherit; font-size: 0.95rem;">
        <?php if ($selectedTagId): ?>
          <input type="hidden" name="tag_id" value="<?= (int) $selectedTagId ?>">
        <?php endif; ?>
        <button type="submit" class="btn btn-destructive" style="padding: 0 20px; height: 42px; font-weight: 600;">
          Buscar
        </button>
        <?php if ($query !== '' || $selectedTagId !== null): ?>
          <a href="/noticias" class="btn" style="padding: 0 16px; height: 42px; line-height: 40px; background: #e2e8f0; color: #334155; border-radius: var(--radius);">
            Limpiar
          </a>
        <?php endif; ?>
      </div>

      <!-- Filtro por Temas / Tags -->
      <?php if (!empty($tags)): ?>
        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
          <span style="font-size: 0.875rem; font-weight: 600; color: var(--muted-foreground); margin-right: 4px;">Filtrar por tema:</span>
          <a href="/noticias<?= $query !== '' ? '?q=' . urlencode($query) : '' ?>"
             style="padding: 4px 12px; border-radius: 999px; font-size: 0.8125rem; font-weight: 500; text-decoration: none; <?= $selectedTagId === null ? 'background: var(--brand); color: white;' : 'background: #e2e8f0; color: #475569;' ?>">
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
            <a href="<?= e($linkUrl) ?>"
               style="padding: 4px 12px; border-radius: 999px; font-size: 0.8125rem; font-weight: 500; text-decoration: none; <?= $isActive ? 'background: var(--brand); color: white;' : 'background: #e2e8f0; color: #475569;' ?>">
              <?= e($t['name']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </form>

    <!-- Listado de Noticias -->
    <?php if (empty($noticias)): ?>
      <div style="background: white; padding: 48px; text-align: center; border-radius: var(--radius); box-shadow: var(--shadow-card);">
        <p style="font-size: 1.1rem; color: var(--muted-foreground);">No se encontraron noticias que coincidan con la búsqueda.</p>
        <a href="/noticias" class="btn btn-destructive" style="margin-top: 16px; display: inline-block;">Ver todas las noticias</a>
      </div>
    <?php else: ?>
      <div class="grid grid-noticias" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px;">
        <?php foreach ($noticias as $n): ?>
          <article class="card" style="display: flex; flex-direction: column; background: white; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-card);">
            <img src="<?= htmlspecialchars(mediaUrl($n['image'] ?? null, 'noticia'), ENT_QUOTES, 'UTF-8') ?>"
                 alt="<?= htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8') ?>"
                 loading="lazy" class="card-img card-img-16-9" style="width: 100%; aspect-ratio: 16/9; object-fit: cover;">
            <div class="card-body" style="padding: 20px; display: flex; flex-direction: column; flex: 1;">
              <?php if (!empty($n['tags'])): ?>
                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 8px;">
                  <?php foreach ($n['tags'] as $t): ?>
                    <span style="font-size: 0.75rem; background: rgba(50, 101, 160, 0.1); color: var(--brand); padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                      <?= e($t['name']) ?>
                    </span>
                  <?php endforeach; ?>
                </div>
              <?php elseif (!empty($n['tag'])): ?>
                <div style="margin-bottom: 8px;">
                  <span style="font-size: 0.75rem; background: rgba(50, 101, 160, 0.1); color: var(--brand); padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                    <?= e($n['tag']) ?>
                  </span>
                </div>
              <?php endif; ?>

              <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 8px; line-height: 1.3;">
                <a href="/noticias/<?= (int) $n['id'] ?>" style="color: inherit; text-decoration: none;">
                  <?= htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8') ?>
                </a>
              </h3>

              <p style="color: var(--muted-foreground); font-size: 0.9rem; flex: 1; margin-bottom: 16px;">
                <?= htmlspecialchars($n['subtitle'] ?: mb_strimwidth(strip_tags($n['content'] ?? ''), 0, 140, '…'), ENT_QUOTES, 'UTF-8') ?>
              </p>

              <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 12px; border-top: 1px solid var(--border);">
                <span style="font-size: 0.8rem; color: var(--muted-foreground);">
                  <?= !empty($n['publication_date']) ? date('d/m/Y', strtotime($n['publication_date'])) : '' ?>
                </span>
                <a href="/noticias/<?= (int) $n['id'] ?>" class="btn btn-destructive btn-sm" style="font-size: 0.85rem; padding: 4px 12px;">
                  Leer noticia
                </a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
