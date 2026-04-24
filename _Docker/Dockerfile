# syntax=docker/dockerfile:1

############################
# Base PHP
############################
FROM php:8.4-fpm-bookworm AS base

# PostgreSQL 16 client (pg_dump) — мажорная версия должна совпадать с сервером
RUN apt-get update && apt-get install -y --no-install-recommends gnupg curl \
    && curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor -o /usr/share/keyrings/postgresql-keyring.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/postgresql-keyring.gpg] http://apt.postgresql.org/pub/repos/apt bookworm-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
    libpq-dev \
    postgresql-client-16 \
    libzip-dev \
    libonig-dev \
    unzip \
    git \
    ffmpeg \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    mbstring \
    zip \
    opcache \
    pcntl

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/wix/todo

# Кастомный php.ini (лимиты загрузки, память, время выполнения)
COPY _Docker/php.ini /usr/local/etc/php/conf.d/99-upload.ini

RUN echo "listen = 0.0.0.0:9000" > /usr/local/etc/php-fpm.d/zz-docker.conf

############################
# Composer
############################
FROM base AS composer

COPY . .

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --prefer-dist \
    --no-scripts

############################
# Frontend (Node + Vite build)
############################
FROM node:20-bookworm-slim AS frontend

WORKDIR /app

# Build-time env для Vite (подставляются из .env при docker compose build)
ARG VITE_REVERB_APP_KEY=
ARG VITE_REVERB_HOST=localhost
ARG VITE_REVERB_PORT=8081
ARG VITE_REVERB_SCHEME=http
ARG VITE_APP_NAME=Laravel

ENV VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY \
    VITE_REVERB_HOST=$VITE_REVERB_HOST \
    VITE_REVERB_PORT=$VITE_REVERB_PORT \
    VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME \
    VITE_APP_NAME=$VITE_APP_NAME

COPY package.json package-lock.json* ./
RUN npm ci

COPY . .
# Ziggy и др. тянут пути из vendor — копируем из стадии composer
COPY --from=composer /var/www/wix/todo/vendor /app/vendor
RUN npm run build

############################
# Final App
############################
FROM base AS app

COPY --from=composer /var/www/wix/todo /var/www/wix/todo
COPY --from=frontend /app/public/build /var/www/wix/todo/public/build

# Node/npm в runtime: dev-compose — composer/npm в entrypoint без Node на хосте
COPY --from=node:20-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:20-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -sf /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

RUN chown -R www-data:www-data /var/www/wix/todo/storage /var/www/wix/todo/bootstrap/cache /var/www/wix/todo/public/build \
    && chmod -R 775 /var/www/wix/todo/storage /var/www/wix/todo/bootstrap/cache \
    && rm -f /var/www/wix/todo/bootstrap/cache/packages.php /var/www/wix/todo/bootstrap/cache/services.php

COPY _Docker/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm", "-F"]
