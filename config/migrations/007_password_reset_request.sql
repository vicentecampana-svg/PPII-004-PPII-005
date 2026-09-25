-- =============================================================================
--  Migración: tabla password_reset_request
--  Issue:     #79 — Bloqueo de spam en recuperación de contraseña
--  Fecha:     2026-09-25
-- =============================================================================
--
--  NOTAS:
--  • Registra cada solicitud de recuperación (exista o no el correo) para
--    aplicar un cooldown entre envíos sin revelar si la cuenta existe.
--  • config/schema.sql ya la incluye para instalaciones nuevas; esta migración
--    es para bases existentes.
--
-- =============================================================================

CREATE TABLE IF NOT EXISTS public.password_reset_request (
    id           BIGSERIAL    PRIMARY KEY,
    email        VARCHAR(255) NOT NULL,
    ip           VARCHAR(45)  NOT NULL,
    requested_at TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_prr_email_requested_at
    ON public.password_reset_request (LOWER(email), requested_at DESC);
CREATE INDEX IF NOT EXISTS idx_prr_ip_requested_at
    ON public.password_reset_request (ip, requested_at DESC);
