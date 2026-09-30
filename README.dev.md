# Entorno de Desarrollo

## Requisitos

- Git
- Docker Desktop

No necesitas instalar PHP, Apache, Postgres ni Composer, todo corre en Docker.

---

## 1. Clonar el repositorio

```bash
git clone https://github.com/vicentecampana-svg/PPII-004-PPII-005.git
cd PPII-004-PPII-005
```

---

## 2. Crear el `.env`

```powershell
Copy-Item .env.example .env
```

O en Linux y macOS:

```bash
cp .env.example .env
```

Edítalo con valores locales. `POSTGRES_USER` y `POSTGRES_PASSWORD` son para la
administración de PostgreSQL; la aplicación usa el usuario de permisos
limitados `DB_USER` y `DB_PASSWORD`.

Ejemplo:

```env
APP_ENV=development
APP_PORT=8080
APP_URL=http://localhost:8080

POSTGRES_DB=techhub
POSTGRES_USER=postgres
POSTGRES_PASSWORD=postgres
POSTGRES_PORT=5433

DB_USER=techhub_app
DB_PASSWORD=techhub_app_dev

MAIL_DRIVER=log
SMTP_HOST=
SMTP_PORT=587
SMTP_USER=
SMTP_PASS=
SMTP_FROM=no-reply@techhub.uls.cl
SMTP_FROM_NAME=TechHub ULS
```

---

## 3. Levantar el entorno

Tienes que tener abierto docker en tu pc.

```bash
docker compose -f docker-compose.dev.yml up --build -d
```

En la primera ejecución, PostgreSQL crea automáticamente `DB_USER` y le da
propiedad de la base de datos y del esquema. Los scripts `.sh` del proyecto
deben conservar finales de línea `LF`; `.gitattributes` ya configura esto para
Git. Si aparece `env: 'bash\\r': No such file or directory`, cambia el final
de línea del script afectado a `LF` en VS Code y vuelve a iniciar PostgreSQL.

Si el volumen ya existía antes de configurar `DB_USER` y `DB_PASSWORD`, los
scripts de inicialización no vuelven a ejecutarse automáticamente. Con
PostgreSQL iniciado, aplica el script del usuario una vez:

```bash
docker compose -f docker-compose.dev.yml exec postgres bash /docker-entrypoint-initdb.d/01-app-user.sh
```

---

## 4. Instalar dependencias

```bash
docker compose -f docker-compose.dev.yml exec web composer install
```

---
## 5. Cargar el schema en la base de datos

Estos comandos funcionan en Windows (PowerShell), Linux y macOS:

```powershell
docker compose -f docker-compose.dev.yml cp config/schema.sql postgres:/tmp/schema.sql
docker compose -f docker-compose.dev.yml exec postgres sh -c 'psql -v ON_ERROR_STOP=1 -U "$DB_USER" -d "$POSTGRES_DB" -f /tmp/schema.sql'
```

Para verificar las tablas:

```bash
docker compose -f docker-compose.dev.yml exec postgres sh -c 'psql -U "$DB_USER" -d "$POSTGRES_DB" -c "\\dt"'
```

La aplicación y el schema usan `DB_USER`; reserva `POSTGRES_USER` para tareas
administrativas.
## 6. Abrir el proyecto

```
http://localhost:8080
```

(o el puerto que hayas puesto en `APP_PORT`).

---

## 7. Verificar contenedores

```bash
docker compose -f docker-compose.dev.yml ps
```

Debe salir `web → Up` y `postgres → Up (healthy)`.

---

## 8. Detener el entorno

```bash
docker compose -f docker-compose.dev.yml down
```

---

## 9. Reconstruir (volver a iniciarlo cuando lo cierres)

```bash
docker compose -f docker-compose.dev.yml up --build
```

---

## 📚 Documentación Relacionada

- 📖 [Documentación Principal (`README.md`)](README.md)
- 🌐 [Guía de Despliegue en Producción / Hosting (`README.hosting.md`)](README.hosting.md)
- 🧪 [Guía de Pruebas Automatizadas (`README.test.md`)](README.test.md)
