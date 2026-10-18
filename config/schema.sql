--
-- PostgreSQL database dump
--
--
-- Issue #94 — Principio de Mínimos Privilegios (DB)
--
-- Este dump se carga con el usuario de aplicación (DB_USER, dueño de la base
-- de datos y del esquema public), de modo que las tablas, secuencias e índices
-- quedan a nombre de ese rol. DB_USER solo tiene LOGIN (sin SUPERUSER, sin
-- CREATEDB y sin CREATEROLE) y opera únicamente sobre los objetos de la app.
-- NO cargar este archivo con el superusuario (POSTGRES_USER): las tablas
-- quedarían a su nombre y la aplicación no podría operar sobre ellas.
--

\restrict 7QUYGPQx6iLPUsV0sozO9TS0ZTuYLgWUJZTW2tkxIiS1j1K0zsMF7t8Z8iPmhXM

-- Dumped from database version 17.10 (Debian 17.10-1.pgdg13+1)
-- Dumped by pg_dump version 17.10 (Debian 17.10-1.pgdg13+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: app_user; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.app_user (
    id integer NOT NULL,
    role_id integer NOT NULL,
    username character varying(50) NOT NULL,
    email character varying(150) NOT NULL,
    password character varying(255) NOT NULL,
    active boolean DEFAULT true NOT NULL,
    must_change_password boolean DEFAULT false NOT NULL,
    deleted_at timestamp without time zone -- eliminación lógica: se conserva por auditoría y para no reutilizar el email
);


--
-- Name: app_user_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.app_user_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: app_user_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.app_user_id_seq OWNED BY public.app_user.id;


--
-- Name: audit_log; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.audit_log (
    id integer NOT NULL,
    user_id integer, -- NULL = visitante anónimo (sin sesión)
    action character varying(100) NOT NULL,
    entity character varying(100) NOT NULL,
    entity_id integer NOT NULL,
    details text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: audit_log_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.audit_log_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: audit_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.audit_log_id_seq OWNED BY public.audit_log.id;


--
-- Name: contact_request; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contact_request (
    id integer NOT NULL,
    name character varying(150) NOT NULL,
    email character varying(150) NOT NULL,
    phone character varying(30),
    subject character varying(255),
    message text NOT NULL,
    sent_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    status character varying(50) NOT NULL
);


--
-- Name: contact_request_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contact_request_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contact_request_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contact_request_id_seq OWNED BY public.contact_request.id;


--
-- Name: contenido_sitio; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contenido_sitio (
    id integer NOT NULL,
    clave character varying(100) NOT NULL,
    sobre_titulo character varying(255),
    sobre_texto text,
    mision_titulo character varying(255),
    mision_texto text,
    vision_titulo character varying(255),
    vision_texto text,
    objetivos_titulo character varying(255),
    objetivos_texto text,
    politicas_titulo character varying(255),
    politicas_texto text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone,
    CONSTRAINT contenido_sitio_clave_check CHECK (((clave)::text <> ''::text))
);


--
-- Name: contenido_sitio_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contenido_sitio_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contenido_sitio_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contenido_sitio_id_seq OWNED BY public.contenido_sitio.id;


--
-- Name: enlaces_footer; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.enlaces_footer (
    id integer NOT NULL,
    grupo character varying(100) NOT NULL,
    etiqueta character varying(150) NOT NULL,
    url character varying(500) NOT NULL,
    orden integer DEFAULT 0 NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone
);


--
-- Name: enlaces_footer_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.enlaces_footer_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: enlaces_footer_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.enlaces_footer_id_seq OWNED BY public.enlaces_footer.id;


--
-- Name: footer_info; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.footer_info (
    id integer NOT NULL,
    email character varying(150),
    phone character varying(30),
    address character varying(255),
    copyright_text character varying(255),
    social_facebook character varying(500),
    social_linkedin character varying(500),
    social_twitter character varying(500),
    social_instagram character varying(500),
    social_youtube character varying(500)
);


--
-- Name: footer_info_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.footer_info_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: footer_info_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.footer_info_id_seq OWNED BY public.footer_info.id;

--
-- Name: login_attempt; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.login_attempt (
    id serial PRIMARY KEY,
    ip character varying(45) NOT NULL,
    email character varying(255) NOT NULL,
    attempted_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    success boolean DEFAULT false NOT NULL
);



--
-- Name: news; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.news (
    id integer NOT NULL,
    author_id integer NOT NULL,
    editor_id integer,
    status_id integer NOT NULL,
    tag_id integer,
    title character varying(255) NOT NULL,
    subtitle character varying(255),
    content text NOT NULL,
    image character varying(255),
    is_public boolean DEFAULT true NOT NULL,
    publication_date timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone
);


--
-- Name: news_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.news_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: news_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.news_id_seq OWNED BY public.news.id;

--
-- Name: news_tag; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.news_tag (
    news_id integer NOT NULL,
    tag_id integer NOT NULL
);



--
-- Name: news_status; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.news_status (
    id integer NOT NULL,
    name character varying(50) NOT NULL
);


--
-- Name: news_status_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.news_status_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: news_status_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.news_status_id_seq OWNED BY public.news_status.id;


--
-- Name: project; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project (
    id integer NOT NULL,
    name character varying(150) NOT NULL,
    description text,
    image character varying(255),
    link character varying(255),
    active boolean DEFAULT true NOT NULL
);


--
-- Name: project_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.project_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.project_id_seq OWNED BY public.project.id;


--
-- Name: role; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role (
    id integer NOT NULL,
    name character varying(50) NOT NULL,
    description text
);


--
-- Name: role_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.role_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: role_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.role_id_seq OWNED BY public.role.id;


--
-- Name: service; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.service (
    id integer NOT NULL,
    name character varying(150) NOT NULL,
    description text,
    image character varying(255),
    link character varying(255),
    active boolean DEFAULT true NOT NULL
);


--
-- Name: service_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.service_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: service_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.service_id_seq OWNED BY public.service.id;


--
-- Name: staff_member; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.staff_member (
    id integer NOT NULL,
    name character varying(150) NOT NULL,
    "position" character varying(100),
    photo character varying(255),
    description text,
    orden integer DEFAULT 0 NOT NULL
);


--
-- Name: staff_member_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.staff_member_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: staff_member_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.staff_member_id_seq OWNED BY public.staff_member.id;


--
-- Name: tag; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tag (
    id integer NOT NULL,
    name character varying(100) NOT NULL
);


--
-- Name: tag_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tag_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tag_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tag_id_seq OWNED BY public.tag.id;


--
-- Name: app_user id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.app_user ALTER COLUMN id SET DEFAULT nextval('public.app_user_id_seq'::regclass);


--
-- Name: audit_log id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_log ALTER COLUMN id SET DEFAULT nextval('public.audit_log_id_seq'::regclass);


--
-- Name: contact_request id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contact_request ALTER COLUMN id SET DEFAULT nextval('public.contact_request_id_seq'::regclass);


--
-- Name: contenido_sitio id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contenido_sitio ALTER COLUMN id SET DEFAULT nextval('public.contenido_sitio_id_seq'::regclass);


--
-- Name: enlaces_footer id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.enlaces_footer ALTER COLUMN id SET DEFAULT nextval('public.enlaces_footer_id_seq'::regclass);


--
-- Name: footer_info id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.footer_info ALTER COLUMN id SET DEFAULT nextval('public.footer_info_id_seq'::regclass);


--
-- Name: news id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news ALTER COLUMN id SET DEFAULT nextval('public.news_id_seq'::regclass);


--
-- Name: news_status id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news_status ALTER COLUMN id SET DEFAULT nextval('public.news_status_id_seq'::regclass);


--
-- Name: project id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project ALTER COLUMN id SET DEFAULT nextval('public.project_id_seq'::regclass);


--
-- Name: role id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role ALTER COLUMN id SET DEFAULT nextval('public.role_id_seq'::regclass);


--
-- Name: service id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service ALTER COLUMN id SET DEFAULT nextval('public.service_id_seq'::regclass);


--
-- Name: staff_member id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_member ALTER COLUMN id SET DEFAULT nextval('public.staff_member_id_seq'::regclass);


--
-- Name: tag id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tag ALTER COLUMN id SET DEFAULT nextval('public.tag_id_seq'::regclass);


--
-- Name: app_user app_user_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.app_user
    ADD CONSTRAINT app_user_email_key UNIQUE (email);


--
-- Name: app_user app_user_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.app_user
    ADD CONSTRAINT app_user_pkey PRIMARY KEY (id);


--
-- Name: app_user app_user_username_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.app_user
    ADD CONSTRAINT app_user_username_key UNIQUE (username);


--
-- Name: audit_log audit_log_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_log
    ADD CONSTRAINT audit_log_pkey PRIMARY KEY (id);


--
-- Name: contact_request contact_request_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contact_request
    ADD CONSTRAINT contact_request_pkey PRIMARY KEY (id);


--
-- Name: contenido_sitio contenido_sitio_clave_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contenido_sitio
    ADD CONSTRAINT contenido_sitio_clave_key UNIQUE (clave);


--
-- Name: contenido_sitio contenido_sitio_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contenido_sitio
    ADD CONSTRAINT contenido_sitio_pkey PRIMARY KEY (id);


--
-- Name: enlaces_footer enlaces_footer_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.enlaces_footer
    ADD CONSTRAINT enlaces_footer_pkey PRIMARY KEY (id);


--
-- Name: footer_info footer_info_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.footer_info
    ADD CONSTRAINT footer_info_pkey PRIMARY KEY (id);


--
-- Name: news news_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news
    ADD CONSTRAINT news_pkey PRIMARY KEY (id);


--
-- Name: news_status news_status_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news_status
    ADD CONSTRAINT news_status_name_key UNIQUE (name);


--
-- Name: news_status news_status_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news_status
    ADD CONSTRAINT news_status_pkey PRIMARY KEY (id);


--
-- Name: project project_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project
    ADD CONSTRAINT project_pkey PRIMARY KEY (id);


--
-- Name: role role_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role
    ADD CONSTRAINT role_name_key UNIQUE (name);


--
-- Name: role role_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role
    ADD CONSTRAINT role_pkey PRIMARY KEY (id);


--
-- Name: service service_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service
    ADD CONSTRAINT service_pkey PRIMARY KEY (id);


--
-- Name: staff_member staff_member_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_member
    ADD CONSTRAINT staff_member_pkey PRIMARY KEY (id);


--
-- Name: tag tag_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tag
    ADD CONSTRAINT tag_name_key UNIQUE (name);


--
-- Name: tag tag_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tag
    ADD CONSTRAINT tag_pkey PRIMARY KEY (id);


--
-- Name: audit_log fk_audit_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_log
    ADD CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES public.app_user(id);


--
-- Name: news fk_news_author; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news
    ADD CONSTRAINT fk_news_author FOREIGN KEY (author_id) REFERENCES public.app_user(id);


--
-- Name: news fk_news_editor; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news
    ADD CONSTRAINT fk_news_editor FOREIGN KEY (editor_id) REFERENCES public.app_user(id);


--
-- Name: news fk_news_status; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news
    ADD CONSTRAINT fk_news_status FOREIGN KEY (status_id) REFERENCES public.news_status(id);


--
-- Name: news fk_news_tag; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news
    ADD CONSTRAINT fk_news_tag FOREIGN KEY (tag_id) REFERENCES public.tag(id);


--
-- Name: app_user fk_user_role; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.app_user
    ADD CONSTRAINT fk_user_role FOREIGN KEY (role_id) REFERENCES public.role(id);




--
-- Name: news_tag news_tag_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news_tag
    ADD CONSTRAINT news_tag_pkey PRIMARY KEY (news_id, tag_id);


--
-- Name: news_tag fk_news_tag_news; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news_tag
    ADD CONSTRAINT fk_news_tag_news FOREIGN KEY (news_id) REFERENCES public.news(id) ON DELETE CASCADE;


--
-- Name: news_tag fk_news_tag_tag; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news_tag
    ADD CONSTRAINT fk_news_tag_tag FOREIGN KEY (tag_id) REFERENCES public.tag(id) ON DELETE CASCADE;


--
-- Name: password_reset_token; Type: TABLE; Schema: public; Owner: -
-- Issue: #15 — Recuperar contraseña por correo
--

CREATE TABLE IF NOT EXISTS public.password_reset_token (
    id         BIGSERIAL    PRIMARY KEY,
    user_id    INTEGER      NOT NULL
                            REFERENCES public.app_user(id) ON DELETE CASCADE,
    token      CHAR(64)     NOT NULL,   -- hash SHA-256 del token plano
    expires_at TIMESTAMPTZ  NOT NULL,
    used       BOOLEAN      NOT NULL DEFAULT false,
    created_at TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_prt_token       ON public.password_reset_token (token);
CREATE INDEX IF NOT EXISTS idx_prt_user_id     ON public.password_reset_token (user_id);
CREATE INDEX IF NOT EXISTS idx_prt_expires_at  ON public.password_reset_token (expires_at);


--
-- Name: credit_member; Type: TABLE; Schema: public; Owner: -
-- Issue: Créditos editables — Super Admin (nombre, cargo, correo de contacto)
-- Nota: el seed de datos base vive en config/migrations/004_credit_member.sql
--

CREATE TABLE public.credit_member (
    id integer NOT NULL,
    "key" character varying(60) NOT NULL,
    name character varying(150) NOT NULL,
    role character varying(100) NOT NULL,
    email character varying(150) NOT NULL,
    orden integer DEFAULT 0 NOT NULL
);


--
-- Name: credit_member_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.credit_member_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

ALTER SEQUENCE public.credit_member_id_seq OWNED BY public.credit_member.id;

ALTER TABLE ONLY public.credit_member
    ADD CONSTRAINT credit_member_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.credit_member
    ADD CONSTRAINT credit_member_key_key UNIQUE ("key");

ALTER TABLE ONLY public.credit_member
    ALTER COLUMN id SET DEFAULT nextval('public.credit_member_id_seq'::regclass);



--
-- Name: password_reset_request; Type: TABLE; Schema: public; Owner: -
-- Issue: #79 — Bloqueo de spam en recuperación de contraseña
--
-- Registra cada solicitud de recuperación (exista o no el correo) para
-- aplicar un cooldown entre envíos, sin filtrar si una cuenta existe.
--

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


--
-- Name: form_submission; Type: TABLE; Schema: public
-- Issue: SEC-03 — Anti-spam en formularios públicos
--
-- Registra cada envío de un formulario público (contacto, créditos,
-- consultas por API) para aplicar un límite por IP y por correo.
--
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


--
-- Indices de Rendimiento (Performance Indexes)
--

-- Indices en Noticias (news): por estado y fecha, por fecha de publicacion, por autor, por editor y por titulo
CREATE INDEX idx_news_status_published ON public.news USING btree (status_id, publication_date DESC);
CREATE INDEX idx_news_published_at ON public.news USING btree (publication_date DESC);
CREATE INDEX idx_news_author_created ON public.news USING btree (author_id, created_at DESC);
CREATE INDEX idx_news_editor ON public.news USING btree (editor_id);
CREATE INDEX idx_news_title ON public.news USING btree (title);

-- Indices en Registro de Auditoria (audit_log): por usuario, por entidad y por fecha
CREATE INDEX idx_audit_log_user_created ON public.audit_log USING btree (user_id, created_at DESC);
CREATE INDEX idx_audit_log_entity ON public.audit_log USING btree (entity, entity_id);
CREATE INDEX idx_audit_log_created_at ON public.audit_log USING btree (created_at DESC);

-- Indices en Enlaces del Footer (enlaces_footer): por columna y orden
CREATE INDEX idx_enlaces_footer_grupo_orden ON public.enlaces_footer USING btree (grupo, orden ASC);

-- Indices en Relacion Noticias-Tags (news_tag)
CREATE INDEX idx_news_tag_tag_id ON public.news_tag USING btree (tag_id);

-- Indices en Usuarios (app_user)
CREATE INDEX idx_app_user_role ON public.app_user USING btree (role_id);
CREATE INDEX idx_app_user_active ON public.app_user USING btree (active);

-- Indices en Proyectos y Servicios (project, service)
CREATE INDEX idx_project_active ON public.project USING btree (active);
CREATE INDEX idx_service_active ON public.service USING btree (active);

-- Indices en Staff (staff_member)
CREATE INDEX idx_staff_member_orden ON public.staff_member USING btree (orden ASC, id ASC);



--
-- PostgreSQL database dump complete
--

\unrestrict 7QUYGPQx6iLPUsV0sozO9TS0ZTuYLgWUJZTW2tkxIiS1j1K0zsMF7t8Z8iPmhXM


