<?php

declare(strict_types=1);

/**
 * @var array $newsList
 * @var array|null $editingNews
 * @var array $user
 */
$newsList ??= [];
$editingNews ??= null;
$user ??= authUser() ?? [];
$roleNormalized = strtolower((string) ($user['role_name'] ?? ''));
$isEditorOrAdmin = in_array($roleNormalized, ['superadmin', 'admin', 'editor'], true);
?>

<div class="admin-layout-grid">
  <!-- Columna izquierda: Listado de Noticias -->
  <div class="admin-main-col">
    <div class="admin-tab-header">
      <h2 class="admin-tab-content-title">Noticias</h2>
    </div>

    <?php if (empty($newsList)): ?>
      <div class="admin-empty-state">
        <p>No hay noticias registradas actualmente.</p>
      </div>
    <?php else: ?>
      <div class="admin-items-list">
        <?php foreach ($newsList as $item): ?>
          <?php 
            $statusName = strtolower((string) ($item['status'] ?? 'pendiente'));
            $isPublished = $statusName === 'publicada';
            $excerpt = $item['subtitle'] ?: mb_strimwidth(strip_tags((string) ($item['content'] ?? '')), 0, 110, '…');
          ?>
          <article class="admin-item-row">
            <div class="admin-item-content">
              <?php if (!empty($item['image'])): ?>
                <img src="<?= e(mediaUrl($item['image'], 'noticia')) ?>" 
                     alt="<?= e($item['title']) ?>" 
                     class="admin-item-thumb"
                     loading="lazy">
              <?php else: ?>
                <div class="admin-item-thumb admin-avatar-placeholder">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"></path>
                    <path d="M18 14h-8"></path>
                    <path d="M15 18h-5"></path>
                    <path d="M10 6h8v4h-8V6Z"></path>
                  </svg>
                </div>
              <?php endif; ?>

              <div class="admin-item-info">
                <div class="admin-item-title"><?= e($item['title']) ?></div>
                <div class="admin-item-desc">
                  <?php if ($isPublished): ?>
                    <span class="admin-badge-approved">Aprobada</span>
                  <?php else: ?>
                    <span class="admin-badge-pending">Pendiente</span>
                  <?php endif; ?>
                  <?php if ($excerpt !== ''): ?>
                    <span class="admin-item-excerpt">- <?= e($excerpt) ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="admin-item-actions">
              <!-- Botón Aprobar / Desaprobar (Estado) -->
              <?php if ($isEditorOrAdmin): ?>
                <form method="post" action="/admin/noticias/status" style="margin: 0;">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                  <input type="hidden" name="status" value="<?= $isPublished ? 'pendiente' : 'publicada' ?>">
                  <?php if (!$isPublished): ?>
                    <button type="submit" 
                            class="admin-btn-icon approve" 
                            title="Aprobar y publicar noticia"
                            aria-label="Aprobar <?= e($item['title']) ?>">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                    </button>
                  <?php else: ?>
                    <button type="submit" 
                            class="admin-btn-icon unapprove" 
                            title="Cambiar a pendiente (despublicar)"
                            aria-label="Despublicar <?= e($item['title']) ?>">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                      </svg>
                    </button>
                  <?php endif; ?>
                </form>
              <?php endif; ?>

              <!-- Botón Editar -->
              <a href="/admin?tab=noticias&edit_id=<?= (int) $item['id'] ?>" 
                 class="admin-btn-icon edit" 
                 title="Editar noticia"
                 aria-label="Editar <?= e($item['title']) ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </a>

              <!-- Botón Eliminar -->
              <form method="post" action="/admin/noticias/delete" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta noticia?');" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                <button type="submit" 
                        class="admin-btn-icon delete" 
                        title="Eliminar noticia"
                        aria-label="Eliminar <?= e($item['title']) ?>">
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
        <span><?= $editingNews ? 'Editar noticia' : 'Nueva noticia' ?></span>
        <?php if ($editingNews): ?>
          <a href="/admin?tab=noticias" class="admin-cancel-edit" title="Cancelar edición">Cancelar</a>
        <?php else: ?>
          <span style="font-size: 1.2rem; font-weight: bold; color: var(--muted-foreground);">+</span>
        <?php endif; ?>
      </div>

      <form method="post" action="/admin/noticias" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <?php if ($editingNews): ?>
          <input type="hidden" name="id" value="<?= (int) $editingNews['id'] ?>">
        <?php endif; ?>

        <!-- Título -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="news-title">Título</label>
          <input type="text" 
                 id="news-title" 
                 name="title" 
                 class="admin-form-input" 
                 value="<?= e($editingNews['title'] ?? '') ?>" 
                 maxlength="200" 
                 placeholder="Ej: Nuevo convenio de vinculación..."
                 required>
        </div>

        <!-- Redactor -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="news-author">Redactor</label>
          <input type="text" 
                 id="news-author" 
                 name="author" 
                 class="admin-form-input" 
                 value="<?= e($editingNews['author'] ?? ($user['username'] ?? 'Redactor')) ?>" 
                 placeholder="Nombre del redactor"
                 <?= !$isEditorOrAdmin ? 'readonly' : '' ?>>
        </div>

        <!-- Resumen -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="news-subtitle">Resumen</label>
          <textarea id="news-subtitle" 
                    name="subtitle" 
                    class="admin-form-textarea" 
                    rows="3" 
                    placeholder="Breve resumen o subtítulo..."><?= e($editingNews['subtitle'] ?? '') ?></textarea>
        </div>

        <!-- Cuerpo (un párrafo por línea) -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="news-content">Cuerpo (un párrafo por línea)</label>
          <textarea id="news-content" 
                    name="content" 
                    class="admin-form-textarea" 
                    rows="6" 
                    placeholder="Escribe el contenido de la noticia..."
                    required><?= e($editingNews['content'] ?? '') ?></textarea>
        </div>

        <!-- Imagen -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="news-image-file">Imagen</label>
          <div class="admin-img-preview-box">
            <img id="news-img-preview" 
                 src="<?= e(mediaUrl($editingNews['image'] ?? null, 'noticia')) ?>" 
                 alt="Previsualización" 
                 class="admin-img-preview">
          </div>
          <input type="file" 
                 id="news-image-file" 
                 name="image_file" 
                 class="admin-form-file" 
                 accept="image/png, image/jpeg, image/webp, image/gif, image/svg+xml"
                 onchange="if(this.files && this.files[0]){ document.getElementById('news-img-preview').src = URL.createObjectURL(this.files[0]); }">
          <input type="hidden" name="existing_image" value="<?= e($editingNews['image'] ?? '') ?>">
        </div>

        <!-- Toggles / Checkboxes (Mockup) -->
        <div class="admin-form-group" style="display: flex; flex-direction: column; gap: 8px; margin-top: 12px;">
          <label class="admin-toggle-label">
            <input type="checkbox" 
                   name="is_public" 
                   value="1" 
                   <?= (!isset($editingNews) || ($editingNews['status'] ?? '') === 'publicada') ? 'checked' : '' ?>>
            <span class="admin-toggle-text">Noticia pública</span>
          </label>

          <?php if ($isEditorOrAdmin): ?>
            <label class="admin-toggle-label">
              <input type="checkbox" 
                     name="is_approved" 
                     value="1" 
                     <?= (($editingNews['status'] ?? '') === 'publicada') ? 'checked' : '' ?>>
              <span class="admin-toggle-text">Aprobada</span>
            </label>
          <?php endif; ?>
        </div>

        <!-- Botón Submit -->
        <button type="submit" class="admin-btn-submit" style="margin-top: 16px;">
          <?= $editingNews ? 'Guardar cambios' : 'Crear noticia' ?>
        </button>
      </form>
    </div>
  </aside>
</div>
