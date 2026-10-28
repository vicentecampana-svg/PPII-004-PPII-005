<?php

declare(strict_types=1);

/**
 * @var string|null $flashSuccess
 * @var string|null $flashError
 * @var array<string,string> $old Datos enviados, para no perderlos si el envío falla.
 */

$old = $old ?? [];
$subjects = ['Consulta general', 'Solicitud de servicio', 'Propuesta de proyecto', 'Prácticas y vinculación', 'Otro'];
$oldSubject = $old['subject'] ?? '';
?>
<section class="contacto-hero-section">
  <div class="container contacto-container">
    <div class="contacto-card">
      <h1 class="contacto-card-title">Envío de formulario de contacto</h1>

      <?php if (!empty($flashSuccess)) : ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($flashError)) : ?>
        <div class="alert alert-error" role="alert">
            <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <form action="/contacto" method="POST" class="contacto-form">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <!-- Honeypot (SEC-03): invisible para las personas, los bots lo completan -->
        <div class="contacto-form-group hp-field" aria-hidden="true">
          <label for="contact-website">Sitio web</label>
          <input type="text" id="contact-website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div class="contacto-form-group">
          <label for="contact-nombre">Nombre</label>
          <input type="text" id="contact-nombre" name="name" required placeholder="Nombre de contacto" autocomplete="name" value="<?= e($old['name'] ?? '') ?>">
        </div>

        <div class="contacto-form-group">
          <label for="contact-motivo">Motivo de envío</label>
          <select id="contact-motivo" name="subject" required>
            <option value="" disabled<?= in_array($oldSubject, $subjects, true) ? '' : ' selected' ?>>Seleccione motivo de envío</option>
            <?php foreach ($subjects as $subjectOption) : ?>
              <option value="<?= e($subjectOption) ?>"<?= $oldSubject === $subjectOption ? ' selected' : '' ?>><?= e($subjectOption) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="contacto-form-group">
          <label for="contact-correo">Correo de contacto</label>
          <input type="email" id="contact-correo" name="email" required placeholder="Correo de contacto" autocomplete="email" value="<?= e($old['email'] ?? '') ?>">
        </div>

        <div class="contacto-form-group">
          <label for="contact-telefono">Teléfono de contacto</label>
          <input type="tel" id="contact-telefono" name="phone" placeholder="Teléfono de contacto" autocomplete="tel" value="<?= e($old['phone'] ?? '') ?>">
        </div>

        <div class="contacto-form-group">
          <label for="contact-cuerpo">Cuerpo del motivo</label>
          <textarea id="contact-cuerpo" name="message" rows="5" required placeholder="Describa aquí el motivo del contacto"><?= e($old['message'] ?? '') ?></textarea>
        </div>

        <!-- SEC-10: CAPTCHA propio de la plataforma (ver CaptchaService) -->
        <div class="contacto-form-group">
          <div class="captcha-label-row">
            <label for="contact-captcha">Código de seguridad</label>
            <button type="button" class="captcha-reload-btn"
                    aria-label="Recargar código de seguridad CAPTCHA" title="Recargar código"
                    onclick="document.getElementById('contact-captcha-img').src='/captcha?' + Date.now()">
              <span aria-hidden="true">⟳</span>
            </button>
          </div>
          <div class="captcha-box">
            <img src="/captcha" alt="Código de seguridad CAPTCHA" id="contact-captcha-img" class="captcha-img"
                 title="Haz clic para recargar el código"
                 onclick="this.src='/captcha?' + Date.now()">
          </div>
          <input type="text" id="contact-captcha" name="captcha" required maxlength="10"
                 placeholder="Ingresa el código" autocomplete="off" aria-required="true">
        </div>

        <button type="submit" class="btn btn-destructive btn-submit-contacto">
          Enviar formulario
        </button>
      </form>
    </div>
  </div>
</section>
