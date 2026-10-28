-- =============================================================================
--  Migración: registro con IP para analizar ataques
--  Issue:     SEC-13 — Trazabilidad de intentos y rechazos
--  Fecha:     2026-10-27
-- =============================================================================
--
--  NOTAS:
--  • form_rejection registra los envíos rechazados de formularios públicos y
--    del login (honeypot, CAPTCHA incorrecto, límite de envíos), con su IP.
--  • audit_log.ip guarda la IP del cliente en cada acción auditada. Los
--    registros anteriores quedan con NULL.
--  • login_attempt ya no se vacía al iniciar sesión (se registra el éxito),
--    así que se agregan índices para las consultas del bloqueo.
--  • config/schema.sql ya incluye estos cambios para instalaciones nuevas;
--    esta migración es para bases existentes.
--
--  Uso:
--      psql -U $DB_USER -d $POSTGRES_DB -f config/migrations/011_security_logging.sql
--
-- =============================================================================

CREATE TABLE IF NOT EXISTS public.form_rejection (
    id         BIGSERIAL    PRIMARY KEY,
    form       VARCHAR(64)  NOT NULL,
    reason     VARCHAR(32)  NOT NULL,
    ip         VARCHAR(45)  NOT NULL,
    email      VARCHAR(255),
    created_at TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_form_rejection_created_at
    ON public.form_rejection (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_form_rejection_ip
    ON public.form_rejection (ip, created_at DESC);

ALTER TABLE public.audit_log
    ADD COLUMN IF NOT EXISTS ip VARCHAR(45);

CREATE INDEX IF NOT EXISTS idx_login_attempt_ip
    ON public.login_attempt (ip, attempted_at DESC);
CREATE INDEX IF NOT EXISTS idx_login_attempt_email
    ON public.login_attempt (LOWER(email), attempted_at DESC);
