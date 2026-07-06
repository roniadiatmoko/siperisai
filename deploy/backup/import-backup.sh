#!/bin/bash
# Import MySQL backup ke server production
# Usage: sudo bash deploy/backup/import-backup.sh [sql_file] [db_name] [db_user] [db_pass]
#
# Contoh:
#   sudo bash deploy/backup/import-backup.sh backup_siperisai.sql

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/../lib/common.sh"

require_root

SQL_FILE="${1:-}"
DB_NAME="${2:-siperisai}"
DB_USER="${3:-siperisai}"
DB_PASS="${4:-$(gen_password 24)}"

[[ -n "$SQL_FILE" ]] || die "Usage: sudo bash deploy/backup/import-backup.sh path/to/backup.sql [db_name] [db_user] [db_pass]"
[[ -f "$SQL_FILE" ]] || die "File tidak ditemukan: $SQL_FILE"

log_banner "Import MySQL/MariaDB Backup"
log_info "File   : $SQL_FILE ($(du -sh "$SQL_FILE" | cut -f1))"
log_info "DB     : $DB_NAME"
log_info "User   : $DB_USER"

# Cek MySQL running
systemctl is-active --quiet mariadb || systemctl is-active --quiet mysql || die "MySQL/MariaDB tidak berjalan. Jalankan dulu: sudo bash deploy/setup-server.sh"

# --- Buat database & user ---
log_step "Membuat MySQL database & user..."
mysql -u root <<MYSQL 2>/dev/null
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
MYSQL

# --- Import SQL ---
log_step "Importing SQL dump..."
if mysql -u root "$DB_NAME" < "$SQL_FILE"; then
    log_ok "Import selesai"
else
    die "Import GAGAL"
fi

# --- Verifikasi ---
log_step "Verifikasi tables..."
TABLE_COUNT=$(mysql -u root -t -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '${DB_NAME}';" 2>/dev/null | tail -2 | head -1 | tr -d ' |')
log_ok "Tables ditemukan: ${TABLE_COUNT:-0}"

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
            'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=${DB_NAME}',
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
DB_PORT=3306
CRED
chmod 600 "$CRED_FILE"

log_ok "Credentials disimpan di: $CRED_FILE"

log_banner "Import Selesai!"
echo "  DB Name : $DB_NAME"
echo "  DB User : $DB_USER"
echo "  DB Pass : $DB_PASS"
echo "  Tables  : ${TABLE_COUNT:-0}"
echo ""
log_info "Jika perlu jalankan Yii migration (untuk tabel baru jika ada):"
log_info "  php yii migrate --interactive=0"
echo ""
