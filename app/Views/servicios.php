<?php

declare(strict_types=1);

/**
 * @var array $servicios
 */
$servicios ??= [];
?>
<section class="section servicios-section">
  <div class="container">
    <div class="servicios-header">
      <h1 class="servicios-page-title">Nuestros Servicios</h1>
      <p class="servicios-page-subtitle">
        Ofrecemos soluciones de desarrollo de software, consultoría técnica y capacitación adaptadas a las necesidades del entorno institucional, empresarial y comunitario.
      </p>
    </div>

    <div class="servicios-grid">
      <?php foreach ($servicios as $s) : ?>
        <article class="servicio-card">
          <img src="<?= htmlspecialchars(mediaUrl($s['image'] ?? null, 'servicio'), ENT_QUOTES, 'UTF-8') ?>"
               alt="<?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?>"
               loading="lazy"
               class="servicio-card-img">
          <div class="servicio-card-body">
            <h3 class="servicio-card-title"><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="servicio-card-desc"><?= htmlspecialchars($s['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <div class="servicio-card-action">
              <a href="<?= htmlspecialchars($s['link'] ?: '/contacto', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-destructive btn-sm">
                Solicitar servicio
              </a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="servicios-cta-box">
      <h2>¿Tienes un requerimiento especial?</h2>
      <p>Escríbenos y diseñemos juntos la solución tecnológica que mejor se adapte a tu proyecto.</p>
      <a href="/contacto" class="btn btn-destructive">Contáctenos</a>
    </div>
  </div>
</section>
