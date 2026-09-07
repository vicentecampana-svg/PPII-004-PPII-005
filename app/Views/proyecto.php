<?php

declare(strict_types=1);

/**
 * @var array $proyecto
 * @var array $otrosProyectos
 */
$proyecto ??= [];
$otrosProyectos ??= [];
?>
<article class="section noticia-detail-section">
  <div class="container noticia-detail-container">
    <div class="noticia-detail-back">
      <a href="/proyectos" class="noticia-back-link">
        &larr; Volver a proyectos
      </a>
    </div>

    <header class="noticia-detail-header text-center">
      <h1 class="noticia-detail-title">
        <?= htmlspecialchars($proyecto['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?>
      </h1>
    </header>

    <div class="noticia-detail-hero">
      <img src="<?= htmlspecialchars(mediaUrl($proyecto['imagen_url'] ?? null, 'proyecto'), ENT_QUOTES, 'UTF-8') ?>"
           alt="<?= htmlspecialchars($proyecto['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
           class="noticia-detail-hero-img">
    </div>

    <div class="noticia-detail-content">
      <?= nl2br(htmlspecialchars($proyecto['descripcion'] ?? '', ENT_QUOTES, 'UTF-8')) ?>
    </div>

    <?php if (!empty($proyecto['link'])): ?>
      <div class="section-cta">
        <a href="<?= htmlspecialchars($proyecto['link'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-destructive" target="_blank" rel="noopener noreferrer">
          Ver proyecto
        </a>
      </div>
    <?php endif; ?>

    <div class="noticia-detail-divider"></div>

    <?php if (!empty($otrosProyectos)): ?>
      <section class="noticia-related-section">
        <h2 class="noticia-related-heading">Otros proyectos</h2>

        <div class="proyectos-grid noticia-related-grid">
          <?php foreach ($otrosProyectos as $p) : ?>
            <article class="proyecto-tile">
              <h2><?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?></h2>
              <a href="/proyectos/<?= (int) $p['id'] ?>">
                <img src="<?= htmlspecialchars(mediaUrl($p['imagen_url'], 'proyecto'), ENT_QUOTES, 'UTF-8') ?>"
                     alt="<?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                     loading="lazy" class="proyecto-tile-img">
              </a>
              <p><?= htmlspecialchars($p['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
              <div class="section-cta">
                <a href="/proyectos/<?= (int) $p['id'] ?>" class="btn btn-destructive btn-sm">Ver más</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  </div>
</article>
