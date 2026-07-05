#!/bin/bash
# Import PostgreSQL backup ke server production
# Usage: sudo bash deploy/backup/import-backup.sh [sql_file] [db_name] [db_user] [db_pass]
#
# Contoh:
#   sudo bash deploy/backup/import-backup.sh backup_haloatlet_v2.sql
#   sudo bash deploy/backup/import-backup.sh backup_haloatlet_v2.sql haloatlet_v2 haloatlet_v2 mypassword

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/../lib/common.sh"

require_root

SQL_FILE="${1:-}"
DB_NAME="${2:-siperisai}"
DB_USER="${3:-siperisai}"
DB_PASS="${4:-$(gen_password 24)}"

[[ -n "$SQL_FILE" ]] || die "Usage: sudo bash deploy/backup/import-backup.sh path/to/backup.sql [db_name] [db_user] [db_pass]"
[[ -f "$SQL_FILE" ]] || die "File tidak ditemukan: $SQL_FILE"

log_banner "Import PostgreSQL Backup"
log_info "File   : $SQL_FILE ($(du -sh "$SQL_FILE" | cut -f1))"
log_info "DB     : $DB_NAME"
log_info "User   : $DB_USER"

# Cek PostgreSQL running
systemctl is-active --quiet postgresql || die "PostgreSQL tidak berjalan. Jalankan dulu: sudo bash deploy/setup-server.sh"

# --- Buat role & database ---
log_step "Membuat PostgreSQL role: $DB_USER..."
sudo -u postgres psql <<PSQL
DO \$\$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = '${DB_USER}') THEN
        CREATE USER ${DB_USER} WITH PASSWORD '${DB_PASS}';
        RAISE NOTICE 'Role ${DB_USER} dibuat.';
    ELSE
        ALTER USER ${DB_USER} WITH PASSWORD '${DB_PASS}';
        RAISE NOTICE 'Role ${DB_USER} sudah ada, password diupdate.';
    END IF;
END
\$\$;
PSQL

log_step "Membuat database: $DB_NAME..."
sudo -u postgres psql <<PSQL
SELECT pg_terminate_backend(pid)
FROM pg_stat_activity
WHERE datname = '${DB_NAME}' AND pid <> pg_backend_pid();

DROP DATABASE IF EXISTS ${DB_NAME};
CREATE DATABASE ${DB_NAME} OWNER ${DB_USER};
GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};
PSQL

# --- Import SQL ---
log_step "Importing SQL dump..."
log_info "Catatan: pesan '\restrict' / '\unrestrict' dari Lerd adalah normal, bukan error."

export PGPASSWORD="$DB_PASS"

# Jalankan import sebagai superuser (postgres) agar OWNER TO bisa berjalan
if sudo -u postgres psql \
    -v ON_ERROR_STOP=0 \
    -d "$DB_NAME" \
    < "$SQL_FILE" 2>&1 | grep -v "^psql:" | grep -v "\\\\restrict" | grep -v "\\\\unrestrict"; then
    log_ok "Import selesai"
else
    # Coba sekali lagi tanpa filter jika gagal
    log_warn "Cek output di atas. Jika ada error kritis, import mungkin tidak sempurna."
fi

unset PGPASSWORD

# --- Grant ke user ---
log_step "Grant privileges ke ${DB_USER}..."
sudo -u postgres psql -d "$DB_NAME" <<PSQL
GRANT ALL ON SCHEMA public TO ${DB_USER};
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO ${DB_USER};
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO ${DB_USER};
GRANT ALL PRIVILEGES ON ALL FUNCTIONS IN SCHEMA public TO ${DB_USER};
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO ${DB_USER};
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO ${DB_USER};
PSQL

# --- Verifikasi ---
log_step "Verifikasi tables..."
TABLE_COUNT=$(sudo -u postgres psql -d "$DB_NAME" -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public';" 2>/dev/null | tr -d ' ')
log_ok "Tables ditemukan: ${TABLE_COUNT}"

# Tampilkan beberapa tabel
sudo -u postgres psql -d "$DB_NAME" -c "\dt public.*" 2>/dev/null | head -30

# --- Update config ---
APP_ROOT="/home/wanforge/www/roniadiatmoko/siperisai"
if [[ -d "$APP_ROOT" ]]; then
    log_step "Mengupdate common/config/main-local.php..."
    cat > "$APP_ROOT/common/config/main-local.php" <<PHP
<?php

return [
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'pgsql:host=127.0.0.1;port=5432;dbname=${DB_NAME}',
            'username' => '${DB_USER}',
            'password' => '${DB_PASS}',
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
        ],
    ],
];
PHP
    OWNER_USER=$(stat -c '%U' "$APP_ROOT" 2>/dev/null || echo "wanforge")
    chown "$OWNER_USER" "$APP_ROOT/common/config/main-local.php" 2>/dev/null || true
    log_ok "main-local.php diupdate"
fi

# --- Simpan credentials ---
CRED_FILE="/root/siperisai_db_credentials.txt"
cat > "$CRED_FILE" <<CRED
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
DB_HOST=127.0.0.1
DB_PORT=5432
CRED
chmod 600 "$CRED_FILE"

log_ok "Credentials disimpan di: $CRED_FILE"

log_banner "Import Selesai!"
echo "  DB Name : $DB_NAME"
echo "  DB User : $DB_USER"
echo "  DB Pass : $DB_PASS"
echo "  Tables  : $TABLE_COUNT"
echo ""
log_info "Jika perlu jalankan Yii migration (untuk tabel baru jika ada):"
log_info "  php yii migrate --interactive=0"
log_info ""
