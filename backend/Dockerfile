FROM php:8.2-fpm-alpine

RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Copiar configuración (si existiera)
# COPY ./app /var/www/html
