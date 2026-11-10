<?php

declare(strict_types=1);

/**
 * RF-SRV-001: gestión de servicios del panel.
 *
 * @var array $servicesList
 * @var array|null $editingService
 */
$servicesList ??= [];
$editingService ??= null;
?>

<div class="admin-layout-grid">
  <!-- Columna izquierda: Listado de servicios -->
  <div class="admin-main-col">
    <div class="admin-tab-header">
      <h2 class="admin-tab-content-title">Servicios</h2>
    </div>

    <?php if (empty($servicesList)) : ?>
      <div class="admin-empty-state">
        <p>No hay servicios registrados actualmente.</p>
      </div>
    <?php else : ?>
      <div class="admin-items-list">
        <?php foreach ($servicesList as $service) : ?>
          <article class="admin-item-row">
            <div class="admin-item-content">
              <img src="<?= e(mediaUrl($service['image'] ?? null, 'proyecto')) ?>"
                   alt="<?= e($service['name']) ?>"
                   class="admin-item-thumb"
                   loading="lazy">
              <div class="admin-item-info">
                <div class="admin-item-title"><?= e($service['name']) ?></div>
                <div class="admin-item-desc"><?= e(mb_strimwidth((string) ($service['description'] ?? ''), 0, 110, '…')) ?></div>
              </div>
            </div>

            <div class="admin-item-actions">
              <!-- Botón Editar -->
              <a href="/admin?tab=servicios&edit_id=<?= (int) $service['id'] ?>"
                 class="admin-btn-icon edit"
                 title="Editar servicio"
                 aria-label="Editar <?= e($service['name']) ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </a>

              <!-- Botón Eliminar -->
              <form method="post" action="/admin/servicios/delete" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este servicio?');" class="admin-form-inline">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                <button type="submit"
                        class="admin-btn-icon delete"
                        title="Eliminar servicio"
                        aria-label="Eliminar <?= e($service['name']) ?>">
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

  <!-- Columna derecha: Formulario de creación / edición -->
  <aside class="admin-sidebar-col">
    <div class="admin-sidebar-card">
      <div class="admin-sidebar-title">
        <span><?= $editingService ? 'Editar servicio' : 'Nuevo servicio' ?></span>
        <?php if ($editingService) : ?>
          <a href="/admin?tab=servicios" class="admin-cancel-edit" title="Cancelar edición">Cancelar</a>
        <?php else : ?>
          <span class="admin-plus-icon">+</span>
        <?php endif; ?>
      </div>

      <form method="post" action="/admin/servicios" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <?php if ($editingService) : ?>
          <input type="hidden" name="id" value="<?= (int) $editingService['id'] ?>">
        <?php endif; ?>

        <!-- Nombre -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="service-name">Nombre</label>
          <input type="text"
                 id="service-name"
                 name="name"
                 class="admin-form-input"
                 value="<?= e($editingService['name'] ?? '') ?>"
                 minlength="3"
                 placeholder="Ej: Desarrollo de software a la medida"
                 required>
        </div>

        <!-- Descripción -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="service-description">Descripción</label>
          <textarea id="service-description"
                    name="description"
                    class="admin-form-textarea"
                    rows="4"
                    minlength="10"
                    placeholder="Describe el servicio..."
                    required><?= e($editingService['description'] ?? '') ?></textarea>
        </div>

        <!-- Enlace -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="service-link">Enlace (opcional)</label>
          <input type="url"
                 id="service-link"
                 name="link"
                 class="admin-form-input"
                 value="<?= e($editingService['link'] ?? '') ?>"
                 placeholder="https://...">
        </div>

        <!-- Imagen -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="service-image-file">Imagen</label>
          <div class="admin-img-preview-box">
            <img id="service-img-preview"
                 src="<?= e(mediaUrl($editingService['image'] ?? null, 'proyecto')) ?>"
                 alt="Previsualización"
                 class="admin-img-preview">
          </div>
          <input type="file"
                 id="service-image-file"
                 name="image_file"
                 class="admin-form-file"
                 accept="image/png, image/jpeg"
                 onchange="if(this.files && this.files[0]){ document.getElementById('service-img-preview').src = URL.createObjectURL(this.files[0]); }">
          <input type="hidden" name="existing_image" value="<?= e($editingService['image'] ?? '') ?>">
        </div>

        <!-- Botón Submit -->
        <button type="submit" class="admin-btn-submit">
          <?= $editingService ? 'Guardar cambios' : 'Crear' ?>
        </button>
      </form>
    </div>
  </aside>
</div>
