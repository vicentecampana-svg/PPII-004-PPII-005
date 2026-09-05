<?php

declare(strict_types=1);

/**
 * @var array|null $siteContent
 */
$siteContent ??= null;

$sobreTitulo = $siteContent['sobre_titulo'] ?? 'Sobre nosotros';
$sobreTexto = $siteContent['sobre_texto'] ?? "Software Factory Lab es la fábrica de software de la Universidad de La Serena, donde estudiantes y profesionales desarrollan soluciones digitales reales para instituciones y empresas de la región, bajo estándares de la industria.";
$misionTitulo = $siteContent['mision_titulo'] ?? 'Misión, visión y objetivos';
$misionTexto = $siteContent['mision_texto'] ?? "Formar talento tecnológico mediante la práctica en proyectos reales, entregando productos de calidad que aporten valor al desarrollo regional. Buscamos ser un referente en innovación, transferencia tecnológica y vinculación con el medio.";
?>

<div class="admin-full-card-wrapper">
  <div class="admin-sidebar-card admin-content-card">
    <div class="admin-tab-header">
      <h2 class="admin-tab-content-title" style="margin-bottom: 20px; font-size: 1.15rem;">Sobre nosotros, misión y visión</h2>
    </div>

    <form method="post" action="/admin/sobre-nosotros">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <!-- Título «Sobre nosotros» -->
      <div class="admin-form-group">
        <label class="admin-form-label" for="sobre-titulo">Título «Sobre nosotros»</label>
        <input type="text" 
               id="sobre-titulo" 
               name="sobre_titulo" 
               class="admin-form-input" 
               value="<?= e($sobreTitulo) ?>" 
               maxlength="255" 
               placeholder="Sobre nosotros"
               required>
      </div>

      <!-- Texto «Sobre nosotros» -->
      <div class="admin-form-group">
        <label class="admin-form-label" for="sobre-texto">Texto «Sobre nosotros»</label>
        <textarea id="sobre-texto" 
                  name="sobre_texto" 
                  class="admin-form-textarea" 
                  rows="4" 
                  placeholder="Descripción de la institución..."
                  required><?= e($sobreTexto) ?></textarea>
      </div>

      <!-- Título «Misión y visión» -->
      <div class="admin-form-group">
        <label class="admin-form-label" for="mision-titulo">Título «Misión y visión»</label>
        <input type="text" 
               id="mision-titulo" 
               name="mision_titulo" 
               class="admin-form-input" 
               value="<?= e($misionTitulo) ?>" 
               maxlength="255" 
               placeholder="Misión, visión y objetivos"
               required>
      </div>

      <!-- Texto «Misión, visión y objetivos» -->
      <div class="admin-form-group">
        <label class="admin-form-label" for="mision-texto">Texto «Misión, visión y objetivos»</label>
        <textarea id="mision-texto" 
                  name="mision_texto" 
                  class="admin-form-textarea" 
                  rows="4" 
                  placeholder="Texto sobre la misión, visión y objetivos..."
                  required><?= e($misionTexto) ?></textarea>
      </div>

      <!-- Botón Guardar cambios -->
      <div style="margin-top: 20px;">
        <button type="submit" class="admin-btn-submit" style="width: auto; padding: 10px 24px;">
          Guardar cambios
        </button>
      </div>
    </form>
  </div>
</div>
