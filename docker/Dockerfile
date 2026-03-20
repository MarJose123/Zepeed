FROM serversideup/php:8.4-fpm-nginx AS base

LABEL maintainer="MarJose Darang"

ARG TIMEZONE
ARG LIBRESPEED_VERSION=1.0.12

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=$TIMEZONE

USER root

RUN apt update -y && apt install -y curl nodejs supervisor unzip tar
RUN curl -fsSL https://bun.com/install | bash

ENV PATH="/root/.bun/bin:${PATH}"

RUN exec /bin/sh

# Installation of the Speedtest cli
RUN curl -s https://packagecloud.io/install/repositories/ookla/speedtest-cli/script.deb.sh | bash \
    && apt install speedtest \
    && curl -o /tmp/librespeed-cli.tar.gz -L \
                "https://github.com/librespeed/speedtest-cli/releases/download/v$LIBRESPEED_VERSION/librespeed-cli_${LIBRESPEED_VERSION}_linux_amd64.tar.gz" \
           && tar -xzf /tmp/librespeed-cli.tar.gz -C /usr/bin

RUN bun add --global fast-cli

RUN apt autoremove -y
RUN rm -rf /tmp/* /var/tmp/*


USER www-data
