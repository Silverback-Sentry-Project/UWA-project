# Render deployment image for the Laravel API. Mirrors the retired local Docker
# stack's Laravel image (php:8.4-fpm-bookworm base, composer install pattern),
# but swaps pdo_mysql for pdo_pgsql to match the Neon Postgres hosted target
# (see ../HOSTED-CUTOVER-PLAN.md) - the old image only had pdo_mysql, which was
# flagged there as a concrete, checkable gap for this exact deploy.
FROM php:8.4-fpm-bookworm

# sodium is needed by kreait/firebase-php's JWT dependency chain. docker-php-ext-install
# sodium needs libsodium's headers (libsodium-dev) to compile against - confirmed by an
# actual Render build failure without it ("Package 'libsodium', required by 'virtual:world',
# not found"), not just a dry-run assumption. iconv is usually already compiled into official
# php-docker images and docker-php-ext-install treats already-built-in extensions as a no-op.
RUN apt-get update && apt-get install -y \
    git curl unzip zip libpq-dev libzip-dev libsodium-dev \
    && docker-php-ext-install pdo pdo_pgsql bcmath sodium iconv \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

# --ignore-platform-req=ext-grpc matches the retired local stack's own Dockerfile:
# google/cloud-firestore's grpc transport is optional - it falls back to REST
# without the extension, and grpc has no standard docker-php-ext-install target
# (would need a PECL build step this image deliberately avoids).
RUN composer install --no-interaction --no-dev --optimize-autoloader --ignore-platform-req=ext-grpc

# Render sets $PORT at runtime; Laravel's built-in server binds to it directly
# rather than assuming a fixed port the way the old local-only image did.
CMD php artisan config:cache && php artisan route:cache && \
    php artisan migrate --force && \
    php -S 0.0.0.0:${PORT:-8000} -t public public/index.php
