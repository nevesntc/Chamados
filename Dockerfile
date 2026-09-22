FROM node:22-bookworm-slim AS frontend
WORKDIR /app
COPY package*.json .npmrc ./
RUN npm ci
COPY resources resources
COPY vite.config.js tsconfig.json ./
RUN npm run build

FROM php:8.3-apache-bookworm AS runtime
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libonig-dev libzip-dev unzip \
    && docker-php-ext-install pdo_pgsql mbstring zip opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
COPY --from=frontend /app/public/build public/build
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache
COPY deploy/apache.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/entrypoint.sh /usr/local/bin/chamados-entrypoint
RUN chmod +x /usr/local/bin/chamados-entrypoint
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr LOG_LEVEL=warning
EXPOSE 80
ENTRYPOINT ["chamados-entrypoint"]
CMD ["apache2-foreground"]
