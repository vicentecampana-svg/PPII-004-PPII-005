<?php
/**
 * @var string $pageTitle (opcional)
 * @var string $metaDescription (opcional)
 * @var array $enlacesFooter (no se usa aquí, disponible para futuras vistas)
 */
$pageTitle ??= 'SFL ULS Lab — Software Factory Lab Universidad de La Serena';
$metaDescription ??= 'Software Factory Lab de la Universidad de La Serena: proyectos, servicios, staff y noticias del laboratorio de desarrollo de software.';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

$isLoggedIn = authCheck();

$navLinks = [
    ['href' => '/', 'label' => 'Sobre nosotros'],
    ['href' => '/proyectos', 'label' => 'Proyectos'],
    ['href' => '#staff', 'label' => 'Staff'],
    ['href' => '#noticias', 'label' => 'Noticias'],
    ['href' => '#contacto', 'label' => 'Contáctenos'],
];

if ($isLoggedIn) {
    $navLinks[] = ['href' => '/admin', 'label' => 'Panel de administración'];
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;800&family=Barlow:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
  <?php if (!empty($extraCss)): ?>
    <?php foreach ((array) $extraCss as $css): ?>
      <link rel="stylesheet" href="<?= htmlspecialchars($css, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
  <?php endif; ?>
</head>
<body>
<div class="site">
  <header class="site-header">
    <div class="container header-bar">
      <a href="/" class="brand" aria-label="SFL ULS Lab — inicio">
        <img src="/assets/images/logo-sfl-color.png" alt="SFL ULS Lab" width="120" height="40" class="brand-logo">
      </a>

      <div class="nav-actions">
        <nav class="main-nav" aria-label="Navegación principal">
          <ul>
            <?php foreach ($navLinks as $link): ?>
              <?php 
                $isActive = ($link['href'] === $currentPath) || 
                            ($link['href'] === '/admin' && str_starts_with($currentPath, '/admin'));
              ?>
              <li><a href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>"<?= $isActive ? ' class="active"' : '' ?>><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></a></li>
            <?php endforeach; ?>
          </ul>
        </nav>

        <div class="header-actions">
          <span class="lang-badge">ES</span>
          <?php if ($isLoggedIn): ?>
            <a href="/logout" class="login-link">Cerrar sesión</a>
          <?php else: ?>
            <a href="/login" class="login-link<?= $currentPath === '/login' ? ' active' : '' ?>">Iniciar sesión</a>
          <?php endif; ?>
        </div>
      </div>

      <button type="button" class="nav-toggle" aria-label="Abrir menú" aria-expanded="false" aria-controls="mobile-nav" onclick="document.getElementById('mobile-nav').classList.toggle('open'); this.setAttribute('aria-expanded', this.getAttribute('aria-expanded') === 'true' ? 'false' : 'true');">
        <span></span><span></span><span></span>
      </button>
    </div>

    <nav id="mobile-nav" class="mobile-nav" aria-label="Navegación móvil">
      <ul>
        <?php foreach ($navLinks as $link) : ?>
          <li><a href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></a></li>
        <?php endforeach; ?>
        <?php if ($isLoggedIn): ?>
          <li><a href="/logout">Cerrar sesión</a></li>
        <?php else: ?>
          <li><a href="/login">Iniciar sesión</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <main class="site-main">
