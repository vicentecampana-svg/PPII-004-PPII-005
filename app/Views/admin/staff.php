<?php

declare(strict_types=1);

/**
 * @var array $staffList
 * @var array|null $editingStaff
 */
$staffList ??= [];
$editingStaff ??= null;
?>

<div class="admin-layout-grid">
  <!-- Columna izquierda: Listado de Miembros del Staff -->
  <div class="admin-main-col">
    <div class="admin-tab-header">
      <h2 class="admin-tab-content-title">Miembros del staff</h2>
    </div>

    <?php if (empty($staffList)): ?>
      <div class="admin-empty-state">
        <p>No hay miembros del staff registrados actualmente.</p>
      </div>
    <?php else: ?>
      <div class="admin-items-list">
        <?php foreach ($staffList as $member): ?>
          <article class="admin-item-row">
            <div class="admin-item-content">
              <?php if (!empty($member['photo'])): ?>
                <img src="<?= e(mediaUrl($member['photo'], 'staff')) ?>" 
                     alt="<?= e($member['name']) ?>" 
                     class="admin-item-thumb"
                     loading="lazy">
              <?php else: ?>
                <div class="admin-item-thumb admin-avatar-placeholder" aria-label="<?= e($member['name']) ?>">
                  <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                  </svg>
                </div>
              <?php endif; ?>
              <div class="admin-item-info">
                <div class="admin-item-title"><?= e($member['name']) ?></div>
                <div class="admin-item-desc"><?= e($member['position'] ?? 'Sin cargo asignado') ?></div>
              </div>
            </div>

            <div class="admin-item-actions">
              <!-- Botón Editar -->
              <a href="/admin?tab=staff&edit_id=<?= (int) $member['id'] ?>" 
                 class="admin-btn-icon edit" 
                 title="Editar miembro"
                 aria-label="Editar <?= e($member['name']) ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </a>

              <!-- Botón Eliminar -->
              <form method="post" action="/admin/staff/delete" onsubmit="return confirm('¿Estás seguro de que deseas eliminar a este miembro del staff?');" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="id" value="<?= (int) $member['id'] ?>">
                <button type="submit" 
                        class="admin-btn-icon delete" 
                        title="Eliminar miembro"
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

  <!-- Columna derecha: Formulario de Creación / Edición (Mockup) -->
  <aside class="admin-sidebar-col">
    <div class="admin-sidebar-card">
      <div class="admin-sidebar-title">
        <span><?= $editingStaff ? 'Editar registro' : 'Nuevo registro' ?></span>
        <?php if ($editingStaff): ?>
          <a href="/admin?tab=staff" class="admin-cancel-edit" title="Cancelar edición">Cancelar</a>
        <?php else: ?>
          <span style="font-size: 1.2rem; font-weight: bold; color: var(--muted-foreground);">+</span>
        <?php endif; ?>
      </div>

      <form method="post" action="/admin/staff" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <?php if ($editingStaff): ?>
          <input type="hidden" name="id" value="<?= (int) $editingStaff['id'] ?>">
        <?php endif; ?>

        <!-- Nombre -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="staff-name">Nombre</label>
          <input type="text" 
                 id="staff-name" 
                 name="name" 
                 class="admin-form-input" 
                 value="<?= e($editingStaff['name'] ?? '') ?>" 
                 maxlength="150" 
                 placeholder="Ej: Pedro Rojas"
                 required>
        </div>

        <!-- Cargo -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="staff-position">Cargo</label>
          <input type="text" 
                 id="staff-position" 
                 name="position" 
                 class="admin-form-input" 
                 value="<?= e($editingStaff['position'] ?? '') ?>" 
                 maxlength="100" 
                 placeholder="Ej: Project Manager Officer">
        </div>

        <!-- Descripción -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="staff-description">Descripción</label>
          <textarea id="staff-description" 
                    name="description" 
                    class="admin-form-textarea" 
                    rows="4" 
                    placeholder="Descripción del rol o perfil..."><?= e($editingStaff['description'] ?? '') ?></textarea>
        </div>

        <!-- Foto -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="staff-photo-file">Foto</label>
          <div class="admin-img-preview-box">
            <img id="staff-img-preview" 
                 src="<?= e(mediaUrl($editingStaff['photo'] ?? null, 'staff')) ?>" 
                 alt="Previsualización" 
                 class="admin-img-preview">
          </div>
          <input type="file" 
                 id="staff-photo-file" 
                 name="photo_file" 
                 class="admin-form-file" 
                 accept="image/png, image/jpeg, image/webp, image/gif, image/svg+xml"
                 onchange="if(this.files && this.files[0]){ document.getElementById('staff-img-preview').src = URL.createObjectURL(this.files[0]); }">
          <input type="hidden" name="existing_photo" value="<?= e($editingStaff['photo'] ?? '') ?>">
        </div>

        <!-- Orden -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="staff-order">Orden</label>
          <input type="number" 
                 id="staff-order" 
                 name="order" 
                 class="admin-form-input" 
                 value="<?= e((string) ($editingStaff['order'] ?? 0)) ?>" 
                 min="0">
        </div>

        <!-- Botón Submit -->
        <button type="submit" class="admin-btn-submit">
          <?= $editingStaff ? 'Guardar cambios' : 'Crear' ?>
        </button>
      </form>
    </div>
  </aside>
</div>
