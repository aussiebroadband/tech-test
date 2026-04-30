# Dev image for ad-hoc PHP/Composer/Artisan commands.
# This is the minimal image used during modernisation and day-to-day dev
# until the full docker-compose environment is built. Other compose
# services (web, queue, scheduler) will likely extend or replace this.
FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libsqlite3-dev \
        libzip-dev \
        libonig-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_sqlite \
        bcmath \
        zip \
        mbstring \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
