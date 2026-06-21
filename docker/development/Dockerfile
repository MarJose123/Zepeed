# =============================================================================
#  Stage 1 — CLI binaries (Ookla + LibreSpeed + Fast + Cloudflare)
# =============================================================================
FROM alpine:3.22 AS cli-builder

# -----------------------------------------------------------------------------
#  Pinned versions — passed through to install-cli-tools.sh as env vars.
#  Update versions here; the script reads these as overrides.
# -----------------------------------------------------------------------------
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
        iputils \
    && cp /usr/share/zoneinfo/${TIMEZONE} /etc/localtime \
    && echo "${TIMEZONE}" > /etc/timezone

# ── Binaries from builder stage ───────────────────────────────────────────────
COPY --from=cli-builder /usr/local/bin/speedtest            /usr/local/bin/speedtest
COPY --from=cli-builder /usr/local/bin/librespeed-cli       /usr/local/bin/librespeed-cli
COPY --from=cli-builder /usr/local/bin/fast-cli             /usr/local/bin/fast-cli
COPY --from=cli-builder /usr/local/bin/cloudflare-speed-cli /usr/local/bin/cloudflare-speed-cli

# ── S6 Overlay service definitions ───────────────────────────────────────────
COPY docker/etc/s6-overlay /etc/s6-overlay
RUN find /etc/s6-overlay/s6-rc.d -name "run" -o -name "finish" | xargs chmod 755

USER www-data
