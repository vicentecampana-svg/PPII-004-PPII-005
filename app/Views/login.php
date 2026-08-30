<?php

declare(strict_types=1);

/**
 * @var string $csrfToken
 * @var array $errors
 * @var string $email
 */
$errors ??= [];
$email ??= '';
?>
<section class="login-section">
  <form class="login-card" method="post" action="/login" novalidate>
    <h1>Login</h1>

    <?php if (!empty($errors['general'])): ?>
      <p class="form-error"><?= e($errors['general']) ?></p>
    <?php endif; ?>

    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <div class="field">
      <label for="email">Correo</label>
      <input
        type="email" id="email" name="email"
        placeholder="correo@userena.cl" maxlength="255"
        autocomplete="email" value="<?= e($email) ?>" required>
      <?php if (!empty($errors['email'])): ?>
        <p class="field-error"><?= e($errors['email']) ?></p>
      <?php endif; ?>
    </div>

    <div class="field">
      <label for="password">Contraseña</label>
      <input
        type="password" id="password" name="password"
        placeholder="Contraseña" maxlength="120"
        autocomplete="current-password" required>
      <?php if (!empty($errors['password'])): ?>
        <p class="field-error"><?= e($errors['password']) ?></p>
      <?php endif; ?>
    </div>

    <div class="field">
      <label for="captcha">Código de seguridad</label>
      <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 8px;">
        <img src="/captcha" alt="CAPTCHA" id="captcha-img"
             style="height: 44px; border-radius: var(--radius); cursor: pointer;"
             title="Haz clic para recargar el código"
             onclick="this.src='/captcha?' + Date.now()">
        <button type="button" class="btn" style="padding: 0 12px; height: 44px; background: #e2e8f0; color: #334155; border-radius: var(--radius); font-size: 0.85rem;"
                onclick="document.getElementById('captcha-img').src='/captcha?' + Date.now()">
          Recargar
        </button>
      </div>
      <input
        type="text" id="captcha" name="captcha"
        placeholder="Ingresa el código" maxlength="10"
        autocomplete="off" required>
      <?php if (!empty($errors['captcha'])): ?>
        <p class="field-error"><?= e($errors['captcha']) ?></p>
      <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-destructive">Iniciar sesión</button>
  </form>
</section>
