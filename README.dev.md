# Entorno de Desarrollo

## Requisitos

Antes de comenzar, asegúrate de tener instalado:

- Git
- Docker Desktop

No es necesario instalar PHP, Apache, PostgreSQL ni Composer en el equipo local, ya que todo se ejecuta dentro de Docker.

---

## 1. Clonar el repositorio

```bash
git clone https://github.com/USUARIO/Proyecto_Tech_Hub_ULS.git
cd Proyecto_Tech_Hub_ULS
```

---

## 2. Crear el archivo `.env`

Copiar el archivo `.env.example` y renombrarlo a `.env`.

En PowerShell:

```powershell
Copy-Item .env.example .env
```

---

## 3. Configurar las variables de entorno

Editar el archivo `.env` con los valores necesarios.

Ejemplo:

```env
APP_ENV=development
APP_PORT=8080

POSTGRES_DB=techhub
POSTGRES_USER=postgres
POSTGRES_PASSWORD=postgres
POSTGRES_PORT=5433

PG_HOST=postgres
PG_PORT=5432
PG_DATABASE=techhub
PG_USER=postgres
PG_PASSWORD=postgres
```

---

## 4. Levantar el entorno

Ejecutar:

```bash
docker compose -f docker-compose.dev.yml up --build -d
```

Este comando:

- Construye la imagen de PHP.
- Levanta Apache.
- Levanta PostgreSQL.
- Conecta ambos contenedores.

---

## 5. Instalar las dependencias

Ejecutar:

```bash
docker compose -f docker-compose.dev.yml exec web composer install
```

---

## 6. Abrir el proyecto

Abrir el navegador en:

```
http://localhost:8080
```

Si todo está correcto, el proyecto debería cargar sin errores.

---

## 7. Verificar los contenedores

```bash
docker compose -f docker-compose.dev.yml ps
```

Debe aparecer:

- web → Up
- postgres → Up (healthy)

---

## 8. Detener el entorno

```bash
docker compose -f docker-compose.dev.yml down
```

---

## 9. Reconstruir el entorno

Si se modifica el `Dockerfile.dev`:

```bash
docker compose -f docker-compose.dev.yml up --build
```