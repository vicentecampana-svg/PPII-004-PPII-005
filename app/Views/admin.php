<?php

declare(strict_types=1);

/**
 * @var array $user
 * @var array $stats
 * @var string $activeTab
 */
$user ??= authUser() ?? [];
$stats ??= [];
$activeTab = (string) ($activeTab ?? 'inicio');
$roleName = (string) ($user['role_name'] ?? 'Usuario');

// Definición de las pestañas del panel según la maqueta y la arquitectura del sistema
$allTabs = [
    'proyectos'      => ['label' => 'Proyectos',        'roles' => ['SuperAdmin', 'Admin']],
    'staff'          => ['label' => 'Staff',            'roles' => ['SuperAdmin', 'Admin']],
    'noticias'       => ['label' => 'Noticias',         'roles' => ['SuperAdmin', 'Admin', 'Redactor']],
    'sobre-nosotros' => ['label' => 'Sobre nosotros',   'roles' => ['SuperAdmin', 'Admin']],
    'footer'         => ['label' => 'Footer',           'roles' => ['SuperAdmin']],
    'usuarios'       => ['label' => 'Usuarios y roles', 'roles' => ['SuperAdmin']],
];

// Filtrar pestañas visibles según el rol del usuario autenticado
$visibleTabs = array_filter(
    $allTabs,
    fn(array $tab) => in_array($roleName, $tab['roles'], true)
);

// Mapeo amigable de nombres de roles para clases CSS
$roleClass = match (strtolower($roleName)) {
    'superadmin' => 'superadmin',
    'admin'      => 'admin',
    'redactor'   => 'redactor',
    default      => '',
};
?>
<section class="admin-section">
  <div class="container">
    <!-- Encabezado del Panel -->
    <div class="admin-header">
      <div class="admin-header-title">
        <h1>Panel de administración</h1>
        <div class="admin-header-subtitle">
          <span>Bienvenido/a, <strong><?= e($user['username'] ?? 'Usuario') ?></strong></span>
          <span class="admin-role-badge <?= $roleClass ?>"><?= e($roleName) ?></span>
        </div>
      </div>

      <div class="admin-header-actions">
        <a href="/logout" class="admin-btn-logout" aria-label="Cerrar sesión del panel">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
          </svg>
          <span>Cerrar sesión</span>
        </a>
      </div>
    </div>

    <!-- Barra de Pestañas (Tabs) -->
    <nav class="admin-tabs" aria-label="Navegación del panel de administración">
      <a href="/admin" class="admin-tab<?= $activeTab === 'inicio' ? ' active' : '' ?>">
        Inicio
      </a>
      <?php foreach ($visibleTabs as $tabKey => $tabData): ?>
        <a href="/admin?tab=<?= e($tabKey) ?>" class="admin-tab<?= $activeTab === $tabKey ? ' active' : '' ?>">
          <?= e($tabData['label']) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <!-- Contenido de la vista según la pestaña activa -->
    <?php if ($activeTab === 'inicio'): ?>
      <div class="admin-welcome-card">
        <div class="admin-welcome-text">
          <h2>Gestión de Contenidos del Software Factory Lab</h2>
          <p>Selecciona una pestaña superior para consultar, crear o editar registros del sistema según tus permisos.</p>
        </div>
      </div>

      <!-- Resumen de Métricas -->
      <div class="admin-stats-grid">
        <a href="/admin?tab=proyectos" class="admin-stat-card">
          <span class="admin-stat-label">Proyectos</span>
          <span class="admin-stat-value"><?= (int) ($stats['proyectos'] ?? 0) ?></span>
          <span class="admin-stat-link">Gestionar proyectos &rarr;</span>
        </a>

        <a href="/admin?tab=servicios" class="admin-stat-card">
          <span class="admin-stat-label">Servicios</span>
          <span class="admin-stat-value"><?= (int) ($stats['servicios'] ?? 0) ?></span>
          <span class="admin-stat-link">Gestionar servicios &rarr;</span>
        </a>

        <a href="/admin?tab=staff" class="admin-stat-card">
          <span class="admin-stat-label">Staff</span>
          <span class="admin-stat-value"><?= (int) ($stats['staff'] ?? 0) ?></span>
          <span class="admin-stat-link">Gestionar equipo &rarr;</span>
        </a>

        <a href="/admin?tab=noticias" class="admin-stat-card">
          <span class="admin-stat-label">Noticias</span>
          <span class="admin-stat-value"><?= (int) ($stats['noticias'] ?? 0) ?></span>
          <span class="admin-stat-link">Gestionar noticias &rarr;</span>
        </a>

        <a href="/admin" class="admin-stat-card">
          <span class="admin-stat-label">Consultas</span>
          <span class="admin-stat-value"><?= (int) ($stats['consultas'] ?? 0) ?></span>
          <span class="admin-stat-link">Ver consultas &rarr;</span>
        </a>

        <?php if ($roleName === 'SuperAdmin'): ?>
          <a href="/admin?tab=usuarios" class="admin-stat-card">
            <span class="admin-stat-label">Usuarios</span>
            <span class="admin-stat-value"><?= (int) ($stats['usuarios'] ?? 0) ?></span>
            <span class="admin-stat-link">Gestionar usuarios &rarr;</span>
          </a>
        <?php endif; ?>
      </div>

    <?php elseif (isset($allTabs[$activeTab])): ?>
      <!-- Contenedor base preparado para cada pestaña -->
      <div class="admin-tab-header">
        <h2 class="admin-tab-content-title"><?= e($allTabs[$activeTab]['label']) ?></h2>
      </div>

      <?php if (!isset($visibleTabs[$activeTab])): ?>
        <div class="admin-alert admin-alert-error">
          <p>No tienes los permisos requeridos para acceder a esta sección.</p>
        </div>
      <?php else: ?>
        <div class="admin-empty-state">
          <p>El módulo de <strong><?= e($allTabs[$activeTab]['label']) ?></strong> se implementará en su issue correspondiente.</p>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="admin-alert admin-alert-error">
        <p>Pestaña no encontrada.</p>
      </div>
    <?php endif; ?>

  </div>
</section>
