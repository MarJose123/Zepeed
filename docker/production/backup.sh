#!/bin/bash
# =============================================================================
# Zepeed — MariaDB Backup Script
#
# Usage:
#   ./backup.sh              # Backup to ./backups directory
#   ./backup.sh /path/to/dir # Backup to custom directory
#
# Environment variables (optional):
#   DB_HOST      - MariaDB host (default: zepeed-mariadb)
#   DB_PORT      - MariaDB port (default: 3306)
#   DB_DATABASE  - Database name (default: zepeed)
#   DB_USERNAME  - Database user (default: zepeed)
#   DB_PASSWORD  - Database password (required if not set in environment)
#   DB_ROOT_PASSWORD - Root password (alternative for full dump)
#   RETENTION    - Number of days to keep backups (default: 7)
# =============================================================================

set -euo pipefail

# Configuration
BACKUP_DIR="${1:-./backups}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS="${RETENTION_DAYS:-7}"

DB_HOST="${DB_HOST:-zepeed-mariadb}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-zepeed}"
DB_USERNAME="${DB_USERNAME:-zepeed}"
DB_PASSWORD="${DB_PASSWORD:-${DB_ROOT_PASSWORD:-}}"

# Validate
if [ -z "$DB_PASSWORD" ]; then
    echo "ERROR: DB_PASSWORD or DB_ROOT_PASSWORD must be set."
    exit 1
fi

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Backup file name
BACKUP_FILE="${BACKUP_DIR}/zepeed_backup_${TIMESTAMP}.sql.gz"

echo "Starting backup of '${DB_DATABASE}' database..."
echo "Backup file: ${BACKUP_FILE}"

# Run backup
export MYSQL_PWD="$DB_PASSWORD"
mariadb-dump \
    --host="$DB_HOST" \
    --port="$DB_PORT" \
    --user="$DB_USERNAME" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    "$DB_DATABASE" \
    | gzip > "$BACKUP_FILE"
unset MYSQL_PWD

# Verify backup file was created and is not empty
if [ ! -s "$BACKUP_FILE" ]; then
    echo "ERROR: Backup failed - file is empty or was not created."
    exit 1
fi

BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
echo "Backup completed successfully: ${BACKUP_FILE} (${BACKUP_SIZE})"

# Clean up old backups
echo "Cleaning up backups older than ${RETENTION_DAYS} days..."
find "$BACKUP_DIR" -name "zepeed_backup_*.sql.gz" -type f -mtime +"$RETENTION_DAYS" -delete
echo "Done."
