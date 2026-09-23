<?php

declare(strict_types=1);

/**
 * @var string   $csrfToken
 * @var array    $errors
 * @var string|null $success
 * @var string   $email
 */
$errors  ??= [];
$success ??= null;
$email   ??= '';
?>
<section class="login-section">
  <form class="login-card" method="post" action="/recuperar-password" novalidate>
    <h1>Recuperar contraseña</h1>
    <p class="auth-instructions">
      Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.
    </p>

    <?php if (!empty($errors['general'])) : ?>
      <p class="form-error"><?= e($errors['general']) ?></p>
    <?php endif; ?>

    <?php if (!empty($success)) : ?>
      <p class="alert-flash-success">
        <?= e($success) ?>
      </p>
    <?php endif; ?>

    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <div class="field">
      <label for="email">Correo electrónico</label>
      <input
        type="email" id="email" name="email"
        placeholder="correo@userena.cl" maxlength="255"
        autocomplete="email" value="<?= e($email) ?>" required>
      <?php if (!empty($errors['email'])) : ?>
        <p class="field-error"><?= e($errors['email']) ?></p>
      <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-destructive btn-full-width">
      Enviar enlace de recuperación
    </button>

    <p class="form-footer-link">
      <a href="/login">← Volver al login</a>
    </p>
  </form>
</section>
