ALTER TABLE public.news
    ADD COLUMN IF NOT EXISTS author_display character varying(255);
