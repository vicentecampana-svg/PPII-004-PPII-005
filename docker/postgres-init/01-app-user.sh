#!/usr/bin/env bash
# Issue #94 — Principio de mínimos privilegios (DB).
#
# Crea el usuario de aplicación (DB_USER/DB_PASSWORD) con los mínimos
# privilegios posibles y lo deja como dueño de la base de datos y del esquema
# public. Así config/schema.sql se carga con ese usuario y las tablas quedan
# a su nombre (owner), sin necesitar ser superusuario ni permisos globales.
#
# Solo se ejecuta la primera vez que se inicializa el volumen de datos
# (volumen vacío). Si el volumen ya existe, aplica la sección "Migrar un
# volumen existente" de README.dev.md o elimina el volumen con down -v.
set -euo pipefail

if [ -z "${DB_USER:-}" ] || [ -z "${DB_PASSWORD:-}" ]; then
    echo "[postgres-init] DB_USER/DB_PASSWORD sin definir; no se crea el rol de aplicación." >&2
    exit 0
fi

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    DO \$\$
    BEGIN
        IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = '${DB_USER}') THEN
            CREATE ROLE ${DB_USER} LOGIN PASSWORD '${DB_PASSWORD}'
                NOSUPERUSER NOCREATEDB NOCREATEROLE;
        END IF;
    END
    \$\$;

    ALTER DATABASE "${POSTGRES_DB}" OWNER TO ${DB_USER};
    ALTER SCHEMA public OWNER TO ${DB_USER};
EOSQL