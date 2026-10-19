-- =============================================================================
--  Migración: tabla form_submission
--  Issue:     SEC-03 — Anti-spam en formularios públicos
--  Fecha:     2026-10-17
-- =============================================================================
--
--  NOTAS:
--  • Registra cada envío de un formulario público (contacto, créditos,
--    consultas por API) para aplicar un límite por IP y por correo.
--  • config/schema.sql ya la incluye para instalaciones nuevas; esta migración
--    es para bases existentes.
--
-- =============================================================================

CREATE TABLE IF NOT EXISTS public.form_submission (
    id         BIGSERIAL    PRIMARY KEY,
    form       VARCHAR(64)  NOT NULL,
    ip         VARCHAR(45)  NOT NULL,
    email      VARCHAR(255),
    created_at TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_form_submission_form_ip
    ON public.form_submission (form, ip, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_form_submission_form_email
    ON public.form_submission (form, LOWER(email), created_at DESC);
