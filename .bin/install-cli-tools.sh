#!/usr/bin/env bash
# =============================================================================
#  Zepeed — CLI Tools Installer
#  Centralizes all speedtest CLI binary downloads with pinned versions.
#  Called exclusively from docker/Dockerfile Stage 1 (cli-builder).
#
#  To upgrade a tool, update the version variable below and rebuild the image.
# =============================================================================

set -euo pipefail

# -----------------------------------------------------------------------------
#  Pinned versions — update here to upgrade, nowhere else.
# -----------------------------------------------------------------------------
OOKLA_VERSION="${OOKLA_VERSION:-1.2.0}"
LIBRESPEED_VERSION="${LIBRESPEED_VERSION:-1.0.12}"
FAST_CLI_VERSION="${FAST_CLI_VERSION:-0.3.5}"
CLOUDFLARE_SPEED_CLI_VERSION="${CLOUDFLARE_SPEED_CLI_VERSION:-1.0.5}"

INSTALL_DIR="${INSTALL_DIR:-/usr/local/bin}"

# -----------------------------------------------------------------------------
#  Helpers
# -----------------------------------------------------------------------------
log()  { echo "[install-cli-tools] $*"; }
die()  { echo "[install-cli-tools] ERROR: $*" >&2; exit 1; }

require() {
    command -v "$1" > /dev/null 2>&1 || die "Required tool not found: $1"
}

require curl
require tar

# -----------------------------------------------------------------------------
#  1. Ookla Speedtest CLI
# -----------------------------------------------------------------------------
log "Installing Ookla Speedtest CLI v${OOKLA_VERSION}..."

OOKLA_URL="https://install.speedtest.net/app/cli/ookla-speedtest-${OOKLA_VERSION}-linux-x86_64.tgz"

curl -fsSL "${OOKLA_URL}" \
    | tar -xz -C /tmp \
    && install -m 0755 /tmp/speedtest "${INSTALL_DIR}/speedtest" \
    && rm -f /tmp/speedtest /tmp/speedtest.md5 /tmp/speedtest.5 \
    || die "Failed to install Ookla Speedtest CLI"

log "Ookla Speedtest CLI v${OOKLA_VERSION} installed."

# -----------------------------------------------------------------------------
#  2. LibreSpeed CLI
# -----------------------------------------------------------------------------
log "Installing LibreSpeed CLI v${LIBRESPEED_VERSION}..."

LIBRESPEED_URL="https://github.com/librespeed/speedtest-cli/releases/download/v${LIBRESPEED_VERSION}/librespeed-cli_${LIBRESPEED_VERSION}_linux_amd64.tar.gz"

curl -fsSL "${LIBRESPEED_URL}" \
    | tar -xz -C "${INSTALL_DIR}" librespeed-cli \
    && chmod 755 "${INSTALL_DIR}/librespeed-cli" \
    || die "Failed to install LibreSpeed CLI"

log "LibreSpeed CLI v${LIBRESPEED_VERSION} installed."

# -----------------------------------------------------------------------------
#  3. Netflix Fast CLI
#     Releases: https://github.com/mikkelam/fast-cli/releases
#     Archive layout: fast-cli-x86_64-linux.tar.gz → ./fast-cli binary
# -----------------------------------------------------------------------------
log "Installing fast-cli v${FAST_CLI_VERSION}..."

FAST_CLI_URL="https://github.com/mikkelam/fast-cli/releases/download/v${FAST_CLI_VERSION}/fast-cli-x86_64-linux.tar.gz"
FAST_CLI_TMP="$(mktemp -d)"

curl -fsSL "${FAST_CLI_URL}" \
    | tar -xz -C "${FAST_CLI_TMP}" \
    && install -m 0755 "${FAST_CLI_TMP}/fast-cli" "${INSTALL_DIR}/fast-cli" \
    && rm -rf "${FAST_CLI_TMP}" \
    || die "Failed to install fast-cli"

log "fast-cli v${FAST_CLI_VERSION} installed."

# -----------------------------------------------------------------------------
#  4. Cloudflare Speed CLI
#     Releases: https://github.com/kavehtehrani/cloudflare-speed-cli/releases
#     Archive layout: cloudflare-speed-cli-x86_64-unknown-linux-musl.tar.xz
#                     → cloudflare-speed-cli-x86_64-unknown-linux-musl/cloudflare-speed-cli
# -----------------------------------------------------------------------------
log "Installing Cloudflare Speed CLI v${CLOUDFLARE_SPEED_CLI_VERSION}..."

CF_ARCH="x86_64-unknown-linux-musl"
CF_PKG="cloudflare-speed-cli-${CF_ARCH}"
CF_FILE="${CF_PKG}.tar.xz"
CF_URL="https://github.com/kavehtehrani/cloudflare-speed-cli/releases/download/v${CLOUDFLARE_SPEED_CLI_VERSION}/${CF_FILE}"
CF_TMP="$(mktemp -d)"

curl -fsSL "${CF_URL}" -o "${CF_TMP}/${CF_FILE}" \
    || die "Failed to download Cloudflare Speed CLI"

tar -xJf "${CF_TMP}/${CF_FILE}" -C "${CF_TMP}" \
    || die "Failed to extract Cloudflare Speed CLI"

install -m 0755 "${CF_TMP}/${CF_PKG}/cloudflare-speed-cli" \
    "${INSTALL_DIR}/cloudflare-speed-cli" \
    || die "Failed to install Cloudflare Speed CLI binary"

rm -rf "${CF_TMP}"
log "Cloudflare Speed CLI v${CLOUDFLARE_SPEED_CLI_VERSION} installed."

# -----------------------------------------------------------------------------
#  Verify all binaries are in place
# -----------------------------------------------------------------------------
log "Verifying installed binaries..."

for bin in speedtest librespeed-cli fast-cli cloudflare-speed-cli; do
    if [ -x "${INSTALL_DIR}/${bin}" ]; then
        log "  ✓ ${bin}"
    else
        die "Binary not executable or missing: ${INSTALL_DIR}/${bin}"
    fi
done

log "All CLI tools installed successfully."
