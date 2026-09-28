<?php

declare(strict_types=1);

/**
 * @var string   $csrfToken
 * @var array    $errors
 * @var string|null $success
 * @var string   $email
 * @var int      $retryAfterSeconds
 */
$errors  ??= [];
$success ??= null;
$email   ??= '';
$retryAfterSeconds ??= 0;
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

    <button type="submit" class="btn btn-destructive btn-full-width"
            id="recovery-submit-btn" <?= $retryAfterSeconds > 0 ? 'disabled' : '' ?>>
      <span id="recovery-submit-label"><?= $retryAfterSeconds > 0 ? 'Espera para reenviar' : 'Enviar enlace de recuperación' ?></span>
    </button>

    <p id="recovery-countdown" class="recovery-countdown"
       <?= $retryAfterSeconds > 0 ? '' : 'hidden' ?>>
      Podrás solicitar un nuevo enlace en <strong id="recovery-countdown-value"><?= (int) $retryAfterSeconds ?></strong> segundos.
    </p>

    <?php if ($retryAfterSeconds > 0) : ?>
    <script>
      (function () {
        var remaining = <?= (int) $retryAfterSeconds ?>;
        var btn = document.getElementById('recovery-submit-btn');
        var label = document.getElementById('recovery-submit-label');
        var countdown = document.getElementById('recovery-countdown');
        var value = document.getElementById('recovery-countdown-value');

        var timer = setInterval(function () {
          remaining -= 1;
          if (remaining <= 0) {
            clearInterval(timer);
            btn.disabled = false;
            label.textContent = 'Enviar enlace de recuperación';
            countdown.hidden = true;
            return;
          }
          value.textContent = remaining;
        }, 1000);
      })();
    </script>
    <?php endif; ?>

    <p class="form-footer-link">
      <a href="/login">← Volver al login</a>
    </p>
  </form>
</section>
