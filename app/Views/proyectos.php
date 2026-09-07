<?php

declare(strict_types=1);

/**
 * @var array $proyectos
 */
?>
<div class="container proyectos-page">
  <h1>Proyectos de SFL</h1>
  <div class="proyectos-grid">
    <?php foreach ($proyectos as $p) : ?>
      <article class="proyecto-tile">
        <h2><?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?></h2>
        <?php if (!empty($p['id'])) : ?>
          <a href="/proyectos/<?= (int) $p['id'] ?>">
            <img src="<?= htmlspecialchars(mediaUrl($p['imagen_url'], 'proyecto'), ENT_QUOTES, 'UTF-8') ?>"
                 alt="<?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                 loading="lazy" class="proyecto-tile-img">
          </a>
        <?php else : ?>
          <img src="<?= htmlspecialchars(mediaUrl($p['imagen_url'], 'proyecto'), ENT_QUOTES, 'UTF-8') ?>"
               alt="<?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?>"
               loading="lazy" class="proyecto-tile-img">
        <?php endif; ?>
        <p><?= htmlspecialchars($p['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php if (!empty($p['id'])) : ?>
          <div class="proyecto-tile-action">
            <a href="/proyectos/<?= (int) $p['id'] ?>" class="btn btn-destructive btn-sm">Ver más</a>
          </div>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
</div>
