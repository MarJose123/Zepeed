# =============================================================================
#  Stage 1 — CLI binaries (Ookla + LibreSpeed + Fast + Cloudflare)
# =============================================================================
FROM alpine:3.22 AS cli-builder

ARG OOKLA_VERSION=1.2.0
ARG LIBRESPEED_VERSION=1.0.12
ARG FAST_CLI_VERSION=0.3.5
ARG CLOUDFLARE_SPEED_CLI_VERSION=1.0.5

ENV OOKLA_VERSION=${OOKLA_VERSION} \
    LIBRESPEED_VERSION=${LIBRESPEED_VERSION} \
    FAST_CLI_VERSION=${FAST_CLI_VERSION} \
    CLOUDFLARE_SPEED_CLI_VERSION=${CLOUDFLARE_SPEED_CLI_VERSION} \
    INSTALL_DIR=/usr/local/bin

RUN apk add --no-cache curl tar bash xz

COPY .bin/install-cli-tools.sh /tmp/install-cli-tools.sh
COPY .bin/fast-cli-install.sh  /tmp/fast-cli-install.sh
RUN chmod +x /tmp/install-cli-tools.sh && /tmp/install-cli-tools.sh



# =============================================================================
#  Stage 2 — PHP/Node asset build
#  Compiles Composer deps + Bun frontend assets inside a disposable layer.
#  Nothing from this stage reaches the runtime image except /var/www/html.
# =============================================================================
FROM serversideup/php:8.4-fpm-nginx-alpine AS builder

USER root

# Install Bun + build tools
RUN apk add --no-cache curl bash unzip git nodejs npm \
    && install-php-extensions intl \
    && curl -fsSL https://bun.sh/install | bash \
    && ln -sf /root/.bun/bin/bun /usr/local/bin/bun

WORKDIR /var/www/html

# ── Composer ─────────────────────────────────────────────────────────────────
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --no-progress

# ── Frontend ─────────────────────────────────────────────────────────────────
COPY package.json bun.lock* ./
RUN bun install --frozen-lockfile --ignore-scripts

# ── Full source (after deps to maximise cache hits) ──────────────────────────
COPY . .

# Run composer post-scripts now that full source is present
RUN composer run-script post-autoload-dump

# Build frontend assets
RUN bun run build



# =============================================================================
#  Stage 3 — Production runtime image
# =============================================================================
FROM serversideup/php:8.4-fpm-nginx-alpine AS production

ARG TIMEZONE=UTC
ARG VERSION=dev
ARG BUILD_DATE

LABEL org.opencontainers.image.title="Zepeed" \
      org.opencontainers.image.description="Speed test aggregator and network monitor" \
      org.opencontainers.image.url="https://github.com/marjose123/zepeed" \
      org.opencontainers.image.source="https://github.com/marjose123/zepeed" \
      org.opencontainers.image.vendor="MarJose Darang" \
      org.opencontainers.image.licenses="MIT" \
      org.opencontainers.image.version="${VERSION}" \
      org.opencontainers.image.created="${BUILD_DATE}"

ENV TZ=${TIMEZONE} \
    PHP_DATE_TIMEZONE=${TIMEZONE}

ENV PHP_POST_MAX_SIZE="100M" \
    PHP_UPLOAD_MAX_FILE_SIZE="100M" \
    PHP_OPCACHE_ENABLE="1" \
    PHP_OPCACHE_MEMORY_CONSUMPTION="128" \
    PHP_OPCACHE_MAX_ACCELERATED_FILES="10000" \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS="0"

ENV NGINX_FASTCGI_BUFFERS="128 4k" \
    NGINX_FASTCGI_BUFFER_SIZE="32k"

ENV AUTORUN_ENABLED="true" \
    AUTORUN_LARAVEL_OPTIMIZE="true" \
    AUTORUN_LARAVEL_MIGRATION_SEED="true"

ENV HEALTHCHECK_PATH="/up"

ENV DEFAULT_ADMIN_NAME="Zepeed Admin" \
    DEFAULT_ADMIN_EMAIL="admin@zepeed.local"

USER root

RUN apk add --no-cache \
        curl \
        tzdata \
        bash \
        iputils \
    && install-php-extensions intl \
    && cp /usr/share/zoneinfo/${TIMEZONE} /etc/localtime \
    && echo "${TIMEZONE}" > /etc/timezone

COPY --from=cli-builder /usr/local/bin/speedtest            /usr/local/bin/speedtest
COPY --from=cli-builder /usr/local/bin/librespeed-cli       /usr/local/bin/librespeed-cli
COPY --from=cli-builder /usr/local/bin/fast-cli             /usr/local/bin/fast-cli
COPY --from=cli-builder /usr/local/bin/cloudflare-speed-cli /usr/local/bin/cloudflare-speed-cli

COPY docker/production/etc/s6-overlay /etc/s6-overlay
RUN find /etc/s6-overlay/s6-rc.d -name "run" -o -name "finish" | xargs chmod 755

COPY --from=builder --chown=www-data:www-data /var/www/html /var/www/html

RUN mkdir -p \
        /var/www/html/storage/app/public \
        /var/www/html/storage/framework/cache/data \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/testing \
        /var/www/html/storage/framework/views \
        /var/www/html/storage/logs \
        /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache

HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -sf http://127.0.0.1:8080/up || exit 1

USER www-data
