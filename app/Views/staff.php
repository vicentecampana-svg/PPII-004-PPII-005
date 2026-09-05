<?php

declare(strict_types=1);

/**
 * @var array $staff
 */
$staff ??= [];
?>
<section class="section staff-section">
  <div class="container">
    <h1 class="staff-page-title">Miembros del Staff</h1>

    <div class="staff-grid">
      <?php foreach ($staff as $member) : ?>
        <?php
          $name = $member['name'] ?? $member['nombre'] ?? '';
          $position = $member['position'] ?? $member['cargo'] ?? '';
          $photo = $member['photo'] ?? $member['imagen_url'] ?? null;
          $desc = $member['description'] ?? $member['descripcion'] ?? '';
        ?>
        <article class="staff-card-item">
          <h3 class="staff-card-name"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></h3>
          <div class="staff-card-photo-container">
            <img src="<?= htmlspecialchars(mediaUrl($photo, 'staff'), ENT_QUOTES, 'UTF-8') ?>"
                 alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                 loading="lazy"
                 class="staff-card-photo">
          </div>
          <?php if (!empty($position)) : ?>
            <p class="staff-card-role"><?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>
          <?php if (!empty($desc)) : ?>
            <p class="staff-card-desc"><?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
