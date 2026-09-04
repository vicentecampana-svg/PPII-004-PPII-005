<?php

declare(strict_types=1);

/**
 * @var string $csrfToken
 * @var array $errors
 * @var string|null $success
 */
$errors ??= [];
?>
<section class="login-section">
  <form class="login-card" method="post" action="/cambiar-password" novalidate>
    <h1>Cambiar Contraseña</h1>

    <?php if (!empty($errors['general'])): ?>
      <p class="form-error" role="alert" aria-live="assertive"><?= e($errors['general']) ?></p>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <p role="status" aria-live="polite" style="margin-top: 16px; padding: 10px 12px; border-radius: var(--radius); background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: #15803d; font-size: 0.8125rem; font-weight: 600;">
        <?= e($success) ?>
      </p>
    <?php endif; ?>

    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <div class="field">
      <label for="current_password">Contraseña actual</label>
      <input
        type="password" id="current_password" name="current_password"
        placeholder="Tu contraseña actual" maxlength="120"
        autocomplete="current-password"
        aria-required="true"
        <?= !empty($errors['current_password']) ? 'aria-invalid="true" aria-describedby="current-password-error"' : '' ?>
        required>
      <?php if (!empty($errors['current_password'])): ?>
        <p class="field-error" id="current-password-error" role="alert"><?= e($errors['current_password']) ?></p>
      <?php endif; ?>
    </div>

    <div class="field">
      <label for="new_password">Nueva contraseña</label>
      <input
        type="password" id="new_password" name="new_password"
        placeholder="Mínimo 6 caracteres" maxlength="120"
        autocomplete="new-password"
        aria-required="true"
        <?= !empty($errors['new_password']) ? 'aria-invalid="true" aria-describedby="new-password-error"' : '' ?>
        required>
      <?php if (!empty($errors['new_password'])): ?>
        <p class="field-error" id="new-password-error" role="alert"><?= e($errors['new_password']) ?></p>
      <?php endif; ?>
    </div>

    <div class="field">
      <label for="confirm_password">Confirmar nueva contraseña</label>
      <input
        type="password" id="confirm_password" name="confirm_password"
        placeholder="Repite la nueva contraseña" maxlength="120"
        autocomplete="new-password"
        aria-required="true"
        <?= !empty($errors['confirm_password']) ? 'aria-invalid="true" aria-describedby="confirm-password-error"' : '' ?>
        required>
      <?php if (!empty($errors['confirm_password'])): ?>
        <p class="field-error" id="confirm-password-error" role="alert"><?= e($errors['confirm_password']) ?></p>
      <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-destructive">Guardar contraseña</button>
  </form>
</section>
