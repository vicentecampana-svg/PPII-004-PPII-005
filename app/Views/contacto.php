<?php

declare(strict_types=1);

/**
 * @var string|null $flashSuccess
 * @var string|null $flashError
 */
?>
<section class="contacto-hero-section">
  <div class="container contacto-container">
    <div class="contacto-card">
      <h1 class="contacto-card-title">Envío de formulario de contacto</h1>

      <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success" role="alert">
          <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($flashError)): ?>
        <div class="alert alert-error" role="alert">
          <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <form action="/contacto" method="POST" class="contacto-form">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="contacto-form-group">
          <label for="contact-nombre">Nombre</label>
          <input type="text" id="contact-nombre" name="name" required placeholder="Nombre de contacto" autocomplete="name">
        </div>

        <div class="contacto-form-group">
          <label for="contact-motivo">Motivo de envío</label>
          <select id="contact-motivo" name="subject" required>
            <option value="" disabled selected>Seleccione motivo de envío</option>
            <option value="Consulta general">Consulta general</option>
            <option value="Solicitud de servicio">Solicitud de servicio</option>
            <option value="Propuesta de proyecto">Propuesta de proyecto</option>
            <option value="Prácticas y vinculación">Prácticas y vinculación</option>
            <option value="Otro">Otro</option>
          </select>
        </div>

        <div class="contacto-form-group">
          <label for="contact-correo">Correo de contacto</label>
          <input type="email" id="contact-correo" name="email" required placeholder="Correo de contacto" autocomplete="email">
        </div>

        <div class="contacto-form-group">
          <label for="contact-telefono">Teléfono de contacto</label>
          <input type="tel" id="contact-telefono" name="phone" placeholder="Teléfono de contacto" autocomplete="tel">
        </div>

        <div class="contacto-form-group">
          <label for="contact-cuerpo">Cuerpo del motivo</label>
          <textarea id="contact-cuerpo" name="message" rows="5" required placeholder="Describa aquí el motivo del contacto"></textarea>
        </div>

        <button type="submit" class="btn btn-destructive btn-submit-contacto">
          Enviar formulario
        </button>
      </form>
    </div>
  </div>
</section>
