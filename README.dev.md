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

Edítalo con los valores que quieras (usuario, password, puertos, etc). El
`docker-compose.dev.yml` se encarga de pasarle a PHP los valores que la
aplicación necesita (lista explícita en `environment:`: `APP_URL`, `PG_*`,
`MAIL_*`, `SMTP_*`, `CONTACT_NOTIFY_EMAIL`, etc.), así que no hay que tocar
nada más. El contenedor `web` **no** recibe `POSTGRES_USER`/`POSTGRES_PASSWORD`
ni el resto de variables del `.env` (issue #94).

> **Usuarios de la base de datos**: hay dos roles distintos.
> - `POSTGRES_USER` / `POSTGRES_PASSWORD`: superusuario de bootstrap, **solo**
>   se usa para inicializar el servidor y tareas administrativas.
> - `DB_USER` / `DB_PASSWORD`: usuario de la aplicación (principio de mínimos
>   privilegios). La app se conecta siempre con este rol, que es dueño de la
>   base de datos y no es superusuario. Se crea automáticamente en el primer
>   arranque de Postgres (`docker/postgres-init/01-app-user.sh`).

Ejemplo:

```env
APP_ENV=development
APP_PORT=8080
APP_URL=http://localhost:8080

POSTGRES_DB=techhub
POSTGRES_USER=postgres
POSTGRES_PASSWORD=postgres
POSTGRES_PORT=5433

DB_USER=techhub_app1
DB_PASSWORD=Clave2026Local

MAIL_DRIVER=log
SMTP_HOST=
SMTP_PORT=587
SMTP_USER=
SMTP_PASS=
SMTP_FROM=no-reply@techhub.uls.cl
SMTP_FROM_NAME=TechHub ULS

CONTACT_NOTIFY_EMAIL=
TICKET_PLATFORM_URL=
```

---

## 3. Levantar el entorno

Tienes que tener abierto docker en tu pc.

```bash
docker compose -f docker-compose.dev.yml up --build -d
```

---

## 4. Instalar dependencias

```bash
docker compose -f docker-compose.dev.yml exec web composer install
```

---
## 5. Cargar el schema en la base de datos

> El schema **debe** cargarse con `DB_USER` (usuario de aplicación, dueño de
> la base). Si lo cargas con `POSTGRES_USER`, las tablas quedan a nombre del
> superusuario y la app no puede operar sobre ellas (issue #94).

### En Windows (PowerShell)
```powershell
docker compose -f docker-compose.dev.yml cp config/schema.sql postgres:/tmp/schema.sql
```
y luego:

```powershell
docker compose -f docker-compose.dev.yml exec postgres sh -c 'psql -U "$DB_USER" -d "$POSTGRES_DB" -f /tmp/schema.sql'
```

### En Linux y macOS (Bash/Zsh)

```bash
export $(grep -v '^#' .env | xargs)
cat config/schema.sql | docker compose -f docker-compose.dev.yml exec -T postgres psql -U $DB_USER -d $POSTGRES_DB
```

Esto lee tu `.env` y usa esos valores, no importa qué usuario/base hayas
puesto.

Para verificar las tablas:

```bash
docker compose -f docker-compose.dev.yml exec postgres sh -c 'psql -U "$DB_USER" -d "$POSTGRES_DB" -c "\\dt"'
```

### 5.1 Si ya tenías Postgres levantado (volumen existente)

El init script `docker/postgres-init/01-app-user.sh` solo corre la **primera
vez** que se crea el volumen `postgres_dev_data`. Si ya tenías un volumen
anterior:

```bash
docker compose -f docker-compose.dev.yml down -v
docker compose -f docker-compose.dev.yml up --build -d
```

Luego vuelve a cargar el schema (paso 5).

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
