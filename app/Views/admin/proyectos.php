<?php

declare(strict_types=1);

/**
 * @var array $projects
 * @var array|null $editingProject
 */
$projects ??= [];
$editingProject ??= null;
?>

<div class="admin-layout-grid">
  <!-- Columna izquierda: Listado de Proyectos -->
  <div class="admin-main-col">
    <div class="admin-tab-header">
      <h2 class="admin-tab-content-title">Proyectos y servicios</h2>
    </div>

    <?php if (empty($projects)): ?>
      <div class="admin-empty-state">
        <p>No hay proyectos registrados actualmente.</p>
      </div>
    <?php else: ?>
      <div class="admin-items-list">
        <?php foreach ($projects as $proj): ?>
          <article class="admin-item-row<?= empty($proj['active']) ? ' inactive' : '' ?>">
            <div class="admin-item-content">
              <img src="<?= e(mediaUrl($proj['image'] ?? null, 'proyecto')) ?>" 
                   alt="<?= e($proj['name']) ?>" 
                   class="admin-item-thumb"
                   loading="lazy">
              <div class="admin-item-info">
                <div class="admin-item-title">
                  <?= e($proj['name']) ?>
                  <?php if (empty($proj['active'])): ?>
                    <span class="admin-badge-draft">Inactivo</span>
                  <?php endif; ?>
                </div>
                <div class="admin-item-desc"><?= e($proj['description'] ?? 'Sin descripción') ?></div>
              </div>
            </div>

            <div class="admin-item-actions">
              <!-- Botón Editar -->
              <a href="/admin?tab=proyectos&edit_id=<?= (int) $proj['id'] ?>" 
                 class="admin-btn-icon edit" 
                 title="Editar proyecto"
                 aria-label="Editar <?= e($proj['name']) ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </a>

              <!-- Botón Eliminar -->
              <form method="post" action="/admin/proyectos/delete" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este proyecto?');" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="id" value="<?= (int) $proj['id'] ?>">
                <button type="submit" 
                        class="admin-btn-icon delete" 
                        title="Eliminar proyecto"
                        aria-label="Eliminar <?= e($proj['name']) ?>">
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
        <span><?= $editingProject ? 'Editar registro' : 'Nuevo registro' ?></span>
        <?php if ($editingProject): ?>
          <a href="/admin?tab=proyectos" class="admin-cancel-edit" title="Cancelar edición">Cancelar</a>
        <?php else: ?>
          <span style="font-size: 1.2rem; font-weight: bold; color: var(--muted-foreground);">+</span>
        <?php endif; ?>
      </div>

      <form method="post" action="/admin/proyectos" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <?php if ($editingProject): ?>
          <input type="hidden" name="id" value="<?= (int) $editingProject['id'] ?>">
        <?php endif; ?>

        <!-- Título -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="project-name">Título</label>
          <input type="text" 
                 id="project-name" 
                 name="name" 
                 class="admin-form-input" 
                 value="<?= e($editingProject['name'] ?? '') ?>" 
                 maxlength="150" 
                 placeholder="Ej: Plataforma Académica"
                 required>
        </div>

        <!-- Descripción -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="project-description">Descripción</label>
          <textarea id="project-description" 
                    name="description" 
                    class="admin-form-textarea" 
                    rows="4" 
                    placeholder="Descripción del proyecto..."><?= e($editingProject['description'] ?? '') ?></textarea>
        </div>

        <!-- Imagen -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="project-image-file">Imagen</label>
          <div class="admin-img-preview-box">
            <img id="project-img-preview" 
                 src="<?= e(mediaUrl($editingProject['image'] ?? null, 'proyecto')) ?>" 
                 alt="Previsualización" 
                 class="admin-img-preview">
          </div>
          <input type="file" 
                 id="project-image-file" 
                 name="image_file" 
                 class="admin-form-file" 
                 accept="image/png, image/jpeg, image/webp, image/gif, image/svg+xml"
                 onchange="if(this.files && this.files[0]){ document.getElementById('project-img-preview').src = URL.createObjectURL(this.files[0]); }">
          <input type="hidden" name="existing_image" value="<?= e($editingProject['image'] ?? '') ?>">
        </div>

        <!-- Enlace -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="project-link">Enlace / Link (opcional)</label>
          <input type="text" 
                 id="project-link" 
                 name="link" 
                 class="admin-form-input" 
                 value="<?= e($editingProject['link'] ?? '') ?>" 
                 placeholder="https://...">
        </div>

        <!-- Estado Activo -->
        <div class="admin-form-group" style="display: flex; align-items: center; gap: 8px; margin-top: 10px;">
          <input type="checkbox" 
                 id="project-active" 
                 name="active" 
                 value="1" 
                 <?= (!isset($editingProject) || !empty($editingProject['active'])) ? 'checked' : '' ?>>
          <label class="admin-form-label" for="project-active" style="margin-bottom: 0; cursor: pointer;">
            Visible públicamente
          </label>
        </div>

        <!-- Botón Submit -->
        <button type="submit" class="admin-btn-submit">
          <?= $editingProject ? 'Guardar cambios' : 'Crear' ?>
        </button>
      </form>
    </div>
  </aside>
</div>
