<?php
/**
 * @var array $miembros
 * @var string|null $flashSuccess
 * @var string|null $flashError
 */
$miembros ??= [];
?>

<section class="credits-section">
  <div class="container">
    <div class="credits-header">
      <h1 class="credits-title">Créditos</h1>
    </div>

    <?php if (!empty($flashSuccess)): ?>
      <div class="alert alert-success" role="alert">
        <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($flashError)): ?>
      <div class="alert alert-error" role="alert">
        <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <div class="credits-grid">
      <?php foreach ($miembros as $miembro): ?>
        <div class="credits-card">
          <p class="credits-role"><?= htmlspecialchars($miembro['role'], ENT_QUOTES, 'UTF-8') ?></p>
          <p class="credits-name"><?= htmlspecialchars($miembro['name'], ENT_QUOTES, 'UTF-8') ?></p>
          <button
            type="button"
            class="btn-contact-member"
            data-member-key="<?= htmlspecialchars($miembro['key'], ENT_QUOTES, 'UTF-8') ?>"
            data-member-name="<?= htmlspecialchars($miembro['name'], ENT_QUOTES, 'UTF-8') ?>"
            data-member-role="<?= htmlspecialchars($miembro['role'], ENT_QUOTES, 'UTF-8') ?>"
            aria-label="Contactar a <?= htmlspecialchars($miembro['name'], ENT_QUOTES, 'UTF-8') ?>"
          >
            <span>Contactar</span>
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <rect width="20" height="16" x="2" y="4" rx="2"/>
              <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
            </svg>
          </button>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="credits-easter-egg" aria-hidden="true">
      <img src="/assets/images/egg.png" alt="" width="22" height="22" class="easter-egg-img" title="¡Easter egg!">
    </div>
  </div>
</section>

<!-- Modal de Contacto -->
<div id="credits-modal" class="credits-modal-overlay" aria-hidden="true" role="dialog" aria-labelledby="modal-title">
  <div class="credits-modal-card">
    <div class="credits-modal-header">
      <div>
        <h2 id="modal-title" class="credits-modal-title">Contactar integrante</h2>
        <p id="modal-subtitle" class="credits-modal-subtitle"></p>
      </div>
      <button type="button" class="credits-modal-close" id="modal-close-btn" aria-label="Cerrar ventana modal">&times;</button>
    </div>

    <div id="modal-alert-success" class="alert alert-success" style="display: none;" role="alert"></div>
    <div id="modal-alert-error" class="alert alert-error" style="display: none;" role="alert"></div>

    <form id="credits-contact-form" action="/credits" method="POST" class="credits-form">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="member_key" id="modal-member-key" value="">

      <div class="form-group">
        <label for="contact-name">Tu nombre <span class="required">*</span></label>
        <input type="text" id="contact-name" name="name" required minlength="2" maxlength="150" placeholder="Ej. Juan Pérez" autocomplete="name">
      </div>

      <div class="form-group">
        <label for="contact-email">Tu correo electrónico <span class="required">*</span></label>
        <input type="email" id="contact-email" name="email" required maxlength="150" placeholder="nombre@correo.com" autocomplete="email">
      </div>

      <div class="form-group">
        <label for="contact-message">Mensaje <span class="required">*</span></label>
        <textarea id="contact-message" name="message" rows="4" required minlength="5" maxlength="5000" placeholder="Escribe tu mensaje o consulta..."></textarea>
      </div>

      <div class="credits-modal-actions">
        <button type="button" class="btn btn-secondary" id="modal-cancel-btn">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="modal-submit-btn">
          <span id="submit-spinner" class="spinner" style="display: none;"></span>
          <span id="submit-text">Enviar mensaje</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  (function () {
    const modal = document.getElementById('credits-modal');
    const closeBtn = document.getElementById('modal-close-btn');
    const cancelBtn = document.getElementById('modal-cancel-btn');
    const form = document.getElementById('credits-contact-form');
    const memberKeyInput = document.getElementById('modal-member-key');
    const titleEl = document.getElementById('modal-title');
    const subtitleEl = document.getElementById('modal-subtitle');
    const alertSuccess = document.getElementById('modal-alert-success');
    const alertError = document.getElementById('modal-alert-error');
    const submitBtn = document.getElementById('modal-submit-btn');
    const submitText = document.getElementById('submit-text');
    const submitSpinner = document.getElementById('submit-spinner');

    function openModal(memberKey, memberName, memberRole) {
      memberKeyInput.value = memberKey;
      titleEl.textContent = 'Contactar a ' + memberName;
      subtitleEl.textContent = memberRole;
      alertSuccess.style.display = 'none';
      alertError.style.display = 'none';
      form.reset();
      memberKeyInput.value = memberKey;
      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      setTimeout(() => document.getElementById('contact-name')?.focus(), 50);
    }

    function closeModal() {
      modal.classList.remove('open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      alertSuccess.style.display = 'none';
      alertError.style.display = 'none';
    }

    document.querySelectorAll('.btn-contact-member').forEach(btn => {
      btn.addEventListener('click', function () {
        const key = this.dataset.memberKey;
        const name = this.dataset.memberName;
        const role = this.dataset.memberRole;
        openModal(key, name, role);
      });
    });

    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);

    modal.addEventListener('click', function (e) {
      if (e.target === modal) {
        closeModal();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal.classList.contains('open')) {
        closeModal();
      }
    });

    form.addEventListener('submit', async function (e) {
      e.preventDefault();

      const memberKey = memberKeyInput.value;
      const name = document.getElementById('contact-name').value.trim();
      const email = document.getElementById('contact-email').value.trim();
      const message = document.getElementById('contact-message').value.trim();

      alertSuccess.style.display = 'none';
      alertError.style.display = 'none';

      submitBtn.disabled = true;
      submitText.textContent = 'Enviando...';
      if (submitSpinner) submitSpinner.style.display = 'inline-block';

      try {
        const response = await fetch('/api/credits/contact', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            member_key: memberKey,
            name: name,
            email: email,
            message: message
          })
        });

        const data = await response.json();

        if (response.ok && data.success) {
          alertSuccess.textContent = data.data?.message || '¡Tu mensaje ha sido enviado exitosamente!';
          alertSuccess.style.display = 'block';
          form.reset();
          setTimeout(() => {
            closeModal();
          }, 2000);
        } else {
          const errMsg = data.error?.message || 'No se pudo enviar el mensaje. Verifica los datos.';
          alertError.textContent = errMsg;
          alertError.style.display = 'block';
        }
      } catch (err) {
        alertError.textContent = 'Error de conexión con el servidor. Intenta más tarde.';
        alertError.style.display = 'block';
      } finally {
        submitBtn.disabled = false;
        submitText.textContent = 'Enviar mensaje';
        if (submitSpinner) submitSpinner.style.display = 'none';
      }
    });
  })();
</script>
