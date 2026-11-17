FROM dunglas/frankenphp:1-php8.3-trixie AS dependencies

RUN install-php-extensions \
    bcmath \
    intl \
    opcache \
    pcntl \
    pdo_mysql \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

COPY . .

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

FROM node:22-bookworm-slim AS assets

WORKDIR /app

COPY . .
COPY --from=dependencies /app/vendor ./vendor

RUN npm ci --no-audit --no-fund \
    && npm run build

FROM dunglas/frankenphp:1-php8.3-trixie AS runtime

RUN install-php-extensions \
    bcmath \
    intl \
    opcache \
    pcntl \
    pdo_mysql \
    zip

WORKDIR /app

COPY . .
COPY --from=dependencies /app/vendor ./vendor
COPY --from=dependencies /app/bootstrap/cache/ ./bootstrap/cache/
COPY --from=assets /app/public/build ./public/build
COPY Caddyfile /etc/caddy/Caddyfile

RUN mkdir -p \
        bootstrap/cache \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && chown -R www-data:www-data bootstrap/cache storage \
    && chmod -R ug+rwX bootstrap/cache storage

ENV APP_ENV=production \
    APP_DEBUG=false

EXPOSE 8080

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
