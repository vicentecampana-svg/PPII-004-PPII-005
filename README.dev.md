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
`docker-compose.dev.yml` se encarga de pasarle esos mismos valores a PHP, así
que no hay que tocar nada más.

Ejemplo:

```env
APP_ENV=development
APP_PORT=8080

POSTGRES_DB=techhub
POSTGRES_USER=postgres
POSTGRES_PASSWORD=postgres
POSTGRES_PORT=5433
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

### En Windows (PowerShell)
```powershell
docker compose -f docker-compose.dev.yml cp config/schema.sql postgres:/tmp/schema.sql
```
y luego:

```powershell
docker compose -f docker-compose.dev.yml exec postgres sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -f /tmp/schema.sql'
```

### En Linux y macOS (Bash/Zsh)

```bash
export $(grep -v '^#' .env | xargs)
cat config/schema.sql | docker compose -f docker-compose.dev.yml exec -T postgres psql -U $POSTGRES_USER -d $POSTGRES_DB
```

Esto lee tu `.env` y usa esos valores, no importa qué usuario/base hayas
puesto.

Para verificar las tablas:

```bash
docker compose -f docker-compose.dev.yml exec postgres psql -U $env:POSTGRES_USER -d $env:POSTGRES_DB -c "\dt"
```
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
