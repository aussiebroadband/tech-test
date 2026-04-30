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

COPY .docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
