<?php

declare(strict_types=1);

/**
 * @var array $user
 * @var string $activeTab
 * @var array $projects
 * @var array|null $editingProject
 * @var string|null $flashSuccess
 * @var string|null $flashError
 */
$user ??= authUser() ?? [];
$roleName = (string) ($user['role_name'] ?? 'Usuario');
$projects ??= [];
$editingProject ??= null;
$flashSuccess ??= null;
$flashError ??= null;

// Definición de las pestañas del panel según la maqueta exacta
$allTabs = [
    'proyectos'      => ['label' => 'Proyectos',        'title' => 'Proyectos y servicios', 'roles' => ['superadmin', 'admin']],
    'staff'          => ['label' => 'Staff',            'title' => 'Staff',                 'roles' => ['superadmin', 'admin']],
    'noticias'       => ['label' => 'Noticias',         'title' => 'Noticias',              'roles' => ['superadmin', 'admin', 'redactor', 'editor']],
    'sobre-nosotros' => ['label' => 'Sobre nosotros',   'title' => 'Sobre nosotros',        'roles' => ['superadmin', 'admin']],
    'footer'         => ['label' => 'Footer',           'title' => 'Footer',                'roles' => ['superadmin']],
    'usuarios'       => ['label' => 'Usuarios y roles', 'title' => 'Usuarios y roles',      'roles' => ['superadmin']],
];

$roleNormalized = strtolower($roleName);

// Filtrar pestañas visibles según el rol del usuario autenticado
$visibleTabs = array_filter(
    $allTabs,
    fn(array $tab) => in_array($roleNormalized, $tab['roles'], true)
);

// Pestaña por defecto: proyectos si tiene permiso, sino la primera visible
$firstKey = !empty($visibleTabs) ? array_key_first($visibleTabs) : 'proyectos';
$activeTab = (string) ($activeTab ?? $firstKey);
if (!isset($visibleTabs[$activeTab]) && isset($visibleTabs[$firstKey])) {
    $activeTab = $firstKey;
}

// Mapeo amigable de nombres de roles para clases CSS
$roleClass = match ($roleNormalized) {
    'superadmin' => 'superadmin',
    'admin'      => 'admin',
    'redactor', 'editor' => 'redactor',
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
    </div>

    <!-- Barra de Pestañas (Tabs según Mockup) -->
    <nav class="admin-tabs" aria-label="Navegación del panel de administración">
      <?php foreach ($visibleTabs as $tabKey => $tabData): ?>
        <a href="/admin?tab=<?= e($tabKey) ?>" class="admin-tab<?= $activeTab === $tabKey ? ' active' : '' ?>">
          <?= e($tabData['label']) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <!-- Alertas y Mensajes Flash -->
    <?php if (!empty($flashSuccess)): ?>
      <div class="admin-alert admin-alert-success" role="status">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <span><?= e($flashSuccess) ?></span>
      </div>
    <?php endif; ?>

    <?php if (!empty($flashError)): ?>
      <div class="admin-alert admin-alert-error" role="alert">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <span><?= e($flashError) ?></span>
      </div>
    <?php endif; ?>

    <!-- Contenido de la pestaña activa -->
    <?php if ($activeTab === 'proyectos' && isset($visibleTabs['proyectos'])): ?>
      <?php require __DIR__ . '/admin/proyectos.php'; ?>
    <?php elseif (isset($allTabs[$activeTab])): ?>
      <div class="admin-tab-header">
        <h2 class="admin-tab-content-title"><?= e($allTabs[$activeTab]['title']) ?></h2>
      </div>

      <?php if (!isset($visibleTabs[$activeTab])): ?>
        <div class="admin-alert admin-alert-error">
          <p>No tienes los permisos requeridos para acceder a esta sección.</p>
        </div>
      <?php else: ?>
        <div class="admin-empty-state">
          <p>El contenido y operaciones CRUD de la pestaña <strong><?= e($allTabs[$activeTab]['label']) ?></strong> se implementarán en su issue correspondiente.</p>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="admin-alert admin-alert-error">
        <p>Pestaña no encontrada.</p>
      </div>
    <?php endif; ?>
  </div>
</section>
