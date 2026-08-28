<?php

declare(strict_types=1);

/**
 * @var array $user
 * @var string $activeTab
 */
$user ??= authUser() ?? [];
$roleName = (string) ($user['role_name'] ?? 'Usuario');

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

      <div class="admin-header-actions">
        <a href="/logout" class="admin-btn-logout" aria-label="Cerrar sesión del panel">
          Cerrar sesión
        </a>
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

    <!-- Contenido de la pestaña activa -->
    <?php if (isset($allTabs[$activeTab])): ?>
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
