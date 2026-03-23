FROM serversideup/php:8.4-fpm-nginx-alpine AS base

LABEL maintainer="MarJose Darang"

ARG TIMEZONE
ARG HEALTHCHECK_PATH
ARG LIBRESPEED_VERSION=1.0.12
ARG SPEEDTEST_VERSION=1.2.0

ENV TZ=$TIMEZONE

# Override serversideup/php base image ENV defaults
ENV PHP_DATE_TIMEZONE=$TIMEZONE \
    PHP_POST_MAX_SIZE="100M" \
    PHP_UPLOAD_MAX_FILE_SIZE="100M" \
    PHP_OPCACHE_ENABLE="1" \
    PHP_OPCACHE_MEMORY_CONSUMPTION="128" \
    PHP_OPCACHE_MAX_ACCELERATED_FILES="10000" \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS="1" \
    NGINX_FASTCGI_BUFFERS='128 4k' \
    NGINX_FASTCGI_BUFFER_SIZE="32k" \
    HEALTHCHECK_PATH=$HEALTHCHECK_PATH

USER root

#Timezone
RUN apk add --no-cache tzdata \
    && cp /usr/share/zoneinfo/${TIMEZONE} /etc/localtime \
    && echo "${TIMEZONE}" > /etc/timezone

# Alpine packages
RUN apk add --no-cache curl nodejs npm unzip tar bash

# Ookla Speedtest CLI — download binary directly, no packagecloud script needed
RUN curl -sSL "https://install.speedtest.net/app/cli/ookla-speedtest-${SPEEDTEST_VERSION}-linux-x86_64.tgz" \
    | tar -xz -C /tmp \
    && mv /tmp/speedtest /usr/local/bin/speedtest \
    && chmod 755 /usr/local/bin/speedtest \
    && rm -rf /tmp/speedtest*

# LibreSpeed CLI
RUN curl -sSL -o /tmp/librespeed-cli.tar.gz \
        "https://github.com/librespeed/speedtest-cli/releases/download/v${LIBRESPEED_VERSION}/librespeed-cli_${LIBRESPEED_VERSION}_linux_amd64.tar.gz" \
    && tar -xzf /tmp/librespeed-cli.tar.gz -C /usr/local/bin \
    && chmod 755 /usr/local/bin/librespeed-cli \
    && rm /tmp/librespeed-cli.tar.gz

# Fast.com CLI
RUN npm install --global fast-cli

# Chromium for Puppeteer (fast-cli)
RUN apk add --no-cache chromium

# Tell Puppeteer to use the system Chromium instead of downloading its own
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser

RUN rm -rf /tmp/* /var/tmp/*

# ─── S6 Overlay service definitions ──────────────────────────────────────────
COPY etc/s6-overlay /etc/s6-overlay
RUN find /etc/s6-overlay/s6-rc.d -name 'run' -o -name 'finish' | xargs chmod 755

USER www-data
