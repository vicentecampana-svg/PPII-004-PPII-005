--
-- PostgreSQL database dump
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
    must_change_password boolean DEFAULT false NOT NULL
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
    user_id integer NOT NULL,
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
    description text
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
-- Data for Name: app_user; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.app_user (id, role_id, username, email, password, active, must_change_password) FROM stdin;
1	1	admin	admin@techhub.cl	$2y$12$WdqdYonkoKQm1E8msfwkYu92sj5JykU5DUN8nbMqbBAfmqfQR84Yq	t	f
2	2	editor1	editor@techhub.cl	\\\\\\.svQph9Te2jhy64UxDg5tU.qpxvsKhBTzXRZVCf.	t	f
3	3	redactor1	redactor@techhub.cl	\\\\\\.svQph9Te2jhy64UxDg5tU.qpxvsKhBTzXRZVCf.	t	f
5	3	testu	test@test.cl	$2y$12$no5d/WikL3JdHZOV2jGPcejco.4V096zMLPgqrbCdIwBylvoOSdUG	t	f
\.


--
-- Data for Name: audit_log; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.audit_log (id, user_id, action, entity, entity_id, details, created_at) FROM stdin;
\.


--
-- Data for Name: contact_request; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.contact_request (id, name, email, phone, subject, message, sent_at, status) FROM stdin;
1	Juan	juan@test.cl	\N	\N	Hola	2026-08-19 05:22:11.268924	pendiente
\.


--
-- Data for Name: contenido_sitio; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.contenido_sitio (id, clave, sobre_titulo, sobre_texto, mision_titulo, mision_texto, vision_titulo, vision_texto, objetivos_titulo, objetivos_texto, politicas_titulo, politicas_texto, created_at, updated_at) FROM stdin;
1	home	Sobre nosotros	TECH HUB ULS es el laboratorio de desarrollo de software de la Universidad de La Serena. Nuestro equipo trabaja en proyectos reales que apoyan a la comunidad universitaria y regional.	Misi?n, visi?n y objetivos	Nuestra misi?n es formar profesionales competentes mediante la experiencia pr?ctica en desarrollo de software, fomentando la innovaci?n, el trabajo en equipo y la vinculaci?n con el medio.	\N	\N	\N	\N	\N	\N	2026-08-19 04:39:32.817084	\N
\.


--
-- Data for Name: enlaces_footer; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.enlaces_footer (id, grupo, etiqueta, url, orden, created_at, updated_at) FROM stdin;
1	Sitio	Inicio	/	1	2026-08-19 04:39:32.819186	\N
2	Sitio	Proyectos	/proyectos	2	2026-08-19 04:39:32.819186	\N
3	Sitio	Staff	/staff	3	2026-08-19 04:39:32.819186	\N
4	Contenido	Noticias	/noticias	1	2026-08-19 04:39:32.819186	\N
5	Contenido	Contacto	/contacto	2	2026-08-19 04:39:32.819186	\N
6	Contenido	Iniciar sesi?n	/login	3	2026-08-19 04:39:32.819186	\N
\.


--
-- Data for Name: footer_info; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.footer_info (id, email, phone, address, copyright_text, social_facebook, social_linkedin, social_twitter, social_instagram, social_youtube) FROM stdin;
1	contacto@techhub.cl	+56912345678	Av. Raúl Valenzuela 123, La Serena	© 2026 Tech Hub ULS	\N	\N	\N	\N	\N
\.


--
-- Data for Name: news; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.news (id, author_id, editor_id, status_id, tag_id, title, subtitle, content, image, publication_date, created_at, updated_at) FROM stdin;
1	1	\N	2	\N	Tech Hub ULS inicia nuevo semestre de proyectos	Se abren las postulaciones para integrar el laboratorio	El laboratorio Tech Hub ULS de la Universidad de La Serena inicia sus actividades del segundo semestre 2026. Se buscan estudiantes interesados en desarrollar proyectos reales.	noticia-1.jpg	2026-08-19 05:09:31.216664	2026-08-19 05:09:31.216664	\N
2	1	\N	1	\N	Hackathon de Innovación 2026	Evento de 48 horas de desarrollo intensivo	Se realizará una hackathon donde los equipos deberán crear soluciones tecnológicas para problemáticas regionales.	noticia-1.jpg	\N	2026-08-19 05:09:31.216664	\N
3	1	\N	1	\N	Test API	Sub	Contenido	\N	\N	2026-08-19 05:12:26.010673	\N
4	1	\N	1	\N	Test API	Sub	Contenido	\N	\N	2026-08-19 05:22:11.076139	\N
5	1	\N	1	\N	Delete Test	x	x	\N	\N	2026-08-19 05:23:58.539241	\N
\.


--
-- Data for Name: news_status; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.news_status (id, name) FROM stdin;
1	pendiente
2	publicada
3	archivada
\.


--
-- Data for Name: project; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.project (id, name, description, image, link, active) FROM stdin;
1	Sistema de Gestión Académica	Plataforma para gestión de notas y asistencia universitaria	proyecto-1.jpg	https://github.com/techhub/gestion-academica	t
2	App de Seguimiento de Salud	Aplicación móvil para monitoreo de signos vitales	proyecto-1.jpg	https://github.com/techhub/salud-app	t
3	Portal de Vinculación con el Medio	Sitio web que conecta proyectos estudiantiles con la comunidad	proyecto-1.jpg	\N	t
4	Test Project	Desc	\N	\N	t
5	Test Project	Desc	\N	\N	t
\.


--
-- Data for Name: role; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.role (id, name, description) FROM stdin;
1	superadmin	Acceso total al sistema
2	admin	Gestión de contenido y usuarios
3	editor	Edición de contenido
4	redactor	Creación de noticias propias
\.


--
-- Data for Name: service; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.service (id, name, description, image, link, active) FROM stdin;
1	Desarrollo de Software a la Medida	Creamos soluciones digitales adaptadas a las necesidades de tu organización	proyecto-1.jpg	\N	t
2	Consultoría en Tecnología	Asesoría técnica para la transformación digital de empresas y organizaciones	proyecto-1.jpg	\N	t
3	Capacitación en Programación	Cursos y talleres de programación para estudiantes y profesionales	proyecto-1.jpg	\N	t
4	Test Service	Desc	\N	\N	t
5	Test Service	Desc	\N	\N	t
\.


--
-- Data for Name: staff_member; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.staff_member (id, name, "position", photo, description) FROM stdin;
1	Carlos Méndez	Director del Laboratorio	staff-1.jpg	Ingeniero en Computación, Magíster en Informática. Líder del Tech Hub ULS.
2	Ana Sofía Riquelme	Coordinadora de Proyectos	staff-1.jpg	Ingeniera en Computación con experiencia en gestión de desarrollo de software.
3	Pedro Contreras	Desarrollador Full Stack	staff-1.jpg	Especialista en PHP, JavaScript y bases de datos PostgreSQL.
4	Test Staff	Dev	\N	\N
5	Test Staff	Dev	\N	\N
\.


--
-- Data for Name: tag; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.tag (id, name) FROM stdin;
1	Desarrollo Web
2	Inteligencia Artificial
3	Ciberseguridad
4	Cloud Computing
5	DevOps
6	TestTag
7	TestTag2
\.


--
-- Name: app_user_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.app_user_id_seq', 5, true);


--
-- Name: audit_log_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.audit_log_id_seq', 1, false);


--
-- Name: contact_request_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.contact_request_id_seq', 1, true);


--
-- Name: contenido_sitio_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.contenido_sitio_id_seq', 1, true);


--
-- Name: enlaces_footer_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.enlaces_footer_id_seq', 6, true);


--
-- Name: footer_info_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.footer_info_id_seq', 1, false);


--
-- Name: news_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.news_id_seq', 5, true);


--
-- Name: news_status_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.news_status_id_seq', 1, false);


--
-- Name: project_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.project_id_seq', 5, true);


--
-- Name: role_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.role_id_seq', 1, false);


--
-- Name: service_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.service_id_seq', 5, true);


--
-- Name: staff_member_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.staff_member_id_seq', 5, true);


--
-- Name: tag_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.tag_id_seq', 7, true);


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
-- Indices de Rendimiento (Performance Indexes)
--

-- Indices en Noticias (news): por estado y fecha, por fecha de publicacion, por autor, por editor y por titulo
CREATE INDEX idx_news_status_published ON public.news USING btree (status_id, published_at DESC);
CREATE INDEX idx_news_published_at ON public.news USING btree (published_at DESC);
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


