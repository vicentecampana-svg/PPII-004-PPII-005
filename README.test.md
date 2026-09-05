# Guía de Pruebas y Calidad de Software (Testing)

Este documento describe la arquitectura, ejecución y mejores prácticas de la suite de pruebas automatizadas del proyecto **Software Factory Lab & Tech Hub ULS**.

---

## 🧪 Marco de Pruebas

El sistema utiliza **PHPUnit 10+** para garantizar la calidad del código, la integridad de los datos y el cumplimiento de requerimientos funcionales y no funcionales.

---

## 📁 Estructura de Pruebas (`tests/`)

```text
tests/
├── Services/               # Pruebas unitarias de la lógica de negocio y validaciones
│   ├── UserServiceTest.php
│   ├── NewsServiceTest.php
│   ├── QueryServiceTest.php
│   └── CreditsServiceTest.php
├── AdminNewsTest.php       # Pruebas del panel de gestión de noticias
├── AdminProjectsTest.php   # Pruebas del panel de gestión de proyectos
├── AdminStaffTest.php      # Pruebas del panel de gestión de staff
├── AdminAboutTest.php      # Pruebas de edición de contenido institucional
├── AdminFooterTest.php     # Pruebas de administración del pie de página
├── RolePermissionTest.php  # Pruebas de control de acceso RBAC y roles
├── PublicPagesTest.php     # Pruebas de renderizado y rutas públicas (Staff, Noticias, etc.)
└── AccessibilityTest.php   # Pruebas de conformidad con accesibilidad WCAG 2.1 Nivel AA
```

---

## 🚀 Cómo Ejecutar las Pruebas

### Opción 1: Con Docker (Recomendado)

Si estás ejecutando el entorno en contenedores:

```bash
# Ejecutar toda la suite de pruebas
docker compose -f docker-compose.dev.yml exec web ./vendor/bin/phpunit

# Ejecutar con salida detallada
docker compose -f docker-compose.dev.yml exec web ./vendor/bin/phpunit --testdox
```

---

### Opción 2: Localmente en la máquina del desarrollador

Si tienes PHP 8.2 y Composer instalados localmente:

```bash
# Ejecutar toda la suite
./vendor/bin/phpunit

# O mediante script de Composer (si está configurado)
composer test
```

---

## 🎯 Ejecución Selectiva de Pruebas

### Ejecutar un archivo de prueba específico:
```bash
docker compose -f docker-compose.dev.yml exec web ./vendor/bin/phpunit tests/AccessibilityTest.php
```

### Ejecutar un método de prueba específico (con `--filter`):
```bash
docker compose -f docker-compose.dev.yml exec web ./vendor/bin/phpunit --filter testStaffViewRendersMembers
```

### Ejecutar una suite específica:
```bash
docker compose -f docker-compose.dev.yml exec web ./vendor/bin/phpunit --testsuite Services
```

---

## 🛡️ Áreas Críticas Evaluadas

1. **Seguridad y Control de Acceso (RBAC)**:
   - Validación de permisos por rol (`SuperAdmin`, `Admin`, `Redactor`, `Editor`).
   - Verificación de protección contra ataques CSRF en peticiones POST.
   - Hashing seguro de contraseñas y validación de sesiones.

2. **Accesibilidad (WCAG 2.1 Nivel AA)**:
   - Presencia de enlace accesible para saltar al contenido principal (*Skip Link*).
   - Roles semánticos y landmarks (`role="banner"`, `role="contentinfo"`, `<main>`).
   - Manejadores de eventos de teclado (tecla `Escape`, navegación por `Tab`).
   - Atributos `aria-*` en formularios, alertas dinámicas y modales.

3. **Lógica de Negocio y Datos**:
   - CRUD de proyectos, integrantes del staff y noticias.
   - Paginación y búsqueda por palabras clave.
   - Almacenamiento seguro de consultas de contacto y solicitudes de servicio.
