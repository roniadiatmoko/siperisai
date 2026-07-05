#!/bin/bash
# Auto Backup Script: Database + Files → Local / S3 / FTP / SFTP
# Usage:
#   sudo bash backup/backup.sh [config_file]
#   sudo bash backup/backup.sh                          # pakai backup.conf di folder ini
#   sudo bash backup/backup.sh /path/to/backup.conf    # custom config
#
# Setup cron (daily 02:00):
#   0 2 * * * root bash /path/to/deploy/backup/backup.sh >> /var/log/backup-haloatlet.log 2>&1

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/../lib/common.sh"

# ============================================================
# Load config
# ============================================================
CONF="${1:-$SCRIPT_DIR/backup.conf}"
[[ -f "$CONF" ]] || die "Config tidak ditemukan: $CONF\nCopy dari: $SCRIPT_DIR/backup.conf.example"
source "$CONF"

# Defaults
BACKUP_APP_NAME="${BACKUP_APP_NAME:-app}"
BACKUP_BACKENDS="${BACKUP_BACKENDS:-local}"
BACKUP_LOCAL_DIR="${BACKUP_LOCAL_DIR:-/var/backups/${BACKUP_APP_NAME}}"
BACKUP_RETAIN_DAYS="${BACKUP_RETAIN_DAYS:-30}"

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DATE_DIR=$(date +%Y-%m-%d)
BACKUP_DIR=$(mktemp -d "/tmp/backup_${BACKUP_APP_NAME}_XXXXXX")
BACKUP_PREFIX="${BACKUP_APP_NAME}_${TIMESTAMP}"
EXIT_CODE=0

log_banner "Backup: $BACKUP_APP_NAME (${TIMESTAMP})"
trap 'rm -rf "$BACKUP_DIR"' EXIT

# ============================================================
# Notifikasi helper
# ============================================================
notify_error() {
    local msg="[BACKUP ERROR] ${BACKUP_APP_NAME}: $*"
    log_error "$msg"

    if [[ -n "$BACKUP_NOTIFY_TELEGRAM_TOKEN" && -n "$BACKUP_NOTIFY_TELEGRAM_CHAT_ID" ]]; then
        curl -s -X POST "https://api.telegram.org/bot${BACKUP_NOTIFY_TELEGRAM_TOKEN}/sendMessage" \
            -d "chat_id=${BACKUP_NOTIFY_TELEGRAM_CHAT_ID}" \
            -d "text=${msg}" &>/dev/null || true
    fi

    if [[ -n "$BACKUP_NOTIFY_EMAIL" ]] && check_cmd mail; then
        echo "$msg" | mail -s "$msg" "$BACKUP_NOTIFY_EMAIL" 2>/dev/null || true
    fi

    EXIT_CODE=1
}

# ============================================================
# 1. DATABASE BACKUP
# ============================================================
if [[ "${BACKUP_DB:-true}" == "true" ]]; then
    log_step "Database backup (${BACKUP_DB_TYPE})..."

    DB_FILE="${BACKUP_DIR}/${BACKUP_PREFIX}_db.sql"

    case "${BACKUP_DB_TYPE:-pgsql}" in
        pgsql|postgresql)
            check_cmd pg_dump || apt-get install -y -qq postgresql-client
            export PGPASSWORD="$BACKUP_DB_PASS"
            if pg_dump \
                -h "$BACKUP_DB_HOST" \
                -p "$BACKUP_DB_PORT" \
                -U "$BACKUP_DB_USER" \
                --no-password \
                --format=plain \
                --clean \
                --if-exists \
                "$BACKUP_DB_NAME" > "$DB_FILE" 2>/tmp/backup_pg_err; then
                log_ok "PostgreSQL dump OK: $(du -sh "$DB_FILE" | cut -f1)"
            else
                notify_error "PostgreSQL dump GAGAL: $(cat /tmp/backup_pg_err)"
            fi
            unset PGPASSWORD
            ;;

        mysql|mariadb)
            check_cmd mysqldump || apt-get install -y -qq mysql-client
            if mysqldump \
                -h "$BACKUP_DB_HOST" \
                -P "$BACKUP_DB_PORT" \
                -u "$BACKUP_DB_USER" \
                -p"$BACKUP_DB_PASS" \
                --single-transaction \
                --quick \
                --lock-tables=false \
                "$BACKUP_DB_NAME" > "$DB_FILE" 2>/tmp/backup_mysql_err; then
                log_ok "MySQL dump OK: $(du -sh "$DB_FILE" | cut -f1)"
            else
                notify_error "MySQL dump GAGAL: $(cat /tmp/backup_mysql_err)"
            fi
            ;;

        *)
            log_warn "DB type tidak dikenal: ${BACKUP_DB_TYPE}. Skip."
            ;;
    esac

    # Compress
    if [[ -f "$DB_FILE" ]]; then
        gzip -9 "$DB_FILE"
        DB_FILE="${DB_FILE}.gz"
        log_info "Compressed: $(du -sh "$DB_FILE" | cut -f1)"
    fi
fi

# ============================================================
# 2. FILES BACKUP
# ============================================================
if [[ "${BACKUP_FILES:-false}" == "true" && -n "$BACKUP_APP_ROOT" ]]; then
    log_step "Files backup..."

    FILES_FILE="${BACKUP_DIR}/${BACKUP_PREFIX}_files.tar.gz"
    EXCLUDE_ARGS=()

    for excl in "${BACKUP_FILES_EXCLUDE[@]:-}"; do
        EXCLUDE_ARGS+=("--exclude=${BACKUP_APP_ROOT}/${excl}")
    done

    INCLUDE_DIRS=()
    for dir in "${BACKUP_FILES_DIRS[@]:-storage}"; do
        FULL_DIR="${BACKUP_APP_ROOT}/${dir}"
        [[ -d "$FULL_DIR" ]] && INCLUDE_DIRS+=("$FULL_DIR")
    done

    if [[ ${#INCLUDE_DIRS[@]} -gt 0 ]]; then
        if tar -czf "$FILES_FILE" "${EXCLUDE_ARGS[@]}" "${INCLUDE_DIRS[@]}" 2>/tmp/backup_tar_err; then
            log_ok "Files backup OK: $(du -sh "$FILES_FILE" | cut -f1)"
        else
            notify_error "Files backup GAGAL: $(cat /tmp/backup_tar_err)"
        fi
    else
        log_warn "Tidak ada directory yang valid untuk backup files."
    fi
fi

# ============================================================
# List backup files yang dibuat
# ============================================================
BACKUP_FILES_CREATED=($(ls "$BACKUP_DIR"/*.{sql.gz,tar.gz} 2>/dev/null))
if [[ ${#BACKUP_FILES_CREATED[@]} -eq 0 ]]; then
    log_warn "Tidak ada file backup yang dibuat."
    exit 1
fi

log_info "File backup:"
for f in "${BACKUP_FILES_CREATED[@]}"; do
    echo "  $(du -sh "$f" | cut -f1)  $(basename "$f")"
done

# ============================================================
# Upload ke backends
# ============================================================

# --- LOCAL ---
upload_local() {
    local dest="${BACKUP_LOCAL_DIR}/${DATE_DIR}"
    mkdir -p "$dest"
    for f in "${BACKUP_FILES_CREATED[@]}"; do
        cp "$f" "$dest/"
    done
    log_ok "Local backup: ${dest}/"

    # Hapus backup lama
    find "$BACKUP_LOCAL_DIR" -type f \( -name "*.sql.gz" -o -name "*.tar.gz" \) \
        -mtime "+${BACKUP_RETAIN_DAYS}" -delete
    log_info "Retain ${BACKUP_RETAIN_DAYS} hari — lama dihapus."
}

# --- S3 ---
upload_s3() {
    if ! check_cmd aws; then
        log_step "Installing AWS CLI..."
        curl -s "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o /tmp/awscliv2.zip
        unzip -q /tmp/awscliv2.zip -d /tmp/
        /tmp/aws/install --update
        rm -rf /tmp/awscliv2.zip /tmp/aws
    fi

    local s3_dest="s3://${BACKUP_S3_BUCKET}/${BACKUP_S3_PREFIX:-backups}/${DATE_DIR}/"
    local aws_args=()

    export AWS_ACCESS_KEY_ID AWS_SECRET_ACCESS_KEY AWS_DEFAULT_REGION
    [[ -n "$AWS_ENDPOINT_URL" ]] && aws_args+=("--endpoint-url" "$AWS_ENDPOINT_URL")

    # IDCloudHost IS3 (Ceph/RadosGW) tidak support chunked encoding + CRC64NVME trailer
    # AWS CLI v2.35+ menambahkan ini secara default → HTTP 411 MissingContentLength
    # Fix: payload_signing_enabled=false + request_checksum_calculation=when_required
    local aws_conf_dir="${HOME:-/root}/.aws"
    mkdir -p "$aws_conf_dir"
    if ! grep -q "request_checksum_calculation" "$aws_conf_dir/config" 2>/dev/null; then
        cat > "$aws_conf_dir/config" <<AWSCFG
[default]
s3 =
    payload_signing_enabled = false
request_checksum_calculation = when_required
response_checksum_validation = when_required
AWSCFG
    fi

    for f in "${BACKUP_FILES_CREATED[@]}"; do
        if aws s3 cp "$f" "${s3_dest}$(basename "$f")" "${aws_args[@]}" --quiet; then
            log_ok "S3 upload OK: $(basename "$f") → $s3_dest"
        else
            notify_error "S3 upload GAGAL: $(basename "$f")"
        fi
    done

    # S3 lifecycle: hapus file lama
    if [[ -n "${BACKUP_S3_RETAIN_DAYS:-}" ]]; then
        CUTOFF_DATE=$(date -d "-${BACKUP_S3_RETAIN_DAYS} days" +%Y-%m-%d)
        aws s3 ls "s3://${BACKUP_S3_BUCKET}/${BACKUP_S3_PREFIX:-backups}/" \
            --recursive "${aws_args[@]}" 2>/dev/null \
            | awk -v cutoff="$CUTOFF_DATE" '$1 < cutoff {print $4}' \
            | while read -r key; do
                aws s3 rm "s3://${BACKUP_S3_BUCKET}/${key}" "${aws_args[@]}" --quiet && \
                    log_info "S3 deleted old: $key"
            done
    fi
}

# --- FTP ---
upload_ftp() {
    if ! check_cmd lftp; then
        apt-get install -y -qq lftp
    fi

    local FTP_URL
    if [[ "${BACKUP_FTP_SSL:-true}" == "true" ]]; then
        FTP_URL="ftps://${BACKUP_FTP_HOST}:${BACKUP_FTP_PORT:-21}"
    else
        FTP_URL="ftp://${BACKUP_FTP_HOST}:${BACKUP_FTP_PORT:-21}"
    fi

    local REMOTE_DIR="${BACKUP_FTP_DIR}/${DATE_DIR}"

    for f in "${BACKUP_FILES_CREATED[@]}"; do
        if lftp -u "${BACKUP_FTP_USER},${BACKUP_FTP_PASS}" "$FTP_URL" <<FTPCMD 2>/tmp/backup_ftp_err
set ssl:verify-certificate no
set ftp:ssl-force ${BACKUP_FTP_SSL:-true}
mkdir -p ${REMOTE_DIR}
put -O ${REMOTE_DIR} ${f}
bye
FTPCMD
        then
            log_ok "FTP upload OK: $(basename "$f") → $REMOTE_DIR"
        else
            notify_error "FTP upload GAGAL: $(basename "$f"): $(cat /tmp/backup_ftp_err)"
        fi
    done
}

# --- SFTP ---
upload_sftp() {
    local REMOTE_DIR="${BACKUP_SFTP_DIR}/${DATE_DIR}"
    local SSH_OPTS="-o StrictHostKeyChecking=no -o BatchMode=yes"
    [[ -n "$BACKUP_SFTP_KEY" ]] && SSH_OPTS+=" -i ${BACKUP_SFTP_KEY}"

    # Buat remote dir
    ssh $SSH_OPTS -p "${BACKUP_SFTP_PORT:-22}" \
        "${BACKUP_SFTP_USER}@${BACKUP_SFTP_HOST}" \
        "mkdir -p '${REMOTE_DIR}'" 2>/dev/null || true

    for f in "${BACKUP_FILES_CREATED[@]}"; do
        if scp $SSH_OPTS \
            -P "${BACKUP_SFTP_PORT:-22}" \
            "$f" \
            "${BACKUP_SFTP_USER}@${BACKUP_SFTP_HOST}:${REMOTE_DIR}/$(basename "$f")"; then
            log_ok "SFTP upload OK: $(basename "$f") → $REMOTE_DIR"
        else
            notify_error "SFTP upload GAGAL: $(basename "$f")"
        fi
    done
}

# --- Dispatch ke backends ---
for BACKEND in $BACKUP_BACKENDS; do
    log_step "Uploading ke backend: ${BACKEND^^}..."
    case "$BACKEND" in
        local) upload_local ;;
        s3)    upload_s3 ;;
        ftp)   upload_ftp ;;
        sftp)  upload_sftp ;;
        *)     log_warn "Backend tidak dikenal: $BACKEND" ;;
    esac
done

# ============================================================
# Summary
# ============================================================
echo ""
if [[ $EXIT_CODE -eq 0 ]]; then
    log_ok "Backup ${BACKUP_APP_NAME} SELESAI (${TIMESTAMP}) — backends: ${BACKUP_BACKENDS}"
else
    log_error "Backup selesai dengan ERROR. Cek log di atas."
fi

exit $EXIT_CODE
