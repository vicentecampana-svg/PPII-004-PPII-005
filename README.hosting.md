# Guía de Despliegue y Puesta en Producción (Hosting)

Esta guía detalla los pasos y consideraciones para desplegar la plataforma **Software Factory Lab & Tech Hub ULS** en un entorno de producción (servidor VPS, nube o servidor institucional).

---

## 1. Requisitos del Servidor

- **Sistema Operativo**: Linux (Ubuntu Server 22.04 LTS / Debian 12 recomendados).
- **PHP**: Versión 8.2 o superior con extensiones:
  - `php8.2-cli`, `php8.2-fpm` o `libapache2-mod-php8.2`
  - `php8.2-pgsql`, `php8.2-pdo-pgsql`
  - `php8.2-curl`, `php8.2-mbstring`, `php8.2-xml`, `php8.2-gd`
- **Base de Datos**: PostgreSQL 15+.
- **Servidor Web**: Apache 2.4 (con `mod_rewrite` habilitado) o Nginx.
- **Gestor de paquetes**: Composer 2+.
- **Certificado SSL**: Let's Encrypt (Certbot) u homólogo para HTTPS.

---

## 2. Preparación del Entorno en el Servidor

### 2.1 Clonar el repositorio
Ubica la aplicación en el directorio web estándar (por ejemplo, `/var/www/sfl-uls`):

```bash
cd /var/www
sudo git clone https://github.com/vicentecampana-svg/PPII-004-PPII-005.git sfl-uls
cd sfl-uls
```

### 2.2 Instalar dependencias de producción
Instala las dependencias sin paquetes de desarrollo y optimizando el autoloader:

```bash
composer install --no-dev --optimize-autoloader
```

---

## 3. Configuración de Variables de Entorno (`.env`)

Copia la plantilla y configura los valores específicos de producción:

```bash
cp .env.example .env
nano .env
```

Ejemplo de configuración de producción:

```env
APP_ENV=production
APP_PORT=80
APP_DEBUG=false
APP_URL=https://sfl.userena.cl

# Base de datos PostgreSQL
# Rol de bootstrap/administración (solo tareas de admin; la app NUNCA
# se conecta con el superusuario — principio de mínimos privilegios, issue #94)
POSTGRES_DB=sfl_production
POSTGRES_USER=postgres
POSTGRES_PASSWORD=ContraseñaAdminDB
POSTGRES_HOST=127.0.0.1
POSTGRES_PORT=5432

# Conexión de la aplicación — rol con LOGIN, dueño de la base de datos,
# sin SUPERUSER/CREATEDB/CREATEROLE (se crea en la sección 4)
PG_DATABASE=sfl_production
PG_USER=sfl_user
PG_PASSWORD=TuPasswordSuperSeguro123!
PG_HOST=127.0.0.1
PG_PORT=5432

# Correo (recuperación de contraseña — issue #15)
# Sin estas variables los correos se loguean en storage/logs/mail_dev.log.
# Confirmar credenciales con el equipo de sistemas de la ULS.
MAIL_DRIVER=smtp
SMTP_HOST=smtp.uls.cl
SMTP_PORT=587
SMTP_USER=no-reply@uls.cl
SMTP_PASS=
SMTP_FROM=no-reply@uls.cl
SMTP_FROM_NAME=TechHub ULS
```

> ⚠️ **Importante**: Asegúrate de que el archivo `.env` tenga permisos restrictivos para que solo el usuario del servidor web pueda leerlo (`chmod 600 .env`).

---

## 4. Base de Datos PostgreSQL

> ⚠️ **Principio de mínimos privilegios (issue #94)**: la aplicación se conecta
> siempre con `sfl_user` (un rol con LOGIN, dueño de la base de datos, sin
> `SUPERUSER`/`CREATEDB`/`CREATEROLE`). Nunca configures la app para
> conectarse con el superusuario `postgres`.

1. Crear el rol de aplicación (mínimos privilegios) y la base de datos:
```bash
sudo -u postgres psql -c "CREATE USER sfl_user WITH LOGIN PASSWORD 'TuPasswordSuperSeguro123!' NOSUPERUSER NOCREATEDB NOCREATEROLE;"
sudo -u postgres psql -c "CREATE DATABASE sfl_production OWNER sfl_user;"
```

2. Cargar el esquema inicial y datos **con el usuario de aplicación** (así las
   tablas quedan a nombre de `sfl_user`, no del superusuario):
```bash
psql -U sfl_user -d sfl_production -h 127.0.0.1 -f config/schema.sql
```

---

## 5. Permisos de Archivos y Directorios

El servidor web (`www-data` en Debian/Ubuntu) requiere permisos de escritura en los directorios de almacenamiento de sesiones, registros y subida de archivos:

```bash
sudo chown -R www-data:www-data /var/www/sfl-uls
sudo chmod -R 755 /var/www/sfl-uls
sudo chmod -R 775 /var/www/sfl-uls/storage
sudo chmod -R 775 /var/www/sfl-uls/public/uploads
```

---

## 6. Configuración del Servidor Web

### Opción A: Apache 2.4 (Recomendado)

1. Habilitar los módulos necesarios:
```bash
sudo a2enmod rewrite headers ssl
```

2. Crear el archivo de configuración del VirtualHost (`/etc/apache2/sites-available/sfl.conf`):
```apache
<VirtualHost *:80>
    ServerName sfl.userena.cl
    DocumentRoot /var/www/sfl-uls/public

    <Directory /var/www/sfl-uls/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # Reescritura Front Controller
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^ index.php [L,QSA]
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/sfl_error.log
    CustomLog ${APACHE_LOG_DIR}/sfl_access.log combined
</VirtualHost>
```

3. Habilitar el sitio y reiniciar Apache:
```bash
sudo a2ensite sfl.conf
sudo systemctl restart apache2
```

---

### Opción B: Nginx + PHP-FPM

Crear el bloque de servidor (`/etc/nginx/sites-available/sfl`):

```nginx
server {
    listen 80;
    server_name sfl.userena.cl;
    root /var/www/sfl-uls/public;
    index index.php;

    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Habilitar y recargar Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/sfl /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## 7. Configuración de HTTPS / SSL

Instalar y ejecutar Certbot para configurar SSL automáticamente con Let's Encrypt:

```bash
sudo apt install certbot python3-certbot-apache # (o python3-certbot-nginx)
sudo certbot --apache -d sfl.userena.cl
```

---

## 8. Mantenimiento y Actualizaciones

Para desplegar una nueva versión del código en producción:

```bash
cd /var/www/sfl-uls
sudo git pull origin main
sudo composer install --no-dev --optimize-autoloader
sudo systemctl reload apache2 # o systemctl reload php8.2-fpm
```
