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

  <footer class="site-footer">
    <div class="container">
      <p class="footer-eyebrow">Software Factory Lab</p>
      <div class="footer-brand">
        <img src="/assets/images/logo-sfl.png" alt="SFL ULS Lab" width="150" height="52" class="footer-logo">
      </div>

      <div class="footer-links">
        <div class="footer-group">
          <p class="footer-group-title">Contacto</p>
          <ul>
            <li><?= htmlspecialchars($contacto['address'], ENT_QUOTES, 'UTF-8') ?></li>
            <li><a href="mailto:<?= htmlspecialchars($contacto['email'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($contacto['email'], ENT_QUOTES, 'UTF-8') ?></a></li>
          </ul>
        </div>

        <?php foreach ($grupos as $grupo => $enlaces) : ?>
          <div class="footer-group">
            <p class="footer-group-title"><?= htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8') ?></p>
            <ul>
              <?php foreach ($enlaces as $enlace) : ?>
                <li><a href="<?= htmlspecialchars($enlace['url'], ENT_QUOTES, 'UTF-8') ?>"<?= $enlace['url'] === $currentPath ? ' class="active"' : '' ?>><?= htmlspecialchars($enlace['etiqueta'], ENT_QUOTES, 'UTF-8') ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="footer-social">
        <p>Síguenos en:</p>
        <ul>
          <li>
            <a href="<?= htmlspecialchars($contacto['social_linkedin'] ?? '#', ENT_QUOTES, 'UTF-8') ?>" aria-label="LinkedIn" target="_blank" rel="noopener noreferrer">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/></svg>
            </a>
          </li>
          <li>
            <a href="<?= htmlspecialchars($contacto['social_twitter'] ?? '#', ENT_QUOTES, 'UTF-8') ?>" aria-label="X (Twitter)" target="_blank" rel="noopener noreferrer">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/></svg>
            </a>
          </li>
          <li>
            <a href="<?= htmlspecialchars($contacto['social_instagram'] ?? '#', ENT_QUOTES, 'UTF-8') ?>" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
            </a>
          </li>
        </ul>
      </div>

      <div class="footer-bottom">
        <p class="footer-copy"><?= htmlspecialchars($contacto['copyright_text'] ?? '© SFL. Todos los derechos reservados', ENT_QUOTES, 'UTF-8') ?></p>
        <a href="/credits" class="footer-credits-link">Página creada por equipo Charlie</a>
      </div>
    </div>
  </footer>
</div>
<script>
  document.addEventListener('click', function (event) {
    var mobileNav = document.getElementById('mobile-nav');
    if (!mobileNav || !mobileNav.classList.contains('open')) return;
    if (event.target.closest('.mobile-nav') || event.target.closest('.nav-toggle')) return;
    mobileNav.classList.remove('open');
    document.querySelector('.nav-toggle')?.setAttribute('aria-expanded', 'false');
  });
</script>
</body>
</html>
