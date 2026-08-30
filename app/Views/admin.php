<?php

declare(strict_types=1);

/**
 * @var array $user
 * @var array $stats
 */
$user ??= [];
$stats ??= [];
?>
<section class="section section-surface" style="min-height: 60vh; padding: 48px 0;">
  <div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 32px;">
      <div>
        <h1 class="section-title" style="margin-bottom: 8px;">Panel de Administración</h1>
        <p style="color: var(--muted-foreground);">
          Bienvenido/a, <strong><?= e($user['username'] ?? 'Usuario') ?></strong> 
          (Rol: <span style="background: #e2e8f0; padding: 2px 8px; border-radius: 4px; font-size: 0.85em;"><?= e($user['role_name'] ?? 'Usuario') ?></span>)
        </p>
      </div>
      <div style="display: flex; gap: 12px; align-items: center;">
        <a href="/cambiar-password" class="btn" style="background: var(--brand); color: white; padding: 8px 16px; border-radius: var(--radius); font-size: 0.9rem;">Cambiar contraseña</a>
        <a href="/logout" class="btn btn-destructive" style="padding: 8px 16px; border-radius: var(--radius); font-size: 0.9rem;">Cerrar sesión</a>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
      <div class="card" style="padding: 24px; text-align: center; border-radius: var(--radius); background: white; box-shadow: var(--shadow-card);">
        <p style="font-size: 0.875rem; color: var(--muted-foreground); text-transform: uppercase; font-weight: 600;">Noticias</p>
        <p style="font-size: 2.25rem; font-weight: 700; color: var(--brand); margin-top: 8px;"><?= (int) ($stats['noticias'] ?? 0) ?></p>
      </div>

      <div class="card" style="padding: 24px; text-align: center; border-radius: var(--radius); background: white; box-shadow: var(--shadow-card);">
        <p style="font-size: 0.875rem; color: var(--muted-foreground); text-transform: uppercase; font-weight: 600;">Proyectos</p>
        <p style="font-size: 2.25rem; font-weight: 700; color: var(--brand); margin-top: 8px;"><?= (int) ($stats['proyectos'] ?? 0) ?></p>
      </div>

      <div class="card" style="padding: 24px; text-align: center; border-radius: var(--radius); background: white; box-shadow: var(--shadow-card);">
        <p style="font-size: 0.875rem; color: var(--muted-foreground); text-transform: uppercase; font-weight: 600;">Servicios</p>
        <p style="font-size: 2.25rem; font-weight: 700; color: var(--brand); margin-top: 8px;"><?= (int) ($stats['servicios'] ?? 0) ?></p>
      </div>

      <div class="card" style="padding: 24px; text-align: center; border-radius: var(--radius); background: white; box-shadow: var(--shadow-card);">
        <p style="font-size: 0.875rem; color: var(--muted-foreground); text-transform: uppercase; font-weight: 600;">Staff</p>
        <p style="font-size: 2.25rem; font-weight: 700; color: var(--brand); margin-top: 8px;"><?= (int) ($stats['staff'] ?? 0) ?></p>
      </div>

      <div class="card" style="padding: 24px; text-align: center; border-radius: var(--radius); background: white; box-shadow: var(--shadow-card);">
        <p style="font-size: 0.875rem; color: var(--muted-foreground); text-transform: uppercase; font-weight: 600;">Consultas</p>
        <p style="font-size: 2.25rem; font-weight: 700; color: var(--brand); margin-top: 8px;"><?= (int) ($stats['consultas'] ?? 0) ?></p>
      </div>

      <div class="card" style="padding: 24px; text-align: center; border-radius: var(--radius); background: white; box-shadow: var(--shadow-card);">
        <p style="font-size: 0.875rem; color: var(--muted-foreground); text-transform: uppercase; font-weight: 600;">Usuarios</p>
        <p style="font-size: 2.25rem; font-weight: 700; color: var(--brand); margin-top: 8px;"><?= (int) ($stats['usuarios'] ?? 0) ?></p>
      </div>
    </div>
  </div>
</section>
