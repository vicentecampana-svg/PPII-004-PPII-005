-- Datos semilla del entorno de desarrollo, extra?dos del respaldo local schema.sql.
-- Cargar DESPU?S de config/schema.sql. Este snapshot incluye hashes de contrase?as
-- y registros personales; mantenerlo privado y no usarlo como datos de producci?n.
-- Los COPY se ordenan para respetar las claves for?neas del esquema.

-- Data for Name: role; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.role (id, name, description) FROM stdin;
1	superadmin	Acceso total al sistema
2	admin	Gestión de contenido y usuarios
3	editor	Edición de contenido
4	redactor	Creación de noticias propias
\.



-- Data for Name: news_status; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.news_status (id, name) FROM stdin;
1	pendiente
2	publicada
3	archivada
\.



-- Data for Name: tag; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.tag (id, name) FROM stdin;
1	Desarrollo Web
2	Inteligencia Artificial
3	Ciberseguridad
4	Cloud Computing
5	DevOps
\.



-- Data for Name: app_user; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.app_user (id, role_id, username, email, password, active, must_change_password, deleted_at) FROM stdin;
5	4	manuel.huenche	manuel.huenche@userena.cl	$2y$12$PvhKWfLDwAIoZLSoiiQ/C.aQaS.HEYPXJ2.fbuolODN5RbXgLF/4G	t	t	\N
6	4	adolfo.toledo	adolfo.toledo@userena.cl	$2y$12$QcPCONOYmFgmWdwa0IVXLevh9Zh70jsbpf728vaBXFyn9KTO6kJ12	t	t	\N
7	4	egodoy7	egodoy7@alumnosuls.cl	$2y$12$QVxrz1M5WFykt10khMxW7.EzCEusJIpwzDTJZENhnG89BJiLgyheu	t	t	\N
8	4	cristian.cubillos	cristian.cubillos@userena.cl	$2y$12$GuMgQ5AYvUD2U.8zaPihIus/1BPTrR/95xWVeCN/T7r8i1SYGJOnO	t	t	\N
9	4	bastian.rojasp	bastian.rojasp@userena.cl	$2y$12$jhOUk0LtaqBmvYny8xH0CeD4pBCjLQRLT.y8P3zzXVQLhmu309.8y	t	t	\N
10	4	luis.molinac	luis.molinac@userena.cl	$2y$12$M8iLMx5BECoiUZu6xpLGU.qgNp2pP.Es3lWSPXZJr2rj/BU2VAm.S	t	t	\N
11	4	jose.villanuevav1	jose.villanuevav1@userena.cl	$2y$12$ep1gDZr9gaXJdzYdjRwDCeHG7btscIn//VT5AQ888o93otFwwgX2S	t	t	\N
1	1	StaffSFL	staffsfl@sfluls.cl	$2y$12$j04.EdE.mgQgxgDmfUk8guG8xJMP5GJRHU5J7QIB5kZwVJO0D49ti	t	f	\N
\.



-- Data for Name: audit_log; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.audit_log (id, user_id, action, entity, entity_id, details, created_at, ip) FROM stdin;
1	1	actualizar	user	4	Usuario actualizado: StaffSFL	2026-10-30 18:40:55	172.18.0.1
2	1	cambiar_password	user	4	Contraseña cambiada por el usuario	2026-10-30 18:40:55	172.18.0.1
3	1	actualizar	user	1	Usuario actualizado: StaffSFL	2026-10-30 19:12:33	172.18.0.1
4	1	cambiar_password	user	1	Contraseña cambiada por el usuario	2026-10-30 19:12:33	172.18.0.1
\.



-- Data for Name: contact_request; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.contact_request (id, name, email, phone, subject, message, sent_at, status, notified_at) FROM stdin;
1	Juan	juan@test.cl	\N	\N	Hola	2026-08-19 05:22:11.268924	pendiente	\N
\.



-- Data for Name: contenido_sitio; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.contenido_sitio (id, clave, sobre_titulo, sobre_texto, mision_titulo, mision_texto, vision_titulo, vision_texto, objetivos_titulo, objetivos_texto, politicas_titulo, politicas_texto, created_at, updated_at) FROM stdin;
1	home	Sobre nosotros	Software Factory Lab es la fábrica de software de la Universidad de La Serena, donde estudiantes y profesionales desarrollan soluciones digitales reales para instituciones y empresas de la región, bajo estándares de la industria.	Misión, visión y objetivos	Formar talento tecnológico mediante la práctica en proyectos reales, entregando productos de calidad que aporten valor al desarrollo regional. Buscamos ser un referente en innovación, transferencia tecnológica y vinculación con el medio.	\N	\N	\N	\N	\N	\N	2026-08-19 04:39:32.817084	\N
\.



-- Data for Name: credit_member; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.credit_member (id, key, name, role, email, orden) FROM stdin;
1	vicente-campana	Vicente Campaña	Project Manager	vicente.campana@userena.cl	1
2	wilmary-guedez	Wilmary Guedez	Ingeniera en Requerimientos	wilmary.guedez@userena.cl	2
3	esteban-zepeda	Esteban Zepeda	Diseño UX/UI	esteban.zepeda@userena.cl	3
4	bastian-pizarro	Bastian Pizarro	Diseño UX/UI	bastian.pizarro@userena.cl	4
5	maximiliano-saavedra	Maximiliano Saavedra	Desarrollo Backend y Base de datos	maximiliano.saavedra@userena.cl	5
6	agustina-lopez	Agustina Lopez	Desarrollo Frontend	agustina.lopez@userena.cl	6
7	basthian-valenzuela	Basthian Valenzuela	Quality Assurance	basthian.valenzuela@userena.cl	7
8	pedro-rojas	Pedro Rojas	Apoyo Desarrollo	pedro.rojasm3@userena.cl	8
\.



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



-- Data for Name: footer_info; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.footer_info (id, email, phone, address, copyright_text, social_facebook, social_linkedin, social_twitter, social_instagram, social_youtube) FROM stdin;
1	contacto@sfluls.cl			© 2026 Software Factory Lab	\N	\N	\N	\N	\N
\.



-- Data for Name: form_rejection; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.form_rejection (id, form, reason, ip, email, created_at) FROM stdin;
\.



-- Data for Name: form_submission; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.form_submission (id, form, ip, email, created_at) FROM stdin;
\.



-- Data for Name: login_attempt; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.login_attempt (id, ip, email, attempted_at, success) FROM stdin;
1	172.18.0.1	staffsfl@sfluls.cl	2026-10-30 18:39:58.737463	t
2	172.18.0.1	staffsfl@sfluls.cl	2026-10-30 19:12:08.220905	t
3	172.18.0.1	staffsfl@sfluls.cl	2026-10-30 19:12:52.230659	t
4	172.18.0.1	manuel.huenche@userena.cl	2026-10-30 19:14:32.601126	t
5	172.18.0.1	staffsfl@sfluls.cl	2026-10-30 19:16:22.90266	t
\.



-- Data for Name: mail_log; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.mail_log (id, scope, delivered, sent_at) FROM stdin;
\.



-- Data for Name: news; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.news (id, author_id, editor_id, status_id, tag_id, title, author_display, subtitle, content, image, is_public, publication_date, created_at, updated_at) FROM stdin;
7	1	\N	2	\N	PPII-008 | El Departamento de Ingeniería Industrial actualiza su sitio web	Staff SFL	El SFL ULS renueva el sitio del Departamento para mejorar el acceso a su información académica e institucional.	El SFL ULS trabaja en la actualización del sitio web del Departamento de Ingeniería Industrial. La renovación apunta a ordenar sus contenidos y mejorar la experiencia de quienes lo visitan.\n\nEstudiantes, postulantes y académicos podrán encontrar con mayor facilidad información sobre programas, actividades y noticias del Departamento.	\N	t	2026-10-30 00:00:05	2026-10-30 00:00:05	\N
6	1	\N	2	\N	PPII-005 | SFL ULS presenta su sitio web oficial	Staff SFL	El nuevo sitio consolida la identidad del SFL ULS y reúne en un solo lugar su portafolio de proyectos.	El SFL ULS cuenta con su propio sitio web oficial. El espacio presenta quiénes somos, cómo trabajamos y los proyectos que desarrollamos para la universidad.\n\nEl sitio funciona también como portafolio profesional: muestra el alcance del trabajo del equipo en desarrollo web, sistemas de gestión e innovación. Aquí se publicarán las noticias y avances de cada proyecto.	\N	t	2026-10-30 00:00:04	2026-10-30 00:00:04	\N
5	1	\N	2	\N	PPII-002 | El Observatorio de Datos Sostenibles suma contenidos a su sitio web	Staff SFL	El SFL ULS trabaja en el poblamiento del sitio para que la comunidad acceda de forma clara a la información del Observatorio.	El SFL ULS está a cargo del poblamiento del sitio web del Observatorio de Datos Sostenibles. El trabajo consiste en incorporar y organizar contenidos, datos e información para que el sitio refleje la labor del Observatorio.\n\nCon esto, investigadores, estudiantes y público general contarán con un espacio actualizado para consultar sus datos y publicaciones.	\N	t	2026-10-30 00:00:03	2026-10-30 00:00:03	\N
4	1	\N	2	\N	SFL ULS implementa metodología QA para asegurar la calidad de sus desarrollos	Staff SFL	La iniciativa estandariza los procesos de revisión y prueba en toda la fábrica de software para entregar soluciones más confiables a la comunidad universitaria.	El SFL ULS implementó la metodología QA (proyecto PPII-001), un marco de trabajo que define cómo se asegura la calidad de cada solución desarrollada por el equipo. A partir de ahora, los proyectos de la fábrica siguen los mismos criterios de revisión antes de ser entregados.\n\nLa metodología establece estándares comunes para planificar pruebas, verificar requisitos, registrar y corregir errores. Esto permite detectar problemas en etapas tempranas, cuando corregirlos es más rápido y menos costoso.\n\nCon esta implementación, el SFL ULS avanza hacia estándares profesionales de la industria del software. Se esperan sistemas más estables y seguros para las unidades que los utilizan, y un proceso de trabajo más ordenado para el equipo.	\N	t	2026-10-30 00:00:02	2026-10-30 00:00:02	\N
3	1	\N	2	\N	SFL ULS realiza entrevistas para seleccionar a integrantes de sus proyectos	Staff SFL	El proceso permitió conocer a los postulantes y conformar los equipos que participarán en la cartera de proyectos del SFL ULS.	El SFL ULS desarrolló un período de entrevistas para seleccionar a quienes participarán en sus proyectos. El proceso contó con cerca de 50 personas entrevistadas. Las entrevistas permitieron conocer la motivación, los conocimientos y la disposición al trabajo en equipo de las y los postulantes. Como resultado, se seleccionó a quienes se integrarán a los proyectos del SFL ULS.\n\nEl equipo agradece a todas las personas que postularon y felicita a quienes fueron seleccionadas.	\N	t	2026-10-30 00:00:01	2026-10-30 00:00:01	\N
1	1	\N	2	\N	SFL ULS inicia nuevo semestre de proyectos	\N	Se abren las postulaciones para integrar el laboratorio	El laboratorio SFL ULS de la Universidad de La Serena inicia sus actividades del segundo semestre 2026. Se buscan estudiantes interesados en desarrollar proyectos reales.	noticia-1.jpg	t	2026-08-19 05:09:31.216664	2026-08-19 05:09:31.216664	\N
2	1	\N	1	\N	Hackathon de Innovación 2026	\N	Evento de 48 horas de desarrollo intensivo	Se realizará una hackathon donde los equipos deberán crear soluciones tecnológicas para problemáticas regionales.	noticia-1.jpg	t	\N	2026-08-19 05:09:31.216664	\N
11	1	\N	2	\N	PPII-007 | Tecnología contra las plagas: SFL ULS desarrolla un modelo de control mediante IoT	Staff SFL	El proyecto combina hardware y software para monitorear y controlar plagas, aplicando la ingeniería a un problema concreto.	El SFL ULS desarrolla un Modelo de Control de Plagas basado en Internet de las Cosas (IoT). La propuesta integra dispositivos físicos conectados con un software que recibe y procesa la información que estos capturan.\n\nEl proyecto muestra cómo la ingeniería puede responder a desafíos del entorno con soluciones tecnológicas propias.	\N	t	2026-10-30 00:00:09	2026-10-30 00:00:09	\N
10	1	\N	2	\N	PPII-012 | Plataforma de visualización de datos: dashboards para decidir mejor	Staff SFL	El SFL ULS implementa una plataforma de dashboards estratégicos que transforma datos en información para la toma de decisiones.	El SFL ULS implementa una Plataforma de Visualización de Datos con dashboards estratégicos. La herramienta presenta indicadores clave de forma visual, clara y actualizada.\n\nCon ella, las autoridades y equipos de gestión podrán analizar información de manera rápida y tomar decisiones basadas en datos.	\N	t	2026-10-30 00:00:08	2026-10-30 00:00:08	\N
9	1	\N	2	\N	PPII-011 | SFL ULS modela un módulo ERP para Ingeniería Comercial	Staff SFL	El proyecto acerca a la carrera a los sistemas de gestión empresarial que utilizan las organizaciones.	El SFL ULS trabaja en el modelamiento de un módulo ERP para la carrera de Ingeniería Comercial. Los sistemas ERP (Planificación de Recursos Empresariales) integran áreas como finanzas, ventas e inventario en una sola plataforma.\n\nEl modelamiento define los procesos, flujos de información y requisitos del módulo según las necesidades de la carrera.	\N	t	2026-10-30 00:00:07	2026-10-30 00:00:07	\N
8	1	\N	2	\N	PPII-003 | Nuevo sistema optimiza la supervisión de alumnos en práctica	Staff SFL	La plataforma desarrollada por el SFL ULS automatiza el seguimiento de estudiantes en práctica profesional.	El SFL ULS desarrolla un Sistema de Supervisión de Alumnos en Práctica. Su objetivo es automatizar y optimizar el seguimiento que hoy realizan supervisores y coordinadores.\n\nEl sistema permitirá centralizar la información de cada práctica y reducir tareas manuales. Así, los equipos académicos podrán dedicar más tiempo al acompañamiento de los estudiantes.	\N	t	2026-10-30 00:00:06	2026-10-30 00:00:06	\N
\.



-- Data for Name: news_tag; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.news_tag (news_id, tag_id) FROM stdin;
\.



-- Data for Name: password_reset_request; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.password_reset_request (id, email, ip, requested_at) FROM stdin;
\.



-- Data for Name: password_reset_token; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.password_reset_token (id, user_id, token, expires_at, used, created_at) FROM stdin;
\.



-- Data for Name: project; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.project (id, name, description, image, link, active) FROM stdin;
1	Sistema de Gestión Académica	Plataforma para gestión de notas y asistencia universitaria	proyecto-1.jpg	\N	t
2	App de Seguimiento de Salud	Aplicación móvil para monitoreo de signos vitales	proyecto-1.jpg	\N	t
3	Portal de Vinculación con el Medio	Sitio web que conecta proyectos estudiantiles con la comunidad	proyecto-1.jpg	\N	t
\.



-- Data for Name: service; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.service (id, name, description, image, link, active) FROM stdin;
1	Desarrollo de Software a la Medida	Creamos soluciones digitales adaptadas a las necesidades de tu organización	proyecto-1.jpg	\N	t
2	Consultoría en Tecnología	Asesoría técnica para la transformación digital de empresas y organizaciones	proyecto-1.jpg	\N	t
3	Capacitación en Programación	Cursos y talleres de programación para estudiantes y profesionales	proyecto-1.jpg	\N	t
\.



-- Data for Name: staff_member; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.staff_member (id, name, "position", photo, description, orden) FROM stdin;
1	Manuel Huenche	QA	\N	\N	0
2	Adolfo Andrés Toledo Oyarce	QAS Lead	\N	\N	0
3	Brandon Muñoz	QAS	\N	\N	0
4	Edinson Godoy	QAS	\N	\N	0
5	Cristian Cubillos	Tech Lead	\N	\N	0
6	Bastian Rojas	Technical Coordinator	\N	\N	0
7	Luis Felipe Molina	Business Coordinator	\N	\N	0
8	José P. Villanueva V.	Chief Information Officer	\N	\N	0
\.



-- Sequence values
--

SELECT pg_catalog.setval('public.app_user_id_seq', 11, true);
SELECT pg_catalog.setval('public.audit_log_id_seq', 4, true);
SELECT pg_catalog.setval('public.contact_request_id_seq', 1, true);
SELECT pg_catalog.setval('public.contenido_sitio_id_seq', 1, true);
SELECT pg_catalog.setval('public.credit_member_id_seq', 8, true);
SELECT pg_catalog.setval('public.enlaces_footer_id_seq', 6, true);
SELECT pg_catalog.setval('public.footer_info_id_seq', 1, false);
SELECT pg_catalog.setval('public.form_rejection_id_seq', 1, false);
SELECT pg_catalog.setval('public.form_submission_id_seq', 1, false);
SELECT pg_catalog.setval('public.login_attempt_id_seq', 5, true);
SELECT pg_catalog.setval('public.mail_log_id_seq', 1, false);
SELECT pg_catalog.setval('public.news_id_seq', 11, true);
SELECT pg_catalog.setval('public.news_status_id_seq', 1, false);
SELECT pg_catalog.setval('public.password_reset_request_id_seq', 1, false);
SELECT pg_catalog.setval('public.password_reset_token_id_seq', 1, false);
SELECT pg_catalog.setval('public.project_id_seq', 3, true);
SELECT pg_catalog.setval('public.role_id_seq', 1, false);
SELECT pg_catalog.setval('public.service_id_seq', 3, true);
SELECT pg_catalog.setval('public.staff_member_id_seq', 8, true);
SELECT pg_catalog.setval('public.tag_id_seq', 5, true);
