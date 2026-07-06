#!/bin/bash
# First-time Yii2 Advanced app setup
# Usage: sudo bash app/first-setup.sh sites/siperisai.my.id.conf [backup.sql]
#   backup.sql (opsional): import dari backup, skip fresh migrate
# Jalankan SEKALI saat pertama deploy ke server baru

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/../lib/common.sh"

require_root

CONF_FILE="$1"
IMPORT_SQL="$2"   # opsional: path ke file .sql backup

[[ -n "$CONF_FILE" ]] || die "Usage: sudo bash app/first-setup.sh sites/DOMAIN.conf [backup.sql]"
load_site_config "$CONF_FILE"

log_banner "First-Time Yii2 App Setup: $SITE_DOMAIN"
log_info "App root : $SITE_ROOT"
log_info "PHP      : $SITE_PHP_VERSION"
log_info "Node     : $SITE_NODE_VERSION"
log_info "User     : $SITE_USER"

[[ -d "$SITE_ROOT" ]] || die "Directory tidak ditemukan: $SITE_ROOT"
[[ -f "$SITE_ROOT/yii" ]] || die "Bukan Yii2 Advanced project: $SITE_ROOT"

cd "$SITE_ROOT"

# --- Run Yii Initialization ---
log_step "Initializing Yii2 environment (Production)..."
sudo -u "$SITE_USER" "php${SITE_PHP_VERSION}" init --env=Production --overwrite=all

# --- Database setup ---
if [[ -n "${DB_NAME:-}" ]]; then
    log_step "Setup database MySQL/MariaDB: $DB_NAME"

    # Buat user & DB jika belum ada
    DB_PASS_ACTUAL="${DB_PASS:-$(gen_password 24)}"

    mysql -u root <<MYSQL 2>/dev/null || log_warn "DB mungkin sudah ada, lanjut..."
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS_ACTUAL}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
MYSQL

    # Write common/config/main-local.php
    log_step "Menulis database credentials ke common/config/main-local.php..."
    cat > "common/config/main-local.php" <<PHP
<?php

return [
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=${DB_NAME}',
            'username' => '${DB_USER}',
            'password' => '${DB_PASS_ACTUAL}',
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
        ],
    ],
];
PHP

    chown "$SITE_USER" "common/config/main-local.php"

    echo ""
    log_ok "Database credentials:"
    echo "  DB: $DB_NAME | User: $DB_USER | Pass: $DB_PASS_ACTUAL"
    echo "  Simpan di: /root/${SITE_DOMAIN}_credentials.txt"
    echo "DB_NAME=$DB_NAME" > "/root/${SITE_DOMAIN}_credentials.txt"
    echo "DB_USER=$DB_USER" >> "/root/${SITE_DOMAIN}_credentials.txt"
    echo "DB_PASS=$DB_PASS_ACTUAL" >> "/root/${SITE_DOMAIN}_credentials.txt"
    chmod 600 "/root/${SITE_DOMAIN}_credentials.txt"
fi

# --- Composer install ---
log_step "Composer install (no-dev)..."
sudo -u "$SITE_USER" composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

# --- Permissions setup ---
log_step "Setup directory permissions..."
chown -R "${SITE_USER}:www-data" "$SITE_ROOT"
find "$SITE_ROOT" -type d -exec chmod 755 {} +
find "$SITE_ROOT" -type f -exec chmod 644 {} +
chmod +x "$SITE_ROOT/yii" 2>/dev/null || true
find "$SITE_ROOT/deploy" -name "*.sh" -exec chmod +x {} +

# Buat runtime & assets folder and set permissions
for dir in backend/runtime backend/web/assets frontend/runtime frontend/web/assets console/runtime; do
    mkdir -p "$dir"
    chown -R "${SITE_USER}:www-data" "$dir"
    chmod -R 777 "$dir"
done

# Pastikan www-data bisa traverse home directory user
SITE_USER_HOME=$(getent passwd "$SITE_USER" | cut -d: -f6)
if [[ -d "$SITE_USER_HOME" ]]; then
    chmod o+x "$SITE_USER_HOME"
    log_ok "Home directory $SITE_USER_HOME: execute permission untuk www-data ditambahkan"
fi

# --- Database: import backup atau fresh migrate ---
if [[ -n "$IMPORT_SQL" ]]; then
    log_step "Import backup database dari: $IMPORT_SQL"
    bash "$SCRIPT_DIR/../backup/import-backup.sh" "$IMPORT_SQL" "$DB_NAME" "${DB_USER:-$DB_NAME}" "${DB_PASS_ACTUAL:-}"
    log_info "Menjalankan migrate untuk tabel baru (jika ada)..."
    sudo -u "$SITE_USER" "php${SITE_PHP_VERSION}" yii migrate --interactive=0 2>/dev/null || true
else
    log_step "Running database migrations..."
    sudo -u "$SITE_USER" "php${SITE_PHP_VERSION}" yii migrate --interactive=0
fi

# --- Sync DB password ke backup.conf (jika ada) ---
BACKUP_CONF="$SCRIPT_DIR/../backup/backup.conf"
if [[ -f "$BACKUP_CONF" ]]; then
    log_step "Sync DB password ke backup.conf..."
    if [[ -n "$DB_PASS_ACTUAL" ]]; then
        sed -i "s|^BACKUP_DB_PASS=.*|BACKUP_DB_PASS=\"${DB_PASS_ACTUAL}\"|" "$BACKUP_CONF"
        log_ok "backup.conf: BACKUP_DB_PASS diperbarui"
    fi
fi

# --- Backup Cron ---
BACKUP_CRON_FILE="/etc/cron.d/backup-${SITE_DOMAIN//./-}"
if [[ ! -f "$BACKUP_CRON_FILE" ]] && [[ -f "$BACKUP_CONF" ]]; then
    log_step "Setup backup cron harian (02:00)..."
    cat > "$BACKUP_CRON_FILE" <<CRON
# Backup otomatis ${SITE_DOMAIN} setiap hari jam 02:00
0 2 * * * root bash ${SCRIPT_DIR}/../backup/backup.sh ${BACKUP_CONF} >> /var/log/backup-${SITE_DOMAIN//./-}.log 2>&1
CRON
    chmod 644 "$BACKUP_CRON_FILE"
    log_ok "Backup cron dipasang: $BACKUP_CRON_FILE"
fi

log_ok "Setup selesai!"
log_info "Akses Frontend: https://${SITE_DOMAIN}"
log_info "Akses Backend: https://admin.${SITE_DOMAIN}"
