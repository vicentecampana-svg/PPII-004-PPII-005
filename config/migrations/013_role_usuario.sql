-- RNF-SEC-002: rol Usuario del SRS (sección 1.3). Inicia sesión solo para
-- leer en el sitio las noticias publicadas con visibilidad Privada.
INSERT INTO public.role (id, name, description)
SELECT COALESCE(MAX(id), 0) + 1, 'usuario', 'Lectura de noticias privadas en el sitio'
FROM public.role
WHERE NOT EXISTS (SELECT 1 FROM public.role WHERE name = 'usuario');

SELECT pg_catalog.setval('public.role_id_seq', (SELECT MAX(id) FROM public.role), true);
