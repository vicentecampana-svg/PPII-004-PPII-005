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

    <button type="submit" class="btn btn-destructive">Iniciar sesión</button>
  </form>
</section>
