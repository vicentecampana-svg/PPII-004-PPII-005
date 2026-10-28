<?php

declare(strict_types=1);

/**
 * @var string $csrfToken
 * @var string $token       Token plano de la URL (incluido en el action del form).
 * @var array  $errors
 */
$errors ??= [];
$token  ??= '';
?>
<section class="login-section">
  <form class="login-card" method="post"
        action="/restablecer-password/<?= e(urlencode($token)) ?>" novalidate
        <?= \App\Services\PasswordPolicy::clientConfigAttribute() ?>>
    <h1>Nueva contraseña</h1>
    <p class="auth-instructions">
      Elige una contraseña segura. <?= e(\App\Services\PasswordPolicy::hint()) ?>
    </p>

    <?php if (!empty($errors['general'])) : ?>
      <p class="form-error"><?= e($errors['general']) ?></p>
    <?php endif; ?>

    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <div class="field">
      <label for="new_password">Nueva contraseña</label>
      <input
        type="password" id="new_password" name="new_password"
        placeholder="Mínimo 12 caracteres" maxlength="120" minlength="12"
        autocomplete="new-password"
        data-password-new data-error-id="new-password-error"
        <?= !empty($errors['new_password']) ? 'aria-invalid="true" aria-describedby="new-password-error"' : '' ?>
        required>
      <p class="field-error" id="new-password-error"<?= empty($errors['new_password']) ? ' hidden' : '' ?>><?= e($errors['new_password'] ?? '') ?></p>
    </div>

    <div class="field">
      <label for="confirm_password">Confirmar contraseña</label>
      <input
        type="password" id="confirm_password" name="confirm_password"
        placeholder="Repite la nueva contraseña" maxlength="120"
        autocomplete="new-password"
        data-password-confirm="new_password" data-error-id="confirm-password-error"
        <?= !empty($errors['confirm_password']) ? 'aria-invalid="true" aria-describedby="confirm-password-error"' : '' ?>
        required>
      <p class="field-error" id="confirm-password-error"<?= empty($errors['confirm_password']) ? ' hidden' : '' ?>><?= e($errors['confirm_password'] ?? '') ?></p>
    </div>

    <button type="submit" class="btn btn-destructive btn-full-width">
      Guardar nueva contraseña
    </button>
  </form>
</section>
<script src="<?= e(assetUrl('/assets/js/password-policy.js')) ?>" defer></script>
