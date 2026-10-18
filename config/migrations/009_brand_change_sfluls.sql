-- =============================================================================
--  Migración: cambio de marca Tech Hub → SFL ULS en datos existentes
--  Issue:     Cambio de marca del sitio (UX-02 / Plan de acción)
--  Fecha:     2026-10-17
-- =============================================================================
--
--  NOTAS:
--  • Ejecutar UNA sola vez sobre bases de datos ya cargadas con la marca
--    anterior ("Tech Hub"). Las instalaciones nuevas usan config/seed.sql,
--    que ya trae la marca nueva.
--  • Solo modifica datos; no toca el esquema ni las credenciales del rol.
--
--  Uso:
--      psql -U $DB_USER -d $POSTGRES_DB -f config/migrations/009_brand_change_sfluls.sql
--
-- =============================================================================

-- 1. Correos de usuarios semilla: @techhub.cl → @sfluls.cl
UPDATE public.app_user
   SET email = REPLACE(email, '@techhub.cl', '@sfluls.cl')
 WHERE email LIKE '%@techhub.cl';

-- 2. Correo institucional del pie de página
UPDATE public.footer_info
   SET email = REPLACE(email, '@techhub.cl', '@sfluls.cl')
 WHERE email LIKE '%@techhub.cl';

-- 3. Noticias: nombre "Tech Hub ULS" → "SFL ULS" en título y contenido
UPDATE public.news
   SET title   = REPLACE(title, 'Tech Hub ULS', 'SFL ULS'),
       content = REPLACE(content, 'Tech Hub ULS', 'SFL ULS')
 WHERE title LIKE '%Tech Hub ULS%'
    OR content LIKE '%Tech Hub ULS%';

-- 4. Descripciones del staff
UPDATE public.staff_member
   SET description = REPLACE(description, 'Tech Hub ULS', 'SFL ULS')
 WHERE description LIKE '%Tech Hub ULS%';

-- 5. Enlaces de GitHub de proyectos con la organización antigua
UPDATE public.project
   SET link = REPLACE(link, 'github.com/techhub/', 'github.com/sfluls/')
 WHERE link LIKE '%github.com/techhub/%';
