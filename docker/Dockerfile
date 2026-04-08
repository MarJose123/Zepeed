# =============================================================================
#  Stage 1 — CLI binaries (Ookla + LibreSpeed)
#  Throwaway Alpine stage: curl/tar never land in the final image.
# =============================================================================
FROM alpine:3.21 AS cli-builder

ARG LIBRESPEED_VERSION=1.0.12
ARG SPEEDTEST_VERSION=1.2.0

RUN apk add --no-cache curl tar

# Ookla Speedtest CLI
RUN curl -sSL "https://install.speedtest.net/app/cli/ookla-speedtest-${SPEEDTEST_VERSION}-linux-x86_64.tgz" \
    | tar -xz -C /tmp \
    && mv /tmp/speedtest /usr/local/bin/speedtest \
    && chmod 755 /usr/local/bin/speedtest

# LibreSpeed CLI
RUN curl -sSL \
        "https://github.com/librespeed/speedtest-cli/releases/download/v${LIBRESPEED_VERSION}/librespeed-cli_${LIBRESPEED_VERSION}_linux_amd64.tar.gz" \
    | tar -xz -C /usr/local/bin \
    && chmod 755 /usr/local/bin/librespeed-cli


# =============================================================================
#  Stage 2 — fast-cli node_modules (runtime-only, stripped)
#  - --ignore-scripts   prevents Puppeteer from attempting a Chromium download
#  - --omit=dev         skips devDependencies
#  - find …-delete      removes ~24 MB of maps, typedefs, and docs
# =============================================================================
FROM node:22-alpine AS node-builder

WORKDIR /opt/fast-cli

ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true \
    PUPPETEER_SKIP_DOWNLOAD=true

RUN npm install fast-cli --ignore-scripts --omit=dev \
    && npm cache clean --force \
    && find node_modules -name "*.map"      -delete \
    && find node_modules -name "*.d.ts"     -delete \
    && find node_modules -name "*.md"       -delete \
    && find node_modules -name "CHANGELOG*" -delete \
    && find node_modules -name "LICENSE*"   -delete \
    && find node_modules -name "README*"    -delete \
    && find node_modules -name "test"       -type d -exec rm -rf {} + 2>/dev/null || true \
    && find node_modules -name "__tests__"  -type d -exec rm -rf {} + 2>/dev/null || true \
    && find node_modules -name "*.test.js"  -delete \
    && find node_modules -name "*.spec.js"  -delete


# =============================================================================
#  Stage 3 — Final runtime image
# =============================================================================
FROM serversideup/php:8.4-fpm-nginx-alpine AS base

LABEL maintainer="MarJose Darang"

ARG TIMEZONE
ARG HEALTHCHECK_PATH
ARG LIBRESPEED_VERSION=1.0.12
ARG SPEEDTEST_VERSION=1.2.0

ENV TZ=$TIMEZONE \
    PHP_DATE_TIMEZONE=$TIMEZONE \
    PHP_POST_MAX_SIZE="100M" \
    PHP_UPLOAD_MAX_FILE_SIZE="100M" \
    PHP_OPCACHE_ENABLE="1" \
    PHP_OPCACHE_MEMORY_CONSUMPTION="128" \
    PHP_OPCACHE_MAX_ACCELERATED_FILES="10000" \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS="1" \
    NGINX_FASTCGI_BUFFERS="128 4k" \
    NGINX_FASTCGI_BUFFER_SIZE="32k" \
    HEALTHCHECK_PATH=$HEALTHCHECK_PATH \
    PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true \
    PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser \
    PATH="/opt/fast-cli/node_modules/.bin:$PATH"

USER root

# ── Single RUN: apk install → timezone copy → tzdata purge → cleanup ─────────
# Installing tzdata only to copy the zone file, then removing it saves ~8 MB.
# nodejs runtime only (no npm) — package manager is not needed at runtime.
RUN apk add --no-cache \
        tzdata \
        bash \
        chromium \
        nodejs \
    && cp /usr/share/zoneinfo/${TIMEZONE} /etc/localtime \
    && echo "${TIMEZONE}" > /etc/timezone \
    && apk del tzdata \
    && find /usr/lib/chromium/locales -type f ! -name "en-US.pak" -delete 2>/dev/null || true \
    && rm -rf \
        /var/cache/apk/* \
        /tmp/* \
        /var/tmp/* \
        /usr/share/doc \
        /usr/share/man

# ── Binaries from builder stages ─────────────────────────────────────────────
COPY --from=cli-builder  /usr/local/bin/speedtest       /usr/local/bin/speedtest
COPY --from=cli-builder  /usr/local/bin/librespeed-cli  /usr/local/bin/librespeed-cli
COPY --from=node-builder /opt/fast-cli/node_modules     /opt/fast-cli/node_modules

# ── S6 Overlay service definitions ───────────────────────────────────────────
# context is project root (see compose.yml), so path is /etc/s6-overlay
COPY /etc/s6-overlay /etc/s6-overlay
RUN find /etc/s6-overlay/s6-rc.d -name "run" -o -name "finish" | xargs chmod 755

USER www-data
