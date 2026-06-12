#!/bin/bash

# This has been separated due to a weird issue when using the pre built binary, the result is different.

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Default install location
DEFAULT_INSTALL_DIR="/usr/local/bin"
INSTALL_DIR="${INSTALL_DIR:-$DEFAULT_INSTALL_DIR}"

# GitHub repository
REPO="mikkelam/fast-cli"
BINARY_NAME="fast-cli"

# Version pin — set via env var, --version flag, or falls back to latest
FAST_CLI_VERSION="${FAST_CLI_VERSION:-}"

# Print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Detect OS and architecture
detect_platform() {
    local os arch

    case "$(uname -s)" in
        Linux*)
            os="linux"
            ;;
        Darwin*)
            os="macos"
            ;;
        *)
            print_error "Unsupported operating system: $(uname -s)"
            exit 1
            ;;
    esac

    case "$(uname -m)" in
        x86_64|amd64)
            arch="x86_64"
            ;;
        aarch64|arm64)
            arch="aarch64"
            ;;
        armv7l)
            arch="armv7"
            ;;
        *)
            print_error "Unsupported architecture: $(uname -m)"
            exit 1
            ;;
    esac

    echo "${BINARY_NAME}-${arch}-${os}"
}

# Resolve version: honour pin, fall back to latest API lookup
resolve_version() {
    if [ -n "${FAST_CLI_VERSION}" ]; then
        # Strip leading 'v' if present so we normalise to bare semver,
        # then re-add it since GitHub tags use the 'v' prefix.
        local bare="${FAST_CLI_VERSION#v}"
        echo "v${bare}"
        return
    fi

    print_warning "FAST_CLI_VERSION not set — resolving latest from GitHub API..."
    local version
    version=$(curl -s "https://api.github.com/repos/${REPO}/releases/latest" \
        | grep '"tag_name":' \
        | sed -E 's/.*"([^"]+)".*/\1/')

    if [ -z "$version" ]; then
        print_error "Failed to resolve latest version from GitHub API"
        exit 1
    fi

    echo "$version"
}

# Download and install
install_fast_cli() {
    local platform version download_url temp_dir

    print_status "Detecting platform..."
    platform=$(detect_platform)
    print_status "Platform detected: $platform"

    print_status "Resolving version..."
    version=$(resolve_version)
    print_status "Version: $version"

    # Versioned download URL — never uses /releases/latest/download/ so the
    # exact artifact is always reproducible.
    download_url="https://github.com/${REPO}/releases/download/${version}/${platform}.tar.gz"

    # Create temporary directory
    temp_dir=$(mktemp -d)
    trap "rm -rf $temp_dir" EXIT

    print_status "Downloading fast-cli..."
    if ! curl -L --fail --silent --show-error "$download_url" -o "$temp_dir/fast-cli.tar.gz"; then
        print_error "Failed to download fast-cli from $download_url"
        print_error "Please check if a release exists for your platform and version"
        exit 1
    fi

    print_status "Extracting archive..."
    if ! tar -xzf "$temp_dir/fast-cli.tar.gz" -C "$temp_dir"; then
        print_error "Failed to extract archive"
        exit 1
    fi

    # Check if we need sudo for installation
    if [ ! -w "$INSTALL_DIR" ]; then
        if [ "$INSTALL_DIR" = "$DEFAULT_INSTALL_DIR" ]; then
            print_warning "Installing to $INSTALL_DIR requires sudo privileges"
            SUDO="sudo"
        else
            print_error "No write permission to $INSTALL_DIR"
            exit 1
        fi
    fi

    print_status "Installing to $INSTALL_DIR..."
    $SUDO mkdir -p "$INSTALL_DIR"
    $SUDO cp "$temp_dir/$BINARY_NAME" "$INSTALL_DIR/$BINARY_NAME"
    $SUDO chmod +x "$INSTALL_DIR/$BINARY_NAME"

    print_status "Installation complete!"
    echo
    echo -e "${GREEN}✓${NC} fast-cli ${version} installed to $INSTALL_DIR/$BINARY_NAME"
    echo
    echo "Try it out:"
    echo "  $BINARY_NAME --help"
    echo "  $BINARY_NAME --json"
}

# Show usage
show_usage() {
    cat << EOF
Fast-CLI Installer

Usage: $0 [OPTIONS]

Options:
    --version VER   Install a specific version (e.g. 0.3.5 or v0.3.5)
    --prefix DIR    Install to custom directory (default: $DEFAULT_INSTALL_DIR)
    --help          Show this help message

Environment Variables:
    FAST_CLI_VERSION    Pin a specific version (e.g. FAST_CLI_VERSION=0.3.5)
    INSTALL_DIR         Custom installation directory

Examples:
    # Install pinned version
    FAST_CLI_VERSION=0.3.5 $0

    # Install pinned version via flag
    $0 --version 0.3.5

    # Install latest (no pin)
    $0

    # Install to custom directory
    $0 --prefix /opt/bin

EOF
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --version)
            FAST_CLI_VERSION="$2"
            shift 2
            ;;
        --prefix)
            INSTALL_DIR="$2"
            shift 2
            ;;
        --help|-h)
            show_usage
            exit 0
            ;;
        *)
            print_error "Unknown option: $1"
            show_usage
            exit 1
            ;;
    esac
done

# Check dependencies
check_dependencies() {
    local missing_deps=()

    for cmd in curl tar; do
        if ! command -v "$cmd" > /dev/null 2>&1; then
            missing_deps+=("$cmd")
        fi
    done

    if [ ${#missing_deps[@]} -ne 0 ]; then
        print_error "Missing required dependencies: ${missing_deps[*]}"
        print_error "Please install them and try again"
        exit 1
    fi
}

# Main
main() {
    echo "Fast-CLI Installer"
    echo "=================="
    echo

    check_dependencies
    install_fast_cli
}

main "$@"
