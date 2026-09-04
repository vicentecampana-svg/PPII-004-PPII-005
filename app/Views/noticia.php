<?php

declare(strict_types=1);

/**
 * @var array $noticia
 * @var array $otrasNoticias
 */
$noticia ??= [];
$otrasNoticias ??= [];
?>
<article class="section noticia-detail-section">
  <div class="container noticia-detail-container">
    <div class="noticia-detail-back">
      <a href="/noticias" class="noticia-back-link">
        &larr; Volver a noticias
      </a>
    </div>

    <!-- Encabezado de la noticia -->
    <header class="noticia-detail-header text-center">
      <h1 class="noticia-detail-title">
        <?= strtoupper(htmlspecialchars($noticia['title'] ?? '', ENT_QUOTES, 'UTF-8')) ?>
      </h1>

      <p class="noticia-detail-author">
        <em>Redactor: <?= htmlspecialchars($noticia['author'] ?? 'Periodista', ENT_QUOTES, 'UTF-8') ?></em>
      </p>

      <?php if (!empty($noticia['subtitle'])): ?>
        <p class="noticia-detail-lead">
          <?= nl2br(htmlspecialchars($noticia['subtitle'], ENT_QUOTES, 'UTF-8')) ?>
        </p>
      <?php endif; ?>
    </header>

    <!-- Imagen Principal -->
    <?php if (!empty($noticia['image'])): ?>
      <div class="noticia-detail-hero">
        <img src="<?= htmlspecialchars(mediaUrl($noticia['image'], 'noticia'), ENT_QUOTES, 'UTF-8') ?>"
             alt="<?= htmlspecialchars($noticia['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
             class="noticia-detail-hero-img">
      </div>
    <?php endif; ?>

    <!-- Contenido / Cuerpo -->
    <div class="noticia-detail-content">
      <?= nl2br(htmlspecialchars($noticia['content'] ?? '', ENT_QUOTES, 'UTF-8')) ?>
    </div>

    <!-- Separador azul de sección -->
    <div class="noticia-detail-divider"></div>

    <!-- Otras noticias relevantes -->
    <?php if (!empty($otrasNoticias)): ?>
      <section class="noticia-related-section">
        <h2 class="noticia-related-heading">Otras noticias relevantes</h2>

        <div class="noticias-grid noticia-related-grid">
          <?php foreach ($otrasNoticias as $rel): ?>
            <?php
              $relId = (int) ($rel['id'] ?? 1);
              $relTitle = $rel['title'] ?? '';
              $relAuthor = $rel['author'] ?? 'Periodista';
              $relImage = $rel['image'] ?? null;
              $relSummary = $rel['subtitle'] ?: mb_strimwidth(strip_tags($rel['content'] ?? ''), 0, 120, '…');
            ?>
            <article class="noticia-card">
              <a href="/noticias/<?= $relId ?>" class="noticia-card-img-link" tabindex="-1">
                <img src="<?= htmlspecialchars(mediaUrl($relImage, 'noticia'), ENT_QUOTES, 'UTF-8') ?>"
                     alt="<?= htmlspecialchars($relTitle, ENT_QUOTES, 'UTF-8') ?>"
                     loading="lazy"
                     class="noticia-card-img">
              </a>
              <div class="noticia-card-content">
                <h3 class="noticia-card-title">
                  <a href="/noticias/<?= $relId ?>">
                    <?= htmlspecialchars($relTitle, ENT_QUOTES, 'UTF-8') ?>
                  </a>
                </h3>
                <p class="noticia-card-author">por: <?= htmlspecialchars($relAuthor, ENT_QUOTES, 'UTF-8') ?></p>
                <p class="noticia-card-summary"><?= htmlspecialchars($relSummary, ENT_QUOTES, 'UTF-8') ?></p>
                <div class="noticia-card-actions">
                  <a href="/noticias/<?= $relId ?>" class="btn btn-destructive btn-sm">
                    Ver más
                  </a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  </div>
</article>
