<?php
/**
 * @var array $enlacesFooter
 * @var array $contacto
 */
$enlacesFooter ??= [];
$contacto ??= [];
$currentPath ??= parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$grupos = [];
foreach ($enlacesFooter as $enlace) {
    $grupos[$enlace['grupo']][] = $enlace;
}

if ($grupos === []) {
    $grupos = [
        'Contenido' => [
            ['url' => '/noticias', 'etiqueta' => 'Noticias'],
            ['url' => '/#contacto', 'etiqueta' => 'Contacto'],
            ['url' => '/login', 'etiqueta' => 'Iniciar sesión'],
        ],
        'Sitio' => [
            ['url' => '/', 'etiqueta' => 'Inicio'],
            ['url' => '/proyectos', 'etiqueta' => 'Proyectos'],
            ['url' => '/#staff', 'etiqueta' => 'Staff'],
        ],
    ];
}
?>
  </main>

  <footer class="site-footer" role="contentinfo">
    <div class="container">
      <p class="footer-eyebrow">Software Factory Lab</p>

      <div class="footer-links">
        <div class="footer-group">
          <p class="footer-group-title">Contacto</p>
          <ul>
            <li><?= htmlspecialchars($contacto['address'] ?? 'La Serena, Chile', ENT_QUOTES, 'UTF-8') ?></li>
            <li><a href="mailto:<?= htmlspecialchars($contacto['email'] ?? 'contacto@sfl.uls.cl', ENT_QUOTES, 'UTF-8') ?>" aria-label="Enviar correo a <?= htmlspecialchars($contacto['email'] ?? 'contacto@sfl.uls.cl', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($contacto['email'] ?? 'contacto@sfl.uls.cl', ENT_QUOTES, 'UTF-8') ?></a></li>
          </ul>
        </div>

        <?php foreach ($grupos as $grupo => $enlaces) : ?>
          <div class="footer-group">
            <p class="footer-group-title"><?= htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8') ?></p>
            <ul>
              <?php foreach ($enlaces as $enlace) : ?>
                <li><a href="<?= htmlspecialchars($enlace['url'], ENT_QUOTES, 'UTF-8') ?>"<?= $enlace['url'] === $currentPath ? ' class="active" aria-current="page"' : '' ?>><?= htmlspecialchars($enlace['etiqueta'], ENT_QUOTES, 'UTF-8') ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="footer-social">
        <p>Síguenos en:</p>
        <ul>
          <li>
            <a href="<?= htmlspecialchars($contacto['social_linkedin'] ?? '#', ENT_QUOTES, 'UTF-8') ?>" aria-label="Software Factory Lab en LinkedIn (abre en pestaña nueva)" target="_blank" rel="noopener noreferrer">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/></svg>
            </a>
          </li>
          <li>
            <a href="<?= htmlspecialchars($contacto['social_twitter'] ?? '#', ENT_QUOTES, 'UTF-8') ?>" aria-label="Software Factory Lab en X / Twitter (abre en pestaña nueva)" target="_blank" rel="noopener noreferrer">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/></svg>
            </a>
          </li>
          <li>
            <a href="<?= htmlspecialchars($contacto['social_instagram'] ?? '#', ENT_QUOTES, 'UTF-8') ?>" aria-label="Software Factory Lab en Instagram (abre en pestaña nueva)" target="_blank" rel="noopener noreferrer">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
            </a>
          </li>
        </ul>
      </div>

      <div class="footer-bottom">
        <p class="footer-copy"><?= htmlspecialchars($contacto['copyright_text'] ?? '© SFL. Todos los derechos reservados', ENT_QUOTES, 'UTF-8') ?></p>
        <button type="button" class="footer-legal-link" id="legal-reopen-btn">Aviso legal</button>
        <a href="/credits" class="footer-credits-link">Página creada por equipo Charlie</a>
      </div>
    </div>
  </footer>
</div>

<div id="legal-modal" class="legal-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="legal-modal-title">
  <div class="legal-modal-card">
    <div class="legal-modal-header">
      <h2 id="legal-modal-title" class="legal-modal-title">Aviso legal</h2>
      <button type="button" class="legal-modal-close" id="legal-close-btn" aria-label="Cerrar aviso legal">&times;</button>
    </div>

    <div class="legal-modal-body">
      <h3>Tech Hub ULS</h3>
      <p>Este sitio tiene como propósito presentar la identidad corporativa del Tech Hub de la Universidad de La Serena y dar a conocer los servicios ofrecidos por sus unidades internas (entre ellas, el Software Factory Lab, ODS, Repositorio, IA Lab e IoT Lab).</p>
      <p>Todos los contenidos que conforman este sitio web —textos, logotipos, imágenes y fotografías del staff— son propiedad exclusiva del Tech Hub ULS o de terceros autorizados, y su uso no autorizado está prohibido.</p>
      <p>Al utilizar este sitio, el usuario se compromete a hacer un uso lícito y adecuado de los contenidos, absteniéndose de ingresar datos falsos o de dañar la infraestructura tecnológica del Tech Hub ULS.</p>
      <p>El Tech Hub ULS no asume responsabilidad sobre el contenido de sitios externos vinculados desde esta plataforma, ni sobre interrupciones del servicio derivadas de labores de mantenimiento.</p>

      <h3>Software Factory Lab (SFL)</h3>
      <p>El Software Factory Lab es una unidad del Tech Hub ULS dedicada a servicios de ingeniería de software y a la difusión de su portafolio de proyectos estudiantiles.</p>
      <p>Queda prohibida la ingeniería inversa o la comunicación pública de los activos de software y diseño presentados en este sitio sin el consentimiento previo y por escrito del equipo del laboratorio.</p>

      <h3>Protección de datos personales</h3>
      <p>El tratamiento de los datos personales recopilados a través de este sitio (por ejemplo, en el formulario de contacto) se rige por la Ley N° 19.628 sobre Protección de la Vida Privada y la Ley N° 21.719, garantizando a los titulares sus derechos ARCO+ (Acceso, Rectificación, Supresión, Oposición, Portabilidad y Bloqueo).</p>

      <p class="legal-modal-footnote">Plataforma desarrollada en 2026 por el equipo Charlie para la Universidad de La Serena.</p>
    </div>

    <div class="legal-modal-actions">
      <button type="button" class="btn btn-destructive" id="legal-accept-btn">Aceptar</button>
    </div>
  </div>
</div>

<script>
  (function () {
    var STORAGE_KEY = 'techhub_aviso_legal_aceptado';
    var modal = document.getElementById('legal-modal');
    var closeBtn = document.getElementById('legal-close-btn');
    var acceptBtn = document.getElementById('legal-accept-btn');
    var reopenBtn = document.getElementById('legal-reopen-btn');

    if (!modal) return;

    var hasAccepted = false;
    try {
      hasAccepted = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {
      hasAccepted = false;
    }

    function openModal(requireAcceptance) {
      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
      modal.classList.toggle('legal-modal-mandatory', !!requireAcceptance);
    }

    function closeModal() {
      modal.classList.remove('open', 'legal-modal-mandatory');
      modal.setAttribute('aria-hidden', 'true');
    }

    function accept() {
      try {
        localStorage.setItem(STORAGE_KEY, '1');
      } catch (e) {
        // localStorage no disponible (modo privado, etc.): no persiste entre visitas,
        // pero no debe impedir que el usuario cierre el aviso en esta sesión.
      }
      closeModal();
    }

    if (!hasAccepted) {
      openModal(true);
    }

    if (acceptBtn) {
      acceptBtn.addEventListener('click', accept);
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', closeModal);
    }

    if (reopenBtn) {
      reopenBtn.addEventListener('click', function () {
        openModal(false);
      });
    }

    modal.addEventListener('click', function (event) {
      if (event.target === modal && !modal.classList.contains('legal-modal-mandatory')) {
        closeModal();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && modal.classList.contains('open') && !modal.classList.contains('legal-modal-mandatory')) {
        closeModal();
      }
    });
  })();
</script>
<script>
  (function () {
    var toggleBtn = document.getElementById('nav-toggle');
    var mobileNav = document.getElementById('mobile-nav');

    if (!toggleBtn || !mobileNav) return;

    function openMobileMenu() {
      mobileNav.classList.add('open');
      mobileNav.setAttribute('aria-hidden', 'false');
      toggleBtn.setAttribute('aria-expanded', 'true');
      toggleBtn.setAttribute('aria-label', 'Cerrar menú');
    }

    function closeMobileMenu() {
      mobileNav.classList.remove('open');
      mobileNav.setAttribute('aria-hidden', 'true');
      toggleBtn.setAttribute('aria-expanded', 'false');
      toggleBtn.setAttribute('aria-label', 'Abrir menú');
    }

    toggleBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      var isOpen = mobileNav.classList.contains('open');
      if (isOpen) {
        closeMobileMenu();
      } else {
        openMobileMenu();
      }
    });

    document.addEventListener('click', function (event) {
      if (!mobileNav.classList.contains('open')) return;
      if (event.target.closest('#mobile-nav') || event.target.closest('#nav-toggle')) return;
      closeMobileMenu();
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && mobileNav.classList.contains('open')) {
        closeMobileMenu();
        toggleBtn.focus();
      }
    });

    var navLinks = mobileNav.querySelectorAll('a');
    navLinks.forEach(function (link) {
      link.addEventListener('click', function () {
        closeMobileMenu();
      });
    });
  })();
</script>
</body>
</html>
