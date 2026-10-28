-- =============================================================================
--  Migración: tope diario de correos por ámbito y aviso agrupado de consultas
--  Issue:     SEC-09 — Protección de la cuota del proveedor de correo
--  Fecha:     2026-10-26
-- =============================================================================
--
--  NOTAS:
--  • mail_log registra cada correo que la aplicación intenta enviar (sin
--    destinatario ni contenido) para aplicar el tope diario de cada ámbito
--    (contacto, créditos, recuperación). Ver MailBudgetService.
--  • contact_request.notified_at marca las consultas ya incluidas en un aviso
--    al encargado. Las consultas existentes se dan por avisadas, porque hasta
--    ahora se notificaban una por una al llegar.
--  • config/schema.sql ya incluye estos cambios para instalaciones nuevas;
--    esta migración es para bases existentes.
--
--  Uso:
--      psql -U $DB_USER -d $POSTGRES_DB -f config/migrations/010_mail_budget_contact_digest.sql
--
-- =============================================================================

CREATE TABLE IF NOT EXISTS public.mail_log (
    id        BIGSERIAL    PRIMARY KEY,
    scope     VARCHAR(32)  NOT NULL,
    delivered BOOLEAN,
    sent_at   TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_mail_log_scope_sent_at
    ON public.mail_log (scope, sent_at DESC);

ALTER TABLE public.contact_request
    ADD COLUMN IF NOT EXISTS notified_at TIMESTAMPTZ;

UPDATE public.contact_request
   SET notified_at = sent_at
 WHERE notified_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_contact_request_unnotified
    ON public.contact_request (id)
    WHERE notified_at IS NULL;
