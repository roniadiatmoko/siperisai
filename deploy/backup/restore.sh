#!/bin/bash
# Restore backup
# Usage:
#   sudo bash backup/restore.sh [config_file] [backup_file.sql.gz]
#
# Contoh:
#   sudo bash backup/restore.sh backup.conf /var/backups/haloatlet/2026/07/01/haloatlet_20260701_020000_db.sql.gz
#   sudo bash backup/restore.sh backup.conf s3://bucket/backups/haloatlet/2026/07/01/haloatlet_20260701_020000_db.sql.gz

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/../lib/common.sh"

require_root

CONF="${1:-$SCRIPT_DIR/backup.conf}"
BACKUP_FILE="$2"

[[ -f "$CONF" ]] || die "Config tidak ditemukan: $CONF"
source "$CONF"

[[ -n "$BACKUP_FILE" ]] || die "Usage: sudo bash backup/restore.sh backup.conf BACKUP_FILE"

log_banner "Restore Backup"
log_warn "PERHATIAN: Ini akan MENIMPA database ${BACKUP_DB_NAME}!"
confirm "Yakin restore dari: ${BACKUP_FILE}?" || exit 0

WORK_DIR=$(mktemp -d)
trap 'rm -rf "$WORK_DIR"' EXIT

# --- Download dari S3 jika URL S3 ---
LOCAL_FILE="$BACKUP_FILE"
if [[ "$BACKUP_FILE" == s3://* ]]; then
    log_step "Download dari S3..."
    export AWS_ACCESS_KEY_ID AWS_SECRET_ACCESS_KEY AWS_DEFAULT_REGION
    AWS_ARGS=()
    [[ -n "$AWS_ENDPOINT_URL" ]] && AWS_ARGS+=("--endpoint-url" "$AWS_ENDPOINT_URL")
    LOCAL_FILE="$WORK_DIR/$(basename "$BACKUP_FILE")"
    aws s3 cp "$BACKUP_FILE" "$LOCAL_FILE" "${AWS_ARGS[@]}" || die "Download S3 gagal"
    log_ok "Downloaded: $LOCAL_FILE"
fi

[[ -f "$LOCAL_FILE" ]] || die "File tidak ditemukan: $LOCAL_FILE"

# Decompress jika .gz
SQL_FILE="$LOCAL_FILE"
if [[ "$LOCAL_FILE" == *.gz ]]; then
    log_step "Decompressing..."
    SQL_FILE="${WORK_DIR}/restore.sql"
    gunzip -c "$LOCAL_FILE" > "$SQL_FILE"
fi

# --- Restore ---
log_step "Restoring database ${BACKUP_DB_NAME} dari ${BACKUP_DB_TYPE}..."

case "${BACKUP_DB_TYPE:-pgsql}" in
    pgsql|postgresql)
        export PGPASSWORD="$BACKUP_DB_PASS"

        # Drop & recreate
        if confirm "Drop & recreate database ${BACKUP_DB_NAME}?"; then
            sudo -u postgres psql -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname='${BACKUP_DB_NAME}';" 2>/dev/null || true
            sudo -u postgres psql -c "DROP DATABASE IF EXISTS ${BACKUP_DB_NAME};" 2>/dev/null || true
            sudo -u postgres psql -c "CREATE DATABASE ${BACKUP_DB_NAME} OWNER ${BACKUP_DB_USER};" 2>/dev/null || true
        fi

        psql \
            -h "$BACKUP_DB_HOST" \
            -p "$BACKUP_DB_PORT" \
            -U "$BACKUP_DB_USER" \
            -d "$BACKUP_DB_NAME" \
            < "$SQL_FILE" \
            && log_ok "PostgreSQL restore OK" \
            || die "PostgreSQL restore GAGAL"
        unset PGPASSWORD
        ;;

    mysql|mariadb)
        mysql \
            -h "$BACKUP_DB_HOST" \
            -P "$BACKUP_DB_PORT" \
            -u "$BACKUP_DB_USER" \
            -p"$BACKUP_DB_PASS" \
            "$BACKUP_DB_NAME" \
            < "$SQL_FILE" \
            && log_ok "MySQL restore OK" \
            || die "MySQL restore GAGAL"
        ;;
esac

log_ok "Restore selesai dari: $BACKUP_FILE"
log_warn "Jangan lupa jalankan: php artisan optimize:clear"
