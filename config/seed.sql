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
--  Las filas se ordenan según sus claves foráneas: roles/estados/etiquetas
--  antes de usuarios y noticias.
--
--  NOTA: las contraseñas de prueba de app_user son públicas (README.md) y
--  todas las cuentas quedan con must_change_password = false porque son
--  credenciales de administración inicial; se recomienda rotarlas.
--
-- =============================================================================

-- Data for Name: role; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.role (id, name, description) FROM stdin;
1	superadmin	Acceso total al sistema
2	admin	Gestión de contenido y usuarios
3	editor	Edición de contenido
4	redactor	Creación de noticias propias
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

COPY public.news (id, author_id, editor_id, status_id, tag_id, title, author_display, subtitle, content, image, publication_date, created_at, updated_at, is_public) FROM stdin;
1	1	\N	2	\N	SFL ULS inicia nuevo semestre de proyectos	\N	Se abren las postulaciones para integrar el laboratorio	El laboratorio SFL ULS de la Universidad de La Serena inicia sus actividades del segundo semestre 2026. Se buscan estudiantes interesados en desarrollar proyectos reales.	noticia-1.jpg	2026-08-19 05:09:31.216664	2026-08-19 05:09:31.216664	\N	t
2	1	\N	1	\N	Hackathon de Innovación 2026	\N	Evento de 48 horas de desarrollo intensivo	Se realizará una hackathon donde los equipos deberán crear soluciones tecnológicas para problemáticas regionales.	noticia-1.jpg	\N	2026-08-19 05:09:31.216664	\N	t
3	2	\N	2	\N	SFL ULS realiza entrevistas para seleccionar a integrantes de sus proyectos	Staff SFL	El proceso permitió conocer a los postulantes y conformar los equipos que participarán en la cartera de proyectos del SFL ULS.	El SFL ULS desarrolló un período de entrevistas para seleccionar a quienes participarán en sus proyectos. El proceso contó con cerca de 50 personas entrevistadas. Las entrevistas permitieron conocer la motivación, los conocimientos y la disposición al trabajo en equipo de las y los postulantes. Como resultado, se seleccionó a quienes se integrarán a los proyectos del SFL ULS.\n\nEl equipo agradece a todas las personas que postularon y felicita a quienes fueron seleccionadas.	\N	2026-10-30 00:00:01	2026-10-30 00:00:01	\N	t
4	2	\N	2	\N	SFL ULS implementa metodología QA para asegurar la calidad de sus desarrollos	Staff SFL	La iniciativa estandariza los procesos de revisión y prueba en toda la fábrica de software para entregar soluciones más confiables a la comunidad universitaria.	El SFL ULS implementó la metodología QA (proyecto PPII-001), un marco de trabajo que define cómo se asegura la calidad de cada solución desarrollada por el equipo. A partir de ahora, los proyectos de la fábrica siguen los mismos criterios de revisión antes de ser entregados.\n\nLa metodología establece estándares comunes para planificar pruebas, verificar requisitos, registrar y corregir errores. Esto permite detectar problemas en etapas tempranas, cuando corregirlos es más rápido y menos costoso.\n\nCon esta implementación, el SFL ULS avanza hacia estándares profesionales de la industria del software. Se esperan sistemas más estables y seguros para las unidades que los utilizan, y un proceso de trabajo más ordenado para el equipo.	\N	2026-10-30 00:00:02	2026-10-30 00:00:02	\N	t
5	2	\N	2	\N	PPII-002 | El Observatorio de Datos Sostenibles suma contenidos a su sitio web	Staff SFL	El SFL ULS trabaja en el poblamiento del sitio para que la comunidad acceda de forma clara a la información del Observatorio.	El SFL ULS está a cargo del poblamiento del sitio web del Observatorio de Datos Sostenibles. El trabajo consiste en incorporar y organizar contenidos, datos e información para que el sitio refleje la labor del Observatorio.\n\nCon esto, investigadores, estudiantes y público general contarán con un espacio actualizado para consultar sus datos y publicaciones.	\N	2026-10-30 00:00:03	2026-10-30 00:00:03	\N	t
6	2	\N	2	\N	PPII-005 | SFL ULS presenta su sitio web oficial	Staff SFL	El nuevo sitio consolida la identidad del SFL ULS y reúne en un solo lugar su portafolio de proyectos.	El SFL ULS cuenta con su propio sitio web oficial. El espacio presenta quiénes somos, cómo trabajamos y los proyectos que desarrollamos para la universidad.\n\nEl sitio funciona también como portafolio profesional: muestra el alcance del trabajo del equipo en desarrollo web, sistemas de gestión e innovación. Aquí se publicarán las noticias y avances de cada proyecto.	\N	2026-10-30 00:00:04	2026-10-30 00:00:04	\N	t
7	2	\N	2	\N	PPII-008 | El Departamento de Ingeniería Industrial actualiza su sitio web	Staff SFL	El SFL ULS renueva el sitio del Departamento para mejorar el acceso a su información académica e institucional.	El SFL ULS trabaja en la actualización del sitio web del Departamento de Ingeniería Industrial. La renovación apunta a ordenar sus contenidos y mejorar la experiencia de quienes lo visitan.\n\nEstudiantes, postulantes y académicos podrán encontrar con mayor facilidad información sobre programas, actividades y noticias del Departamento.	\N	2026-10-30 00:00:05	2026-10-30 00:00:05	\N	t
8	2	\N	2	\N	PPII-003 | Nuevo sistema optimiza la supervisión de alumnos en práctica	Staff SFL	La plataforma desarrollada por el SFL ULS automatiza el seguimiento de estudiantes en práctica profesional.	El SFL ULS desarrolla un Sistema de Supervisión de Alumnos en Práctica. Su objetivo es automatizar y optimizar el seguimiento que hoy realizan supervisores y coordinadores.\n\nEl sistema permitirá centralizar la información de cada práctica y reducir tareas manuales. Así, los equipos académicos podrán dedicar más tiempo al acompañamiento de los estudiantes.	\N	2026-10-30 00:00:06	2026-10-30 00:00:06	\N	t
9	2	\N	2	\N	PPII-011 | SFL ULS modela un módulo ERP para Ingeniería Comercial	Staff SFL	El proyecto acerca a la carrera a los sistemas de gestión empresarial que utilizan las organizaciones.	El SFL ULS trabaja en el modelamiento de un módulo ERP para la carrera de Ingeniería Comercial. Los sistemas ERP (Planificación de Recursos Empresariales) integran áreas como finanzas, ventas e inventario en una sola plataforma.\n\nEl modelamiento define los procesos, flujos de información y requisitos del módulo según las necesidades de la carrera.	\N	2026-10-30 00:00:07	2026-10-30 00:00:07	\N	t
10	2	\N	2	\N	PPII-012 | Plataforma de visualización de datos: dashboards para decidir mejor	Staff SFL	El SFL ULS implementa una plataforma de dashboards estratégicos que transforma datos en información para la toma de decisiones.	El SFL ULS implementa una Plataforma de Visualización de Datos con dashboards estratégicos. La herramienta presenta indicadores clave de forma visual, clara y actualizada.\n\nCon ella, las autoridades y equipos de gestión podrán analizar información de manera rápida y tomar decisiones basadas en datos.	\N	2026-10-30 00:00:08	2026-10-30 00:00:08	\N	t
11	2	\N	2	\N	PPII-007 | Tecnología contra las plagas: SFL ULS desarrolla un modelo de control mediante IoT	Staff SFL	El proyecto combina hardware y software para monitorear y controlar plagas, aplicando la ingeniería a un problema concreto.	El SFL ULS desarrolla un Modelo de Control de Plagas basado en Internet de las Cosas (IoT). La propuesta integra dispositivos físicos conectados con un software que recibe y procesa la información que estos capturan.\n\nEl proyecto muestra cómo la ingeniería puede responder a desafíos del entorno con soluciones tecnológicas propias.	\N	2026-10-30 00:00:09	2026-10-30 00:00:09	\N	t
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
1	Manuel Huenche	QA	\N	\N
2	Adolfo Andrés Toledo Oyarce	QAS Lead	\N	\N
3	Brandon Muñoz	QAS	\N	\N
4	Edinson Godoy	QAS	\N	\N
5	Cristian Cubillos	Tech Lead	\N	\N
6	Bastian Rojas	Technical Coordinator	\N	\N
7	Luis Felipe Molina	Business Coordinator	\N	\N
8	José P. Villanueva V.	Chief Information Officer	\N	\N
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

SELECT pg_catalog.setval('public.news_id_seq', 11, true);


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

SELECT pg_catalog.setval('public.staff_member_id_seq', 8, true);


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
