<?php

declare(strict_types=1);

/**
 * @var array $creditsList
 * @var array|null $editingCredit
 */
$creditsList ??= [];
$editingCredit ??= null;
?>

<div class="admin-layout-grid">
  <!-- Columna izquierda: Listado de Integrantes de Créditos -->
  <div class="admin-main-col">
    <div class="admin-tab-header">
      <h2 class="admin-tab-content-title">Integrantes de créditos</h2>
    </div>

    <?php if (empty($creditsList)) : ?>
      <div class="admin-empty-state">
        <p>No hay integrantes de créditos registrados actualmente.</p>
      </div>
    <?php else : ?>
      <div class="admin-items-list">
        <?php foreach ($creditsList as $member) : ?>
          <article class="admin-item-row">
            <div class="admin-item-content">
              <div class="admin-item-thumb admin-avatar-placeholder" aria-label="<?= e($member['name']) ?>">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
              </div>
              <div class="admin-item-info">
                <div class="admin-item-title"><?= e($member['name']) ?></div>
                <div class="admin-item-desc"><?= e($member['role'] ?? 'Sin cargo asignado') ?></div>
                <?php if (!empty($member['email'])) : ?>
                  <div class="admin-item-email"><?= e($member['email']) ?></div>
                <?php endif; ?>
              </div>
            </div>

            <div class="admin-item-actions">
              <!-- Botón Editar -->
              <a href="/admin?tab=creditos&edit_id=<?= (int) $member['id'] ?>" 
                 class="admin-btn-icon edit" 
                 title="Editar integrante"
                 aria-label="Editar <?= e($member['name']) ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </a>

              <!-- Botón Eliminar -->
              <form method="post" action="/admin/creditos/delete" onsubmit="return confirm('¿Estás seguro de que deseas eliminar a este integrante de créditos?');" class="admin-form-inline">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="id" value="<?= (int) $member['id'] ?>">
                <button type="submit" 
                        class="admin-btn-icon delete" 
                        title="Eliminar integrante"
                        aria-label="Eliminar <?= e($member['name']) ?>">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    <line x1="10" y1="11" x2="10" y2="17"></line>
                    <line x1="14" y1="11" x2="14" y2="17"></line>
                  </svg>
                </button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Columna derecha: Formulario de Creación / Edición -->
  <aside class="admin-sidebar-col">
    <div class="admin-sidebar-card">
      <div class="admin-sidebar-title">
        <span><?= $editingCredit ? 'Editar integrante' : 'Nuevo integrante' ?></span>
        <?php if ($editingCredit) : ?>
          <a href="/admin?tab=creditos" class="admin-cancel-edit" title="Cancelar edición">Cancelar</a>
        <?php else : ?>
          <span class="admin-plus-icon">+</span>
        <?php endif; ?>
      </div>

      <form method="post" action="/admin/creditos">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <?php if ($editingCredit) : ?>
          <input type="hidden" name="id" value="<?= (int) $editingCredit['id'] ?>">
        <?php endif; ?>

        <!-- Nombre -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="credit-name">Nombre</label>
          <input type="text" 
                 id="credit-name" 
                 name="name" 
                 class="admin-form-input" 
                 value="<?= e($editingCredit['name'] ?? '') ?>" 
                 maxlength="150" 
                 placeholder="Ej: Vicente Campaña" 
                 required>
        </div>

        <!-- Cargo -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="credit-role">Cargo / Rol</label>
          <input type="text" 
                 id="credit-role" 
                 name="role" 
                 class="admin-form-input" 
                 value="<?= e($editingCredit['role'] ?? '') ?>" 
                 maxlength="100" 
                 placeholder="Ej: Desarrollador Backend" 
                 required>
        </div>

        <!-- Correo Electrónico -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="credit-email">Correo de contacto</label>
          <input type="email" 
                 id="credit-email" 
                 name="email" 
                 class="admin-form-input" 
                 value="<?= e($editingCredit['email'] ?? '') ?>" 
                 maxlength="150" 
                 placeholder="Ej: vicente.campana@userena.cl" 
                 required>
        </div>

        <!-- Orden -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="credit-orden">Orden</label>
          <input type="number" 
                 id="credit-orden" 
                 name="orden" 
                 class="admin-form-input" 
                 value="<?= e((string) ($editingCredit['orden'] ?? 0)) ?>" 
                 min="0">
        </div>

        <!-- Botón Submit -->
        <button type="submit" class="admin-btn-submit">
          <?= $editingCredit ? 'Guardar cambios' : 'Crear' ?>
        </button>
      </form>
    </div>
  </aside>
</div>
