-- =============================================================================
--  Migración: tabla credit_member + datos base del equipo Charlie
--  Issue:     Créditos editables — Super Admin (nombre, cargo, correo de contacto)
--  Autor:     fix-issue-credits-editable
--  Fecha:     2026-09-16
-- =============================================================================
--
--  NOTAS:
--  • Reemplaza la constante hardcodeada CreditsService::TEAM_MEMBERS por datos
--    en BD, con los mismos integrantes del apartado Créditos (equipo Charlie).
--  • `key` es el slug que usa el formulario de contacto del modal de créditos
--    (member_key) y se conserva igual que en el código actual.
--  • `orden` reproduce el orden de las tarjetas como aparecían en la vista.
--  • El seed es idempotente (ON CONFLICT DO NOTHING) para poder re-aplicarse.
--  • El email se usa SOLO para el envío de mensajes; el frontend nunca lo ve.
--
-- =============================================================================

CREATE TABLE IF NOT EXISTS public.credit_member (
    id     INTEGER      NOT NULL,
    "key"  VARCHAR(60)  NOT NULL UNIQUE,
    name   VARCHAR(150) NOT NULL,
    role   VARCHAR(100) NOT NULL,
    email  VARCHAR(150) NOT NULL,
    orden  INTEGER      NOT NULL DEFAULT 0
);

CREATE SEQUENCE IF NOT EXISTS public.credit_member_id_seq
    AS INTEGER
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

ALTER SEQUENCE public.credit_member_id_seq OWNED BY public.credit_member.id;

ALTER TABLE ONLY public.credit_member
    ALTER COLUMN id SET DEFAULT nextval('public.credit_member_id_seq'::regclass);

ALTER TABLE ONLY public.credit_member
    ADD CONSTRAINT credit_member_pkey PRIMARY KEY (id);

-- Datos base: mismos integrantes que hoy muestra el apartado Créditos
INSERT INTO public.credit_member ("key", name, role, email, orden) VALUES
    ('vicente-campana',       'Vicente Campaña',          'Project Manager',                          'vicente.campana@userena.cl',    1),
    ('wilmary-guedez',        'Wilmary Guedez',           'Ingeniera en Requerimientos',              'wilmary.guedez@userena.cl',     2),
    ('esteban-zepeda',        'Esteban Zepeda',           'Diseño UX/UI',                             'esteban.zepeda@userena.cl',     3),
    ('bastian-pizarro',       'Bastian Pizarro',          'Diseño UX/UI',                             'bastian.pizarro@userena.cl',    4),
    ('maximiliano-saavedra',  'Maximiliano Saavedra',     'Desarrollo Backend y Base de datos',       'maximiliano.saavedra@userena.cl', 5),
    ('agustina-lopez',        'Agustina Lopez',           'Desarrollo Frontend',                      'agustina.lopez@userena.cl',     6),
    ('basthian-valenzuela',   'Basthian Valenzuela',      'Quality Assurance',                        'basthian.valenzuela@userena.cl', 7),
    ('pedro-rojas',           'Pedro Rojas',              'Apoyo Desarrollo',                         'pedro.rojasm3@userena.cl',      8)
ON CONFLICT ("key") DO NOTHING;