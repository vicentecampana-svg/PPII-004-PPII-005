# Software Factory Lab & Tech Hub — ULS

Portal web institucional y sistema de gestión de contenidos para el **Software Factory Lab (SFL)** y el **Tech Hub** de la **Universidad de La Serena**.

---

## 📋 Descripción del Proyecto

El sistema proporciona una plataforma integral para la divulgación y administración de las actividades del laboratorio de desarrollo de software universitario, incluyendo:

- **Portal Público**: Presentación institucional (*Sobre nosotros*, *Misión, visión y objetivos*), catálogo de proyectos estudiantiles, directorio de miembros del staff, servicios tecnológicos, portal de noticias con buscador por palabras clave y formulario de contacto.
- **Panel de Administración**: Panel de control protegido por roles (*SuperAdmin*, *Admin*, *Redactor/Editor*) para la gestión dinámica de proyectos, integrantes del equipo, noticias con etiquetas, contenido institucional, enlaces del pie de página y administración de usuarios.
- **Accesibilidad y Rendimiento**: Cumplimiento de estándares de accesibilidad **WCAG 2.1 Nivel AA** (*skip-links*, navegación íntegra por teclado, contraste verificado y soporte para lectores de pantalla).

> 📌 **Nota sobre maquetas y prototipos**:
> Las maquetas iniciales y prototipos de diseño (como proyectos en Lovable o capturas de referencia) son únicamente material de consulta y especificación previa para el equipo de desarrollo. El código contenido en este repositorio constituye el único entregable funcional y mantenible del sistema.

---

## 🏗️ Arquitectura y Tecnologías

El proyecto está diseñado bajo una arquitectura limpia **MVC** con separación de responsabilidades mediante el patrón **Service-Repository**, sin frameworks pesados, maximizando el rendimiento y la mantenibilidad.

- **Lenguaje**: PHP 8.3 (tipado estricto `declare(strict_types=1)`).
- **Base de Datos**: PostgreSQL 15+ (acceso vía PDO y consultas preparadas).
- **Servidor Web / Contenedores**: Apache 2.4 con `mod_rewrite` sobre Docker.
- **Frontend**: HTML5 semántico, CSS3 corporativo (diseño responsivo propio) y JavaScript vainilla.
- **Seguridad**: Protección contra ataques CSRF mediante tokens por sesión, sanitización estricta XSS, autenticación segura con `password_hash()` (Argon2id/Bcrypt), control de acceso basado en roles (RBAC) y código de seguridad CAPTCHA.
- **Pruebas**: PHPUnit 10+.

---

## 📁 Estructura del Repositorio

```text
├── app/
│   ├── Controllers/     # Controladores HTTP web y API REST
│   ├── Repositories/    # Capa de acceso y consultas a la base de datos PostgreSQL
│   ├── Services/        # Lógica de negocio, validaciones y subida de archivos
│   ├── Views/           # Vistas y componentes en PHP/HTML
│   │   ├── admin/       # Vistas del panel de administración
│   │   └── layout/      # Encabezado, navegación y pie de página común
│   └── helpers.php      # Funciones auxiliares globales y conexión DB
├── config/
│   └── schema.sql       # Esquema DDL, tablas, índices y datos iniciales
├── public/              # Raíz pública del servidor web (DocumentRoot)
│   ├── assets/          # Hojas de estilo CSS, imágenes e íconos
│   ├── uploads/         # Archivos multimedia subidos por usuarios
│   └── index.php        # Front Controller y enrutador HTTP
├── storage/             # Almacenamiento local persistente
├── tests/               # Suite completa de pruebas unitarias y de integración
├── Dockerfile.dev       # Definición del contenedor Apache + PHP 8.3
├── docker-compose.dev.yml # Orquestación del entorno de desarrollo (Web + Postgres)
├── README.dev.md        # Guía detallada del entorno de desarrollo local
├── README.hosting.md    # Guía de despliegue y puesta en producción
└── README.test.md       # Guía de ejecución de pruebas y aseguramiento de calidad
```

---

## 🚀 Inicio Rápido (Desarrollo con Docker)

### Requisitos Previos
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (o Docker Engine + Docker Compose).
- [Git](https://git-scm.com/).

### 1. Clonar el repositorio y configurar variables
```bash
git clone https://github.com/vicentecampana-svg/PPII-004-PPII-005.git
cd PPII-004-PPII-005
cp .env.example .env
```

### 2. Iniciar los contenedores
```bash
docker compose -f docker-compose.dev.yml up --build -d
```

### 3. Instalar dependencias de Composer
```bash
docker compose -f docker-compose.dev.yml exec web composer install
```

### 4. Cargar el esquema y datos iniciales en PostgreSQL
**En Linux / macOS:**
```bash
export $(grep -v '^#' .env | xargs)
cat config/schema.sql | docker compose -f docker-compose.dev.yml exec -T postgres psql -U $POSTGRES_USER -d $POSTGRES_DB
```

**En Windows (PowerShell):**
```powershell
Get-Content config/schema.sql | docker compose -f docker-compose.dev.yml exec -T postgres psql -U postgres -d techhub
```

### 5. Acceder a la aplicación
- **Sitio público**: [http://localhost:8080](http://localhost:8080)
- **Panel de administración**: [http://localhost:8080/login](http://localhost:8080/login)

---

## 👥 Usuarios de Prueba

El archivo [`config/schema.sql`](config/schema.sql) incluye usuarios precargados para pruebas:

| Rol | Correo Electrónico | Contraseña | Permisos |
| :--- | :--- | :--- | :--- |
| **SuperAdmin** | `admin@userena.cl` | `admin123` | Control total del sistema, usuarios, roles, footer y contenidos. |
| **Admin** | `coordinador@userena.cl` | `admin123` | Gestión de proyectos, staff, noticias y contenido institucional. |
| **Redactor** | `periodista@userena.cl` | `admin123` | Creación y publicación de noticias. |

---

## 📚 Documentación Adicional

- 🛠️ [Guía de Desarrollo Local (`README.dev.md`)](README.dev.md)
- 🌐 [Guía de Despliegue en Servidor / Hosting (`README.hosting.md`)](README.hosting.md)
- 🧪 [Guía de Pruebas Automatizadas (`README.test.md`)](README.test.md)

---

## 📄 Licencia

Desarrollado para la **Universidad de La Serena** por el equipo de desarrollo de Software Factory Lab. Todos los derechos reservados.