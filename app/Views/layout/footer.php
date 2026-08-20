<?php
/** @var array $enlacesFooter */
$enlacesFooter ??= [];
$grupos = [];
foreach ($enlacesFooter as $enlace) {
    $grupos[$enlace['grupo']][] = $enlace;
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
        <?php foreach ($grupos as $grupo => $enlaces): ?>
          <div class="footer-group">
            <p class="footer-group-title"><?= htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8') ?></p>
            <ul>
              <?php foreach ($enlaces as $enlace): ?>
                <li><a href="<?= htmlspecialchars($enlace['url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($enlace['etiqueta'], ENT_QUOTES, 'UTF-8') ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="footer-social">
        <p>Síguenos en:</p>
        <ul>
          <li>Facebook</li>
          <li>LinkedIn</li>
          <li>Twitter</li>
          <li>Instagram</li>
          <li>YouTube</li>
        </ul>
      </div>

      <p class="footer-copy">© SFL, SPA. Ningún derecho reservado</p>
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
