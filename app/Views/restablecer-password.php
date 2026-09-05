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
        action="/restablecer-password/<?= e(urlencode($token)) ?>" novalidate>
    <h1>Nueva contraseña</h1>
    <p style="font-size: 0.875rem; color: #64748b; margin-top: 0; margin-bottom: 20px;">
      Elige una contraseña segura de al menos 12 caracteres.
    </p>

    <?php if (!empty($errors['general'])): ?>
      <p class="form-error"><?= e($errors['general']) ?></p>
    <?php endif; ?>

    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <div class="field">
      <label for="new_password">Nueva contraseña</label>
      <input
        type="password" id="new_password" name="new_password"
        placeholder="Mínimo 12 caracteres" maxlength="120"
        autocomplete="new-password" required>
      <?php if (!empty($errors['new_password'])): ?>
        <p class="field-error"><?= e($errors['new_password']) ?></p>
      <?php endif; ?>
    </div>

    <div class="field">
      <label for="confirm_password">Confirmar contraseña</label>
      <input
        type="password" id="confirm_password" name="confirm_password"
        placeholder="Repite la nueva contraseña" maxlength="120"
        autocomplete="new-password" required>
      <?php if (!empty($errors['confirm_password'])): ?>
        <p class="field-error"><?= e($errors['confirm_password']) ?></p>
      <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-destructive" style="width: 100%;">
      Guardar nueva contraseña
    </button>
  </form>
</section>
