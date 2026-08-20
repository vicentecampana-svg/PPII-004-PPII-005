<?php
/**
 * @var array $enlacesFooter (no se usa aquí, disponible para futuras vistas)
 */
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SFL ULS Lab — Software Factory Lab Universidad de La Serena</title>
  <meta name="description" content="Software Factory Lab de la Universidad de La Serena: proyectos, servicios, staff y noticias del laboratorio de desarrollo de software.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;800&family=Barlow:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="site">
  <header class="site-header">
    <div class="container header-bar">
      <a href="/" class="brand" aria-label="SFL ULS Lab — inicio">
        <img src="/assets/images/logo-sfl-color.png" alt="SFL ULS Lab" width="120" height="40" class="brand-logo">
      </a>

      <nav class="main-nav" aria-label="Navegación principal">
        <ul>
          <li><a href="/" class="active">Sobre nosotros</a></li>
          <li><a href="#proyectos">Proyectos</a></li>
          <li><a href="#staff">Staff</a></li>
          <li><a href="#noticias">Noticias</a></li>
          <li><a href="#contacto">Contáctenos</a></li>
        </ul>
      </nav>

      <div class="header-actions">
        <span class="lang-badge">ES</span>
        <a href="#" class="login-link">Iniciar sesión</a>
      </div>

      <button type="button" class="nav-toggle" aria-label="Abrir menú" aria-expanded="false" aria-controls="mobile-nav" onclick="document.getElementById('mobile-nav').classList.toggle('open'); this.setAttribute('aria-expanded', this.getAttribute('aria-expanded') === 'true' ? 'false' : 'true');">
        <span></span><span></span><span></span>
      </button>
    </div>

    <nav id="mobile-nav" class="mobile-nav" aria-label="Navegación móvil">
      <ul>
        <li><a href="/">Sobre nosotros</a></li>
        <li><a href="#proyectos">Proyectos</a></li>
        <li><a href="#staff">Staff</a></li>
        <li><a href="#noticias">Noticias</a></li>
        <li><a href="#contacto">Contáctenos</a></li>
        <li><a href="#">Iniciar sesión</a></li>
      </ul>
    </nav>
  </header>

  <main class="site-main">
