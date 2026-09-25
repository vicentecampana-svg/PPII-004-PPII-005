-- =============================================================================
--  Migración: audit_log.user_id admite NULL
--  Fecha:     2026-09-25
-- =============================================================================
--
--  NOTAS:
--  • Las acciones hechas sin sesión (p. ej. el formulario de contacto público)
--    se registraban a nombre del usuario 1 (admin). Ahora se guardan con
--    user_id = NULL y la API de auditoría las muestra como "visitante".
--  • Las filas existentes no se modifican.
--
-- =============================================================================

ALTER TABLE public.audit_log ALTER COLUMN user_id DROP NOT NULL;
