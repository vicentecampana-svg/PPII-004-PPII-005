<?php

declare(strict_types=1);

/**
 * @var array $noticia
 */
$noticia ??= [];
?>
<article class="section" style="padding: 48px 0; min-height: 70vh;">
  <div class="container" style="max-width: 800px;">
    <div style="margin-bottom: 24px;">
      <a href="/noticias" style="color: var(--brand); font-size: 0.9rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
        &larr; Volver a todas las noticias
      </a>
    </div>

    <!-- Tags -->
    <?php if (!empty($noticia['tags'])): ?>
      <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;">
        <?php foreach ($noticia['tags'] as $t): ?>
          <a href="/noticias?tag_id=<?= (int) $t['id'] ?>"
             style="font-size: 0.8125rem; background: rgba(50, 101, 160, 0.1); color: var(--brand); padding: 4px 10px; border-radius: 999px; font-weight: 600; text-decoration: none;">
            <?= e($t['name']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php elseif (!empty($noticia['tag'])): ?>
      <div style="margin-bottom: 12px;">
        <span style="font-size: 0.8125rem; background: rgba(50, 101, 160, 0.1); color: var(--brand); padding: 4px 10px; border-radius: 999px; font-weight: 600;">
          <?= e($noticia['tag']) ?>
        </span>
      </div>
    <?php endif; ?>

    <h1 style="font-size: 2.25rem; line-height: 1.2; font-weight: 800; color: var(--foreground); margin-bottom: 12px;">
      <?= htmlspecialchars($noticia['title'], ENT_QUOTES, 'UTF-8') ?>
    </h1>

    <?php if (!empty($noticia['subtitle'])): ?>
      <p style="font-size: 1.25rem; line-height: 1.5; color: var(--muted-foreground); margin-bottom: 20px;">
        <?= htmlspecialchars($noticia['subtitle'], ENT_QUOTES, 'UTF-8') ?>
      </p>
    <?php endif; ?>

    <div style="display: flex; align-items: center; gap: 16px; font-size: 0.875rem; color: var(--muted-foreground); margin-bottom: 32px; padding-bottom: 16px; border-bottom: 1px solid var(--border);">
      <?php if (!empty($noticia['publication_date'])): ?>
        <span>Publicado el <?= date('d/m/Y', strtotime($noticia['publication_date'])) ?></span>
      <?php endif; ?>
      <?php if (!empty($noticia['author'])): ?>
        <span>Por <strong><?= e($noticia['author']) ?></strong></span>
      <?php endif; ?>
    </div>

    <?php if (!empty($noticia['image'])): ?>
      <div style="margin-bottom: 32px; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-card);">
        <img src="<?= htmlspecialchars(mediaUrl($noticia['image'], 'noticia'), ENT_QUOTES, 'UTF-8') ?>"
             alt="<?= htmlspecialchars($noticia['title'], ENT_QUOTES, 'UTF-8') ?>"
             style="width: 100%; height: auto; display: block;">
      </div>
    <?php endif; ?>

    <div class="prose" style="font-size: 1.1rem; line-height: 1.8; color: var(--foreground);">
      <?= nl2br(htmlspecialchars($noticia['content'], ENT_QUOTES, 'UTF-8')) ?>
    </div>

    <div style="margin-top: 48px; padding-top: 24px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
      <a href="/noticias" class="btn btn-destructive">
        &larr; Ver más noticias
      </a>
      <a href="#contacto" class="btn" style="background: var(--brand); color: white; border-radius: var(--radius); padding: 8px 16px;">
        Contactar al laboratorio
      </a>
    </div>
  </div>
</article>
