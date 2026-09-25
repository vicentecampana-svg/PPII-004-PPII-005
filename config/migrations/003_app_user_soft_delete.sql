-- =============================================================================
--  Migración: eliminación lógica de usuarios (app_user.deleted_at)
--  Fecha:     2026-09-25
-- =============================================================================
--
--  NOTAS:
--  • Eliminar un usuario ya no borra la fila: se marca deleted_at y active=false.
--    Así se conservan sus registros de auditoría y noticias (las FK impedían
--    el DELETE) y su email/username no pueden volver a registrarse.
--  • Un usuario eliminado no aparece en el panel, no puede iniciar sesión ni
--    recuperar contraseña, y no puede reactivarse desde el formulario.
--
-- =============================================================================

ALTER TABLE public.app_user ADD COLUMN IF NOT EXISTS deleted_at timestamp without time zone;
