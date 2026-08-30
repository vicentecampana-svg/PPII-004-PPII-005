<?php

declare(strict_types=1);

/**
 * @var array $usersList
 * @var array $rolesList
 */
$usersList ??= [];
$rolesList ??= [];

// Roles canónicos a mostrar en las tarjetas según la maqueta exacta
$standardRoles = [
    'admin'     => 'Administrador',
    'editor'    => 'Editor',
    'redactor'  => 'Redactor',
    'invitado'  => 'Invitado',
];
?>

<div class="admin-users-wrapper">
  <div class="admin-tab-header" style="margin-bottom: 20px;">
    <h2 class="admin-tab-content-title" style="margin-bottom: 4px;">Usuarios y roles</h2>
    <p class="admin-tab-subtitle" style="font-size: 0.875rem; color: var(--muted-foreground); margin: 0;">
      Activa o desactiva los roles de cada cuenta. Un usuario puede tener más de un rol.
    </p>
  </div>

  <?php if (empty($usersList)): ?>
    <div class="admin-empty-state">
      <p>No hay usuarios registrados actualmente.</p>
    </div>
  <?php else: ?>
    <div class="admin-users-list">
      <?php foreach ($usersList as $u): ?>
        <?php 
          $userRole = strtolower((string) ($u['role_name'] ?? ''));
          // Normalizar si es superadmin a admin para propósitos visuales
          $isSuper = ($userRole === 'superadmin');
        ?>
        <article class="admin-user-card">
          <div class="admin-user-email">
            <strong><?= e($u['email']) ?></strong>
            <?php if (!empty($u['username']) && $u['username'] !== $u['email']): ?>
              <span style="font-size: 0.8rem; color: var(--muted-foreground); margin-left: 8px;">(<?= e($u['username']) ?>)</span>
            <?php endif; ?>
          </div>

          <div class="admin-user-roles-row">
            <?php foreach ($standardRoles as $rKey => $rLabel): ?>
              <?php 
                $isActive = ($rKey === 'admin' && ($userRole === 'admin' || $userRole === 'superadmin'))
                         || ($rKey === 'editor' && $userRole === 'editor')
                         || ($rKey === 'redactor' && $userRole === 'redactor')
                         || ($rKey === 'invitado' && !in_array($userRole, ['admin', 'superadmin', 'editor', 'redactor'], true));
              ?>
              <form method="post" action="/admin/usuarios/role" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                <input type="hidden" name="role_key" value="<?= e($rKey) ?>">
                <button type="submit" 
                        class="admin-role-btn<?= $isActive ? ' active' : '' ?>"
                        title="Asignar rol <?= e($rLabel) ?> a <?= e($u['email']) ?>">
                  <?= e($rLabel) ?>
                </button>
              </form>
            <?php endforeach; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
