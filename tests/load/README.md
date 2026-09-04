# Escenarios de Pruebas de Carga (Load Testing) — SFL ULS Lab

Este directorio contiene los escenarios de prueba de carga reproducibles requeridos por el **SRS**, diseñados para evaluar el rendimiento del sistema bajo una concurrencia de **110 usuarios simultáneos**.

## Requisito del SRS

- **Concurrencia:** 110 usuarios simultáneos.
- **Flujos evaluados:**
  1. **Home:** `GET /`
  2. **Listado de noticias:** `GET /noticias`
  3. **Búsqueda de noticias:** `GET /noticias?q=investigacion`
  4. **Login:** `POST /api/auth/login`
- **Criterio de Aceptación:** El **95 % de las peticiones debe responder en menos de 2 segundos** ($P_{95} < 2000 \text{ ms}$).

---

## 1. Ejecución con el Runner Nativo en PHP

El runner nativo ejecuta peticiones concurrentes mediante `curl_multi` simulando 110 conexiones en paralelo y calculando percentiles ($P_{50}, P_{90}, P_{95}, P_{99}$):

```bash
# Iniciar el servidor si no está corriendo:
php -S 127.0.0.1:8080 -t public public/index.php &

# Ejecutar la prueba de carga (110 usuarios concurrentes):
php tests/load/load_test_runner.php http://127.0.0.1:8080 110 2
```

### Argumentos:
- `argv[1]`: URL base del servidor (por defecto: `http://127.0.0.1:8080`).
- `argv[2]`: Número de usuarios concurrentes (por defecto: `110`).
- `argv[3]`: Número de iteraciones por usuario (por defecto: `2`).

---

## 2. Ejecución con Apache JMeter

Se incluye el plan de pruebas `jmeter_load_test.jmx` compatible con Apache JMeter 5.x.

### Modo CLI (Recomendado para benchmarks headless):
```bash
jmeter -n -t tests/load/jmeter_load_test.jmx -Jhost=127.0.0.1 -Jport=8080 -Jloops=5 -l tests/load/results.jtl -e -o tests/load/dashboard/
```

### Modo Gráfico:
```bash
jmeter -t tests/load/jmeter_load_test.jmx
```

---

## Índices en Base de Datos

Para garantizar el cumplimiento del tiempo de respuesta bajo carga, se incorporaron en `config/schema.sql` los siguientes índices:

- **Noticias (`news`):** `idx_news_status_published`, `idx_news_published_at`, `idx_news_author_created`, `idx_news_editor`, `idx_news_title`.
- **Auditoría (`audit_log`):** `idx_audit_log_user_created`, `idx_audit_log_entity`, `idx_audit_log_created_at`.
- **Enlaces Footer (`enlaces_footer`):** `idx_enlaces_footer_grupo_orden`.
- **Relación Noticias-Tags (`news_tag`):** `idx_news_tag_tag_id`.
- **Usuarios (`app_user`):** `idx_app_user_role`, `idx_app_user_active`.
- **Proyectos y Servicios (`project`, `service`):** `idx_project_active`, `idx_service_active`.
- **Staff (`staff_member`):** `idx_staff_member_orden`.
