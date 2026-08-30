<?php

declare(strict_types=1);

/**
 * @var array $footerLinks
 * @var array|null $editingFooterLink
 * @var array|null $footerInfo
 */
$footerLinks ??= [];
$editingFooterLink ??= null;
$footerInfo ??= [
    'social_linkedin'  => 'https://linkedin.com',
    'social_twitter'   => 'https://twitter.com',
    'social_instagram' => 'https://instagram.com',
];
?>

<div class="admin-layout-grid">
  <!-- Columna izquierda: Listado de Enlaces y Redes Sociales -->
  <div class="admin-main-col">
    <div class="admin-tab-header">
      <h2 class="admin-tab-content-title">Enlaces del footer</h2>
    </div>

    <?php if (empty($footerLinks)): ?>
      <div class="admin-empty-state">
        <p>No hay enlaces configurados en el footer.</p>
      </div>
    <?php else: ?>
      <div class="admin-items-list">
        <?php foreach ($footerLinks as $link): ?>
          <article class="admin-item-row">
            <div class="admin-item-content">
              <div class="admin-item-info" style="padding-left: 4px;">
                <div class="admin-item-title"><?= e($link['etiqueta']) ?></div>
                <div class="admin-item-desc"><?= e($link['url']) ?></div>
              </div>
            </div>

            <div class="admin-item-actions">
              <!-- Botón Editar -->
              <a href="/admin?tab=footer&edit_id=<?= (int) $link['id'] ?>" 
                 class="admin-btn-icon edit" 
                 title="Editar enlace"
                 aria-label="Editar <?= e($link['etiqueta']) ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </a>

              <!-- Botón Eliminar -->
              <form method="post" action="/admin/footer/links/delete" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este enlace?');" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="id" value="<?= (int) $link['id'] ?>">
                <button type="submit" 
                        class="admin-btn-icon delete" 
                        title="Eliminar enlace"
                        aria-label="Eliminar <?= e($link['etiqueta']) ?>">
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

    <!-- Sección de Redes Sociales (Editar enlaces de las existentes) -->
    <div style="margin-top: 32px;">
      <div class="admin-tab-header">
        <h2 class="admin-tab-content-title" style="font-size: 1.1rem; margin-bottom: 12px;">Redes sociales</h2>
      </div>
      <div class="admin-sidebar-card" style="padding: 20px;">
        <p style="font-size: 0.85rem; color: var(--muted-foreground); margin-bottom: 16px;">
          Edita las URLs correspondientes a las redes sociales oficiales del footer.
        </p>
        <form method="post" action="/admin/footer/social">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

          <div class="admin-form-group">
            <label class="admin-form-label" for="social-linkedin">LinkedIn</label>
            <input type="text" 
                   id="social-linkedin" 
                   name="social_linkedin" 
                   class="admin-form-input" 
                   value="<?= e($footerInfo['social_linkedin'] ?? '') ?>" 
                   placeholder="https://linkedin.com/company/...">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label" for="social-twitter">Twitter / X</label>
            <input type="text" 
                   id="social-twitter" 
                   name="social_twitter" 
                   class="admin-form-input" 
                   value="<?= e($footerInfo['social_twitter'] ?? '') ?>" 
                   placeholder="https://twitter.com/...">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label" for="social-instagram">Instagram</label>
            <input type="text" 
                   id="social-instagram" 
                   name="social_instagram" 
                   class="admin-form-input" 
                   value="<?= e($footerInfo['social_instagram'] ?? '') ?>" 
                   placeholder="https://instagram.com/...">
          </div>

          <button type="submit" class="admin-btn-submit" style="width: auto; padding: 8px 20px; margin-top: 6px;">
            Guardar redes sociales
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Columna derecha: Formulario de Creación / Edición de Enlace (Mockup) -->
  <aside class="admin-sidebar-col">
    <div class="admin-sidebar-card">
      <div class="admin-sidebar-title">
        <span><?= $editingFooterLink ? 'Editar registro' : 'Nuevo registro' ?></span>
        <?php if ($editingFooterLink): ?>
          <a href="/admin?tab=footer" class="admin-cancel-edit" title="Cancelar edición">Cancelar</a>
        <?php else: ?>
          <span style="font-size: 1.2rem; font-weight: bold; color: var(--muted-foreground);">+</span>
        <?php endif; ?>
      </div>

      <form method="post" action="/admin/footer/links">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <?php if ($editingFooterLink): ?>
          <input type="hidden" name="id" value="<?= (int) $editingFooterLink['id'] ?>">
        <?php endif; ?>

        <!-- Columna (Sitio, Contenido, Contacto) -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="link-group">Columna (Sitio, Contenido, Contacto)</label>
          <input type="text" 
                 id="link-group" 
                 name="grupo" 
                 class="admin-form-input" 
                 value="<?= e($editingFooterLink['grupo'] ?? 'Sitio') ?>" 
                 placeholder="Sitio"
                 required>
        </div>

        <!-- Texto del enlace -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="link-text">Texto del enlace</label>
          <input type="text" 
                 id="link-text" 
                 name="etiqueta" 
                 class="admin-form-input" 
                 value="<?= e($editingFooterLink['etiqueta'] ?? '') ?>" 
                 maxlength="150" 
                 placeholder="Ej: Inicio"
                 required>
        </div>

        <!-- URL o ruta -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="link-url">URL o ruta</label>
          <input type="text" 
                 id="link-url" 
                 name="url" 
                 class="admin-form-input" 
                 value="<?= e($editingFooterLink['url'] ?? '/') ?>" 
                 placeholder="/"
                 required>
        </div>

        <!-- Orden -->
        <div class="admin-form-group">
          <label class="admin-form-label" for="link-order">Orden</label>
          <input type="number" 
                 id="link-order" 
                 name="orden" 
                 class="admin-form-input" 
                 value="<?= e((string) ($editingFooterLink['orden'] ?? 0)) ?>" 
                 min="0">
        </div>

        <!-- Botón Submit -->
        <button type="submit" class="admin-btn-submit">
          <?= $editingFooterLink ? 'Guardar cambios' : 'Crear' ?>
        </button>
      </form>
    </div>
  </aside>
</div>
