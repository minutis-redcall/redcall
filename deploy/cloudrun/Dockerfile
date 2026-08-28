# ─── Frontend assets (Webpack Encore) ────────────────────────────────────────
FROM node:22-alpine AS assets

WORKDIR /app
COPY symfony/package.json symfony/yarn.lock ./
RUN yarn install --frozen-lockfile

COPY symfony/webpack.config.js ./
COPY symfony/assets ./assets
RUN yarn encore production

# ─── PHP base: same extension set as docker/php/Dockerfile ───────────────────
FROM php:8.4-fpm-alpine AS base

RUN apk --update --no-cache add \
      bash make g++ gcc \
      icu-dev icu-data-full \
      oniguruma-dev libzip-dev \
      freetype freetype-dev \
      libpng libpng-dev \
      libjpeg-turbo libjpeg-turbo-dev \
      libwebp libwebp-dev \
      libsodium-dev libxml2-dev \
      gmp-dev

RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install opcache pdo pdo_mysql intl mbstring bcmath zip sodium dom gd gmp

# ─── PHP dependencies ─────────────────────────────────────────────────────────
FROM base AS deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app/symfony
COPY symfony/composer.json symfony/composer.lock ./
COPY symfony/bundles ./bundles
COPY symfony/src ./src
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction

# ─── Runtime: Caddy + PHP-FPM ─────────────────────────────────────────────────
FROM base AS runtime

ARG ENV=preprod

RUN apk --no-cache add caddy

# Production PHP settings (mirrors symfony/php.ini used on GAE)
RUN { \
      echo 'memory_limit = 512M'; \
      echo 'post_max_size = 20M'; \
      echo 'upload_max_filesize = 20M'; \
      echo 'max_execution_time = 600'; \
      echo 'output_buffering = 4096'; \
      echo 'date.timezone = Europe/Paris'; \
      echo 'display_errors = Off'; \
      echo 'log_errors = On'; \
      echo 'opcache.memory_consumption = 256'; \
      echo 'opcache.max_accelerated_files = 20000'; \
      echo 'opcache.validate_timestamps = 0'; \
      echo 'realpath_cache_size = 4096K'; \
      echo 'realpath_cache_ttl = 600'; \
      echo 'session.cookie_lifetime = 43200'; \
    } > /usr/local/etc/php/conf.d/app.ini

# clear_env=no: Cloud Run env vars (PORT, overrides) must reach PHP workers.
# catch_workers_output: PHP errors go to the container's stderr → Cloud Logging.
RUN { \
      echo 'pm = dynamic'; \
      echo 'pm.max_children = 12'; \
      echo 'pm.start_servers = 3'; \
      echo 'pm.min_spare_servers = 2'; \
      echo 'pm.max_spare_servers = 6'; \
      echo 'clear_env = no'; \
      echo 'catch_workers_output = yes'; \
    } >> /usr/local/etc/php-fpm.d/www.conf

WORKDIR /app/symfony

COPY symfony/ ./
COPY --from=deps /app/symfony/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# No service-account key is baked in: without config/keys/google-service-account.json,
# index.php leaves GOOGLE_APPLICATION_CREDENTIALS unset and the Google clients fall
# back to ADC, i.e. the Cloud Run runtime service account (same identity, no key to
# rotate). Signed URLs go through the IAM Credentials API (the SA holds
# roles/iam.serviceAccountTokenCreator on itself).
COPY deploy/${ENV}/dotenv ./.env
COPY deploy/cloudrun/Caddyfile /etc/caddy/Caddyfile
COPY deploy/cloudrun/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Kernel::getCacheDir() uses sys_get_temp_dir() in prod; pin it to a path
# that persists from build to runtime (do NOT set APP_ENV here: index.php
# only loads the baked .env when APP_ENV is absent from the environment).
ENV TMPDIR=/app/tmp
RUN mkdir -p /app/tmp

# Build-time template generation + container/cache warmup (uses the baked .env).
# php-fpm workers run as www-data and must be able to write under TMPDIR
# (logs, sessions dir, google-cloud batch runtime) — hand the warmed tree over.
RUN php bin/console generate:mjml templates/message/email.html.twig.mjml \
 && php bin/console generate:mjml templates/message/image.html.twig.mjml \
 && php bin/console cache:warmup --env=prod \
 && chown -R www-data:www-data /app/tmp

EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]
