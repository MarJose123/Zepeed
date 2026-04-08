# =============================================================================
#  Stage 1 — CLI binaries (Ookla + LibreSpeed + Fast)
# =============================================================================
FROM alpine:3.22 AS cli-builder

ARG LIBRESPEED_VERSION=1.0.12
ARG SPEEDTEST_VERSION=1.2.0
ARG FAST_CLI_VERSION=0.3.5

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

# Netflix Fast CLI
RUN curl -sSL \
    "https://github.com/mikkelam/fast-cli/releases/download/v${FAST_CLI_VERSION}/fast-cli-x86_64-linux.tar.gz" \
    | tar -xz -C /usr/local/bin \
    && chmod 755 /usr/local/bin/fast-cli


# =============================================================================
#  Stage 2 — Final runtime image
# =============================================================================
FROM serversideup/php:8.4-fpm-nginx-alpine AS base

LABEL maintainer="MarJose Darang"

ARG TIMEZONE
ARG HEALTHCHECK_PATH

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
    HEALTHCHECK_PATH=$HEALTHCHECK_PATH

USER root

RUN apk add --no-cache \
        curl \
        tzdata \
        bash \
    && cp /usr/share/zoneinfo/${TIMEZONE} /etc/localtime \
    && echo "${TIMEZONE}" > /etc/timezone

# ── Binaries from builder stages ─────────────────────────────────────────────
COPY --from=cli-builder  /usr/local/bin/speedtest       /usr/local/bin/speedtest
COPY --from=cli-builder  /usr/local/bin/librespeed-cli  /usr/local/bin/librespeed-cli
COPY --from=cli-builder  /usr/local/bin/fast-cli  /usr/local/bin/fast-cli

# ── S6 Overlay service definitions ───────────────────────────────────────────
# context is project root (see compose.yml), so path is /etc/s6-overlay
COPY /etc/s6-overlay /etc/s6-overlay
RUN find /etc/s6-overlay/s6-rc.d -name "run" -o -name "finish" | xargs chmod 755

USER www-data
