<?php

declare(strict_types=1);

use App\Services\Media;

/**
 * @var array $contenido
 * @var array $proyectos
 * @var array $staff
 * @var array $noticias
 */
?>
<section class="hero" id="sobre-nosotros">
  <div class="container hero-inner">
    <img src="/assets/images/logo-sfl-color.png" alt="SFL ULS Lab" width="260" height="86" class="hero-logo">

    <h1><?= htmlspecialchars($contenido['sobre_titulo'], ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= nl2br(htmlspecialchars($contenido['sobre_texto'], ENT_QUOTES, 'UTF-8')) ?></p>

    <h2><?= htmlspecialchars($contenido['mision_titulo'], ENT_QUOTES, 'UTF-8') ?></h2>
    <p><?= nl2br(htmlspecialchars($contenido['mision_texto'], ENT_QUOTES, 'UTF-8')) ?></p>

    <a href="#contacto" class="btn btn-destructive">Contáctenos</a>
  </div>
</section>

<section class="section section-surface" id="proyectos">
  <div class="container">
    <h2 class="section-title">Proyectos de SFL</h2>
    <div class="grid grid-proyectos">
      <?php foreach ($proyectos as $p) : ?>
        <article class="card">
          <img src="<?= htmlspecialchars(Media::url($p['imagen_url'], 'proyecto'), ENT_QUOTES, 'UTF-8') ?>"
               alt="<?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?>"
               loading="lazy" class="card-img card-img-4-3">
          <div class="card-body">
            <h3><?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= htmlspecialchars($p['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
            <a href="/proyectos" class="btn btn-destructive btn-sm">Ver más</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <div class="section-cta">
      <a href="/proyectos" class="btn btn-destructive">Ver todos los proyectos</a>
    </div>
  </div>
</section>

<section class="section" id="staff">
  <div class="container">
    <div class="section-rule">
      <h2 class="section-title">Conoce al Staff</h2>
    </div>
    <div class="grid grid-staff">
      <?php foreach ($staff as $m) : ?>
        <article class="staff-card">
          <h3><?= htmlspecialchars($m['nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
          <img src="<?= htmlspecialchars(Media::url($m['imagen_url'], 'staff'), ENT_QUOTES, 'UTF-8') ?>"
               alt="<?= htmlspecialchars($m['nombre'], ENT_QUOTES, 'UTF-8') ?>"
               loading="lazy" class="card-img card-img-square">
          <p class="staff-cargo"><?= htmlspecialchars($m['cargo'], ENT_QUOTES, 'UTF-8') ?></p>
          <p class="staff-desc"><?= htmlspecialchars($m['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
        </article>
      <?php endforeach; ?>
    </div>
    <div class="section-cta">
      <a href="#staff" class="btn btn-destructive">Ver todos l@s miembr@s</a>
    </div>
  </div>
</section>

<section class="section section-surface" id="noticias">
  <div class="container">
    <div class="section-rule section-rule-inline">
      <h2 class="section-title">Noticias</h2>
      <a href="#noticias" class="btn btn-destructive btn-sm">Ver más</a>
    </div>
    <div class="grid grid-noticias">
      <?php foreach ($noticias as $n) : ?>
        <article class="card">
          <img src="<?= htmlspecialchars(Media::url($n['imagen_url'], 'noticia'), ENT_QUOTES, 'UTF-8') ?>"
               alt="<?= htmlspecialchars($n['titulo'], ENT_QUOTES, 'UTF-8') ?>"
               loading="lazy" class="card-img card-img-16-9">
          <div class="card-body">
            <h3><?= htmlspecialchars($n['titulo'], ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= htmlspecialchars($n['resumen'], ENT_QUOTES, 'UTF-8') ?></p>
            <a href="#noticias" class="read-more">Leer noticia</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" id="contacto">
  <div class="container contact-cta">
    <h2 class="section-title">¿Tienes un proyecto en mente?</h2>
    <p>Escríbenos y conversemos sobre cómo el laboratorio puede ayudarte.</p>
    <a href="mailto:contacto@sfl-uls.cl" class="btn btn-destructive">Contáctenos</a>
  </div>
</section>
