<?php

declare(strict_types=1);

/**
 * @var array $usersList
 * @var array $rolesList
 * @var array|null $editingUser
 */
$usersList ??= [];
$rolesList ??= [];
$editingUser ??= null;

// Roles canónicos a mostrar en las tarjetas según la maqueta exacta
$standardRoles = [
    'admin'     => 'Administrador',
    'editor'    => 'Editor',
    'redactor'  => 'Redactor',
    'invitado'  => 'Invitado',
];
?>

<div class="admin-layout-grid">
  <!-- Columna izquierda: Listado de Usuarios y Roles -->
  <div class="admin-main-col">
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
          ?>
          <article class="admin-user-card">
            <div class="admin-user-header-row">
              <div class="admin-user-email">
                <strong><?= e($u['email']) ?></strong>
                <?php if (!empty($u['username']) && $u['username'] !== $u['email']): ?>
                  <span style="font-size: 0.8rem; color: var(--muted-foreground); margin-left: 8px;">(<?= e($u['username']) ?>)</span>
                <?php endif; ?>
                <?php if (empty($u['active'])): ?>
                  <span class="admin-badge-draft" style="background-color: #fee2e2; color: #991b1b;">Inactivo</span>
                <?php endif; ?>
              </div>

              <div class="admin-item-actions">
                <!-- Botón Editar Usuario -->
                <a href="/admin?tab=usuarios&edit_id=<?= (int) $u['id'] ?>" 
                   class="admin-btn-icon edit" 
                   title="Editar usuario"
                   aria-label="Editar <?= e($u['email']) ?>">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                  </svg>
                </a>

                <!-- Botón Eliminar Usuario -->
                <form method="post" action="/admin/usuarios/delete" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este usuario?');" style="margin: 0;">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                  <button type="submit" 
                          class="admin-btn-icon delete" 
                          title="Eliminar usuario"
                          aria-label="Eliminar <?= e($u['email']) ?>">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <polyline points="3 6 5 6 21 6"></polyline>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                      <line x1="10" y1="11" x2="10" y2="17"></line>
                      <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                  </button>
                </form>
              </div>
            </div>

            <!-- Botones de Rol según Mockup 6 -->
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

  <!-- Columna derecha: Formulario de Creación / Edición de Usuario (Mockup) -->
  <aside class="admin-sidebar-col">
    <div class="admin-sidebar-card">
      <div class="admin-sidebar-title">
        <span><?= $editingUser ? 'Editar registro' : 'Nuevo registro' ?></span>
        <?php if ($editingUser): ?>
          <a href="/admin?tab=usuarios" class="admin-cancel-edit" title="Cancelar edición">Cancelar</a>
        <?php else: ?>
          <span style="font-size: 1.2rem; font-weight: bold; color: var(--muted-foreground);">+</span>
        <?php endif; ?>
      </div>

      <form method="post" action="/admin/usuarios">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <?php if ($editingUser): ?>
          <input type="hidden" name="id" value="<?= (int) $editingUser['id'] ?>">
        <?php endif; ?>

        <!-- Email -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="user-email">Correo electrónico</label>
          <input type="email" 
                 id="user-email" 
                 name="email" 
                 class="admin-form-input" 
                 value="<?= e($editingUser['email'] ?? '') ?>" 
                 placeholder="usuario@ejemplo.cl"
                 required>
        </div>

        <!-- Nombre de usuario -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="user-username">Nombre de usuario</label>
          <input type="text" 
                 id="user-username" 
                 name="username" 
                 class="admin-form-input" 
                 value="<?= e($editingUser['username'] ?? '') ?>" 
                 maxlength="50" 
                 placeholder="nombre.usuario"
                 required>
        </div>

        <!-- Contraseña -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="user-password">
            Contraseña <?= $editingUser ? '<span style="font-weight: normal; color: var(--muted-foreground);">(dejar en blanco para conservar)</span>' : '' ?>
          </label>
          <input type="password" 
                 id="user-password" 
                 name="password" 
                 class="admin-form-input" 
                 minlength="12" 
                 placeholder="Mínimo 12 caracteres"
                 <?= !$editingUser ? 'required' : '' ?>>
        </div>

        <!-- Rol inicial -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="user-role">Rol asignado</label>
          <select id="user-role" name="role_id" class="admin-form-select" required>
            <?php 
              $currentRoleId = (int) ($editingUser['role_id'] ?? 4);
              $rolesMap = !empty($rolesList) ? $rolesList : [
                  ['id' => 1, 'name' => 'superadmin'],
                  ['id' => 2, 'name' => 'admin'],
                  ['id' => 3, 'name' => 'editor'],
                  ['id' => 4, 'name' => 'redactor'],
              ];
            ?>
            <?php foreach ($rolesMap as $role): ?>
              <option value="<?= (int) $role['id'] ?>" <?= $currentRoleId === (int) $role['id'] ? 'selected' : '' ?>>
                <?= e(ucfirst((string) $role['name'])) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Estado Activo -->
        <div class="admin-form-group" style="display: flex; align-items: center; gap: 8px; margin-top: 10px;">
          <input type="checkbox" 
                 id="user-active" 
                 name="active" 
                 value="1" 
                 <?= (!isset($editingUser) || !empty($editingUser['active'])) ? 'checked' : '' ?>>
          <label class="admin-form-label" for="user-active" style="margin-bottom: 0; cursor: pointer;">
            Cuenta activa
          </label>
        </div>

        <!-- Botón Submit -->
        <button type="submit" class="admin-btn-submit">
          <?= $editingUser ? 'Guardar cambios' : 'Crear' ?>
        </button>
      </form>
    </div>
  </aside>
</div>
