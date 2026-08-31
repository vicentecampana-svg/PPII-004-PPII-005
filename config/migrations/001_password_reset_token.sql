-- =============================================================================
--  Migración: tabla password_reset_token
--  Issue:     #15 — Recuperar contraseña por correo
--  Autor:     fix-issue-15
--  Fecha:     2026-08-31
-- =============================================================================
--
--  NOTAS:
--  • Los tokens se almacenan hasheados (SHA-256), no en claro.
--  • Un usuario sólo puede tener UN token activo a la vez (la solicitud nueva
--    elimina la anterior via PasswordResetRepository::create).
--  • expires_at se fija a NOW() + 1 hora en la capa de servicio.
--  • El campo `used` permite invalidar el token tras el primer uso sin borrarlo
--    (útil para auditoría).
--
-- =============================================================================

CREATE TABLE IF NOT EXISTS public.password_reset_token (
    id         BIGSERIAL    PRIMARY KEY,
    user_id    INTEGER      NOT NULL
                            REFERENCES public.app_user(id) ON DELETE CASCADE,
    token      CHAR(64)     NOT NULL,   -- hash SHA-256 del token plano (64 hex chars)
    expires_at TIMESTAMPTZ  NOT NULL,
    used       BOOLEAN      NOT NULL DEFAULT false,
    created_at TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

-- Índice para la búsqueda por token (ruta crítica del flujo de recuperación)
CREATE INDEX IF NOT EXISTS idx_prt_token      ON public.password_reset_token (token);

-- Índice para eliminar rápidamente tokens anteriores del mismo usuario
CREATE INDEX IF NOT EXISTS idx_prt_user_id   ON public.password_reset_token (user_id);

-- Índice para limpiar tokens vencidos (opcional, tarea de mantenimiento)
CREATE INDEX IF NOT EXISTS idx_prt_expires_at ON public.password_reset_token (expires_at);
