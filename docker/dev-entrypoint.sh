#!/bin/sh
# Entrypoint del contenedor de desarrollo.
#
# public/uploads/ y storage/ vienen del bind-mount del host (docker-compose.dev.yml
# monta ".:/var/www/html"), así que conservan el dueño y permisos del filesystem
# del host. Apache dentro del contenedor corre como www-data, que normalmente no
# es dueño ni pertenece al grupo de esos directorios, y por eso no puede escribir
# en ellos (subida de imágenes, log de correo de recuperación de contraseña, etc.).
#
# Cualquier chmod/chown hecho en el Dockerfile en build-time es inútil aquí porque
# el volumen se monta encima en runtime y pisa esos permisos. Por eso se ajusta acá,
# en cada arranque del contenedor, antes de levantar Apache.
set -e

mkdir -p /var/www/html/storage/logs /var/www/html/public/uploads
chmod -R 0777 /var/www/html/storage /var/www/html/public/uploads

exec apache2-foreground
