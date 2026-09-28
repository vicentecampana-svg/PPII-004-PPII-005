<?php

declare(strict_types=1);

/**
 * @var string $csrfToken
 * @var array $errors
 * @var string $email
 * @var string|null $flashSuccess  Mensaje de éxito de otras acciones (ej: contraseña restablecida).
 */
$errors       ??= [];
$email        ??= '';
$flashSuccess ??= null;
?>
<section class="login-section">
  <form class="login-card" method="post" action="/login" novalidate>
    <h1>Login</h1>

    <?php if (!empty($errors['general'])) : ?>
      <p class="form-error" role="alert" aria-live="assertive"><?= e($errors['general']) ?></p>
    <?php endif; ?>

    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <div class="field">
      <label for="email">Correo</label>
      <input
        type="email" id="email" name="email"
        placeholder="correo@userena.cl" maxlength="255"
        autocomplete="email" value="<?= e($email) ?>"
        aria-required="true"
        <?= !empty($errors['email']) ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>
        required>
      <?php if (!empty($errors['email'])) : ?>
        <p class="field-error" id="email-error" role="alert"><?= e($errors['email']) ?></p>
      <?php endif; ?>
    </div>

    <div class="field">
      <label for="password">Contraseña</label>
      <input
        type="password" id="password" name="password"
        placeholder="Contraseña" maxlength="120"
        autocomplete="current-password"
        aria-required="true"
        <?= !empty($errors['password']) ? 'aria-invalid="true" aria-describedby="password-error"' : '' ?>
        required>
      <?php if (!empty($errors['password'])) : ?>
        <p class="field-error" id="password-error" role="alert"><?= e($errors['password']) ?></p>
      <?php endif; ?>
    </div>

    <div class="field">
      <label for="captcha">Código de seguridad</label>
      <div class="captcha-box">
        <img src="/captcha" alt="Código de seguridad CAPTCHA" id="captcha-img" class="captcha-img"
             title="Haz clic para recargar el código"
             onclick="this.src='/captcha?' + Date.now()">
        <button type="button" class="captcha-reload-btn" id="captcha-reload-btn"
                aria-label="Recargar código de seguridad CAPTCHA"
                onclick="document.getElementById('captcha-img').src='/captcha?' + Date.now()">
          Recargar
        </button>
      </div>
      <input
        type="text" id="captcha" name="captcha"
        placeholder="Ingresa el código" maxlength="10"
        autocomplete="off"
        aria-required="true"
        <?= !empty($errors['captcha']) ? 'aria-invalid="true" aria-describedby="captcha-error"' : '' ?>
        required>
      <?php if (!empty($errors['captcha'])) : ?>
        <p class="field-error" id="captcha-error" role="alert"><?= e($errors['captcha']) ?></p>
      <?php endif; ?>
    </div>

    <?php if (!empty($flashSuccess)) : ?>
      <p class="alert-flash-success">
        <?= e($flashSuccess) ?>
      </p>
    <?php endif; ?>

    <button type="submit" class="btn btn-destructive btn-submit-login">Iniciar sesión</button>

    <p class="form-footer-link">
      <a href="/recuperar-password">¿Olvidaste tu contraseña?</a>
    </p>
  </form>
</section>
