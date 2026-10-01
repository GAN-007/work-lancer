FROM php:8.2-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libpq-dev poppler-utils supervisor \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction
COPY docker/supervisord.conf /etc/supervisor/conf.d/galika.conf
CMD ["supervisord","-c","/etc/supervisor/supervisord.conf"]
