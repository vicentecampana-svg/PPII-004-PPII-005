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
    <p style="font-size: 0.875rem; color: #64748b; margin-top: 0; margin-bottom: 20px;">
      Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.
    </p>

    <?php if (!empty($errors['general'])): ?>
      <p class="form-error"><?= e($errors['general']) ?></p>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <p style="margin-top: 16px; padding: 10px 12px; border-radius: var(--radius);
                background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3);
                color: #15803d; font-size: 0.8125rem;">
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
      <?php if (!empty($errors['email'])): ?>
        <p class="field-error"><?= e($errors['email']) ?></p>
      <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-destructive" style="width: 100%;">
      Enviar enlace de recuperación
    </button>

    <p style="text-align: center; margin-top: 16px; font-size: 0.875rem;">
      <a href="/login" style="color: var(--primary, #0f172a);">← Volver al login</a>
    </p>
  </form>
</section>
