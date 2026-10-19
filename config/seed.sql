-- =============================================================================
--  Datos semilla (seed) de la aplicación SFL ULS
--  Fecha: 2026-10-17
-- =============================================================================
--
--  BUG-05: este archivo contiene ÚNICAMENTE datos, separados del DDL de
--  config/schema.sql. Se carga DESPUÉS del esquema:
--
--      psql -U $DB_USER -d $POSTGRES_DB -f config/schema.sql
--      psql -U $DB_USER -d $POSTGRES_DB -f config/seed.sql
--
--  NOTA: las contraseñas de prueba de app_user son públicas (README.md) y
--  todas las cuentas quedan con must_change_password = false porque son
--  credenciales de administración inicial; se recomienda rotarlas.
--
-- =============================================================================

-- Data for Name: app_user; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.app_user (id, role_id, username, email, password, active, must_change_password) FROM stdin;
1	1	admin	admin@sfluls.cl	$2y$12$WdqdYonkoKQm1E8msfwkYu92sj5JykU5DUN8nbMqbBAfmqfQR84Yq	t	f
2	2	editor1	editor@sfluls.cl	$2y$12$QHeO3iESmYSn02nxJlXYUO.nCZD6bcOJpqzlBCSHAWtbGq3UFjLW6	t	f
3	3	redactor1	redactor@sfluls.cl	$2y$12$QHeO3iESmYSn02nxJlXYUO.nCZD6bcOJpqzlBCSHAWtbGq3UFjLW6	t	f
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
1	home	Sobre nosotros	Software Factory Lab es la fábrica de software de la Universidad de La Serena, donde estudiantes y profesionales desarrollan soluciones digitales reales para instituciones y empresas de la región, bajo estándares de la industria.	Misión, visión y objetivos	Formar talento tecnológico mediante la práctica en proyectos reales, entregando productos de calidad que aporten valor al desarrollo regional. Buscamos ser un referente en innovación, transferencia tecnológica y vinculación con el medio.	\N	\N	\N	\N	\N	\N	2026-08-19 04:39:32.817084	\N
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
6	Contenido	Iniciar sesión	/login	3	2026-08-19 04:39:32.819186	\N
\.


--
-- Data for Name: footer_info; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.footer_info (id, email, phone, address, copyright_text, social_facebook, social_linkedin, social_twitter, social_instagram, social_youtube) FROM stdin;
1	contacto@sfluls.cl			© 2026 Software Factory Lab	\N	\N	\N	\N	\N
\.


--
-- Data for Name: news; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.news (id, author_id, editor_id, status_id, tag_id, title, subtitle, content, image, publication_date, created_at, updated_at) FROM stdin;
1	1	\N	2	\N	SFL ULS inicia nuevo semestre de proyectos	Se abren las postulaciones para integrar el laboratorio	El laboratorio SFL ULS de la Universidad de La Serena inicia sus actividades del segundo semestre 2026. Se buscan estudiantes interesados en desarrollar proyectos reales.	noticia-1.jpg	2026-08-19 05:09:31.216664	2026-08-19 05:09:31.216664	\N
2	1	\N	1	\N	Hackathon de Innovación 2026	Evento de 48 horas de desarrollo intensivo	Se realizará una hackathon donde los equipos deberán crear soluciones tecnológicas para problemáticas regionales.	noticia-1.jpg	\N	2026-08-19 05:09:31.216664	\N
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
1	Sistema de Gestión Académica	Plataforma para gestión de notas y asistencia universitaria	proyecto-1.jpg	\N	t
2	App de Seguimiento de Salud	Aplicación móvil para monitoreo de signos vitales	proyecto-1.jpg	\N	t
3	Portal de Vinculación con el Medio	Sitio web que conecta proyectos estudiantiles con la comunidad	proyecto-1.jpg	\N	t
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
\.


--
-- Data for Name: staff_member; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.staff_member (id, name, "position", photo, description) FROM stdin;
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
\.


--
-- Name: app_user_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.app_user_id_seq', 3, true);


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

SELECT pg_catalog.setval('public.news_id_seq', 2, true);


--
-- Name: news_status_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.news_status_id_seq', 1, false);


--
-- Name: project_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.project_id_seq', 3, true);


--
-- Name: role_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.role_id_seq', 1, false);


--
-- Name: service_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.service_id_seq', 3, true);


--
-- Name: staff_member_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.staff_member_id_seq', 1, false);


--
-- Name: tag_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.tag_id_seq', 5, true);


--


-- Datos base de créditos (los mismos de config/migrations/004_credit_member.sql),
-- para que una instalación nueva y el CI tengan la tabla poblada.
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
