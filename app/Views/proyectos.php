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
        <img src="<?= htmlspecialchars(mediaUrl($p['imagen_url'], 'proyecto'), ENT_QUOTES, 'UTF-8') ?>"
             alt="<?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?>"
             loading="lazy" class="proyecto-tile-img">
        <p><?= htmlspecialchars($p['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
      </article>
    <?php endforeach; ?>
  </div>
</div>
