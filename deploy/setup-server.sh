#!/bin/bash
# ============================================================
# setup-server.sh — Setup server & aplikasi siperisai.my.id
#
# Satu script untuk segalanya: install software, konfigurasi
# aplikasi, import database, SSL, backup, firewall.
# Deteksi otomatis apa yang sudah ada, tanya skip atau ulang.
#
# Jalankan: sudo bash deploy/setup-server.sh
# ============================================================

# shellcheck source=/dev/null
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib/common.sh"
require_root

APP_DIR="/home/wanforge/www/roniadiatmoko/siperisai"
APP_USER="${SUDO_USER:-wanforge}"
APP_USER_HOME="/home/$APP_USER"
SITE_CONF="$SCRIPT_DIR/sites/siperisai.my.id.conf"
BACKUP_CONF="$SCRIPT_DIR/backup/backup.conf"
SQL_BACKUP="$APP_DIR/backup_siperisai.sql"
DOMAIN="siperisai.my.id"

# ============================================================
# BAGIAN 1 — DETEKSI SOFTWARE
# ============================================================
log_banner "Setup Server — Deteksi Software"

echo "  Server : $(hostname) ($(hostname -I 2>/dev/null | awk '{print $1}'))"
echo "  OS     : $(lsb_release -ds 2>/dev/null || uname -sr)"
echo "  User   : $APP_USER"
echo "  App    : $APP_DIR"
echo ""
echo "  Mendeteksi software yang terinstall..."

# Semua deteksi hanya cek keberadaan binary/file — tidak menjalankan program apapun

# System packages
SYS_STATUS="missing"; SYS_DETAIL="belum dipasang"
if command -v git &>/dev/null && command -v curl &>/dev/null; then
    SYS_STATUS="found"; SYS_DETAIL="git & curl tersedia"
fi

# PHP
PHP_STATUS="missing"; PHP_DETAIL="belum terinstall"
PHP_FOUND=()
for v in 8.1 8.2 8.3 8.4 8.5; do
    command -v "php${v}" &>/dev/null && PHP_FOUND+=("$v")
done
[[ ${#PHP_FOUND[@]} -gt 0 ]] && PHP_STATUS="found" && PHP_DETAIL="terinstall: ${PHP_FOUND[*]}"

# Composer
COMPOSER_STATUS="missing"; COMPOSER_DETAIL="belum terinstall"
[[ -x /usr/local/bin/composer || -x /usr/bin/composer ]] \
    && COMPOSER_STATUS="found" && COMPOSER_DETAIL="terinstall di /usr/local/bin"

# Nginx
NGINX_STATUS="missing"; NGINX_DETAIL="belum terinstall"
[[ -x /usr/sbin/nginx || -x /usr/bin/nginx ]] \
    && NGINX_STATUS="found" && NGINX_DETAIL="binary ditemukan"

# PostgreSQL
PG_STATUS="missing"; PG_DETAIL="belum terinstall"
[[ -x /usr/bin/psql ]] \
    && PG_STATUS="found" && PG_DETAIL="psql ditemukan"

# Redis
REDIS_STATUS="missing"; REDIS_DETAIL="belum terinstall"
[[ -x /usr/bin/redis-server || -x /usr/sbin/redis-server ]] \
    && REDIS_STATUS="found" && REDIS_DETAIL="binary ditemukan"

echo ""

echo "  ┌─ Software ─────────────────────────────────────────┐"
echo -e "  │  $([[ $SYS_STATUS    == found ]] && echo "${GREEN}✓${NC}" || echo "${YELLOW}○${NC}") System packages  : $SYS_DETAIL"
echo -e "  │  $([[ $PHP_STATUS    == found ]] && echo "${GREEN}✓${NC}" || echo "${YELLOW}○${NC}") PHP              : $PHP_DETAIL"
echo -e "  │  $([[ $COMPOSER_STATUS == found ]] && echo "${GREEN}✓${NC}" || echo "${YELLOW}○${NC}") Composer         : $COMPOSER_DETAIL"
echo -e "  │  $([[ $NGINX_STATUS  == found ]] && echo "${GREEN}✓${NC}" || echo "${YELLOW}○${NC}") Nginx            : $NGINX_DETAIL"
echo -e "  │  $([[ $PG_STATUS     == found ]] && echo "${GREEN}✓${NC}" || echo "${YELLOW}○${NC}") PostgreSQL       : $PG_DETAIL"
echo -e "  │  $([[ $REDIS_STATUS  == found ]] && echo "${GREEN}✓${NC}" || echo "${YELLOW}○${NC}") Redis            : $REDIS_DETAIL"
echo "  └────────────────────────────────────────────────────┘"
echo ""

# Tanya versi PHP
printf "  PHP version yang akan diinstall? [8.3] "
read -r INPUT_PHP
PHP_VERSIONS="${INPUT_PHP:-8.3}"
# Deteksi PHP aktif dari versi yang dipilih/ada
PHP_ACTIVE="${PHP_VERSIONS%% *}"  # ambil versi pertama

# ============================================================
# INSTALL SOFTWARE
# ============================================================
echo ""

if ask_step "System packages (git, curl, dll)" "$SYS_STATUS" "$SYS_DETAIL"; then
    bash "$SCRIPT_DIR/install/system.sh"
fi

if ask_step "PHP ($PHP_VERSIONS)" "$PHP_STATUS" "$PHP_DETAIL"; then
    # shellcheck disable=SC2086
    bash "$SCRIPT_DIR/install/php.sh" $PHP_VERSIONS
fi

if ask_step "Composer" "$COMPOSER_STATUS" "$COMPOSER_DETAIL"; then
    bash "$SCRIPT_DIR/install/composer.sh"
fi

if ask_step "Nginx" "$NGINX_STATUS" "$NGINX_DETAIL"; then
    bash "$SCRIPT_DIR/install/nginx.sh"
fi

if ask_step "PostgreSQL" "$PG_STATUS" "$PG_DETAIL"; then
    bash "$SCRIPT_DIR/install/postgresql.sh"
fi

if ask_step "Redis" "$REDIS_STATUS" "$REDIS_DETAIL"; then
    bash "$SCRIPT_DIR/install/redis.sh"
fi

# ============================================================
# BAGIAN 2 — DETEKSI APLIKASI
# ============================================================
log_banner "Setup Aplikasi — $DOMAIN"
echo "  Mendeteksi kondisi aplikasi..."

# Nginx vhost
VHOST_FILE="/etc/nginx/sites-available/$DOMAIN"
VHOST_STATUS="missing"; VHOST_DETAIL="belum ada"
if [[ -f "$VHOST_FILE" ]]; then
    VHOST_SIZE=$(wc -c < "$VHOST_FILE")
    if [[ "$VHOST_SIZE" -gt 100 ]]; then
        VHOST_STATUS="found"; VHOST_DETAIL="ada (${VHOST_SIZE} bytes)"
    else
        VHOST_DETAIL="ada tapi kosong/tidak valid (${VHOST_SIZE} bytes)"
    fi
fi

# Database — pg_isready sangat cepat, tidak perlu query
DB_STATUS="missing"; DB_DETAIL="belum ada"
if [[ -x /usr/bin/pg_isready ]] || command -v pg_isready &>/dev/null; then
    if timeout 3 pg_isready -U postgres -d siperisai -q 2>/dev/null; then
        DB_STATUS="found"; DB_DETAIL="siperisai aktif"
    elif timeout 3 pg_isready -U postgres -q 2>/dev/null; then
        DB_DETAIL="PostgreSQL aktif, database siperisai belum ada/belum terkonfigurasi"
    fi
elif [[ -S /var/run/postgresql/.s.PGSQL.5432 ]]; then
    DB_DETAIL="PostgreSQL berjalan (pg_isready tidak tersedia)"
fi

# Vendor
VENDOR_STATUS="missing"; VENDOR_DETAIL="belum diinstall"
[[ -f "$APP_DIR/vendor/autoload.php" ]] && VENDOR_STATUS="found" && VENDOR_DETAIL="sudah ada"

# SSL — cek file cert langsung, hindari jalankan certbot (bisa lambat)
SSL_STATUS="missing"; SSL_DETAIL="belum ada"
CERT_FILE="/etc/letsencrypt/live/$DOMAIN/fullchain.pem"
if [[ -f "$CERT_FILE" ]]; then
    EXPIRY=$(timeout 3 openssl x509 -enddate -noout -in "$CERT_FILE" 2>/dev/null | cut -d= -f2)
    SSL_STATUS="found"; SSL_DETAIL="valid, expire: ${EXPIRY:-?}"
fi

# Backup cron
BCRON_STATUS="missing"; BCRON_DETAIL="belum dipasang"
[[ -f "/etc/cron.d/backup-siperisai" ]] && BCRON_STATUS="found" && BCRON_DETAIL="/etc/cron.d/backup-siperisai ada"

# Firewall — cek file config UFW, hindari jalankan ufw status (lambat)
UFW_STATUS="missing"; UFW_DETAIL="belum aktif"
if grep -q "^ENABLED=yes" /etc/ufw/ufw.conf 2>/dev/null; then
    UFW_STATUS="found"; UFW_DETAIL="UFW aktif (via config)"
fi

echo "  ┌─ Aplikasi ─────────────────────────────────────────┐"
echo -e "  │  $([[ $VHOST_STATUS  == found ]] && echo "${GREEN}✓${NC}" || echo "${YELLOW}○${NC}") Nginx vhost      : $VHOST_DETAIL"
echo -e "  │  $([[ $DB_STATUS     == found ]] && echo "${GREEN}✓${NC}" || echo "${YELLOW}○${NC}") Database         : $DB_DETAIL"
echo -e "  │  $([[ $VENDOR_STATUS == found ]] && echo "${GREEN}✓${NC}" || echo "${YELLOW}○${NC}") Composer + Init  : $VENDOR_DETAIL"
echo -e "  │  $([[ $SSL_STATUS    == found ]] && echo "${GREEN}✓${NC}" || echo "${YELLOW}○${NC}") SSL certificate  : $SSL_DETAIL"
echo -e "  │  $([[ $BCRON_STATUS  == found ]] && echo "${GREEN}✓${NC}" || echo "${YELLOW}○${NC}") Backup cron      : $BCRON_DETAIL"
echo -e "  │  $([[ $UFW_STATUS    == found ]] && echo "${GREEN}✓${NC}" || echo "${YELLOW}○${NC}") Firewall UFW     : $UFW_DETAIL"
echo "  └────────────────────────────────────────────────────┘"
echo ""

# ============================================================
# SETUP APLIKASI
# ============================================================

# Nginx vhost
if ask_step "Nginx vhost ($DOMAIN)" "$VHOST_STATUS" "$VHOST_DETAIL"; then
    bash "$SCRIPT_DIR/site/create.sh" "$SITE_CONF" --no-ssl
fi

# Database
if ask_step "Database PostgreSQL (siperisai)" "$DB_STATUS" "$DB_DETAIL"; then
    if [[ -f "$SQL_BACKUP" ]]; then
        log_info "File backup ditemukan: $SQL_BACKUP"
        bash "$SCRIPT_DIR/backup/import-backup.sh" "$SQL_BACKUP" "siperisai" "siperisai"
    else
        log_warn "File $SQL_BACKUP tidak ditemukan — buat database kosong"
        DB_PASS=$(gen_password 24)
        sudo -u postgres psql <<PSQL 2>/dev/null || true
DO \$$ BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname='siperisai') THEN
        CREATE USER siperisai WITH PASSWORD '${DB_PASS}';
    END IF;
END \$\$;
CREATE DATABASE siperisai OWNER siperisai;
GRANT ALL PRIVILEGES ON DATABASE siperisai TO siperisai;
\c siperisai
GRANT ALL ON SCHEMA public TO siperisai;
PSQL

        cat > "$APP_DIR/common/config/main-local.php" <<PHP
<?php

return [
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'pgsql:host=127.0.0.1;port=5432;dbname=siperisai',
            'username' => 'siperisai',
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
        chown "${APP_USER}:www-data" "$APP_DIR/common/config/main-local.php"
        log_ok "Database dibuat (kosong)"
    fi
fi

# Composer install + Yii init + permissions
if ask_step "Composer install & Yii init" "$VENDOR_STATUS" "$VENDOR_DETAIL"; then
    cd "$APP_DIR" || exit 1
    sudo -u "$APP_USER" composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
    sudo -u "$APP_USER" "php${PHP_ACTIVE}" init --env=Production --overwrite=all
    
    # Set permissions
    chown -R "${APP_USER}:www-data" "$APP_DIR"
    chmod -R 755 "$APP_DIR"
    
    for dir in backend/runtime backend/web/assets frontend/runtime frontend/web/assets console/runtime; do
        mkdir -p "$dir"
        chown -R "${APP_USER}:www-data" "$dir"
        chmod -R 777 "$dir"
    done
    
    chmod o+x "$APP_USER_HOME"
    log_ok "Composer & Yii init selesai"
fi

# Yii migrations
if [[ -f "$APP_DIR/vendor/autoload.php" ]]; then
    cd "$APP_DIR" || exit 1
    log_step "Yii: running database migrations..."
    sudo -u "$APP_USER" "php${PHP_ACTIVE}" yii migrate --interactive=0
    log_ok "Database migrations selesai"
fi

# Backup cron — sync password dulu
if [[ -f "$BACKUP_CONF" && -f "$APP_DIR/common/config/main-local.php" ]]; then
    DB_PASS_ENV=$(php -r '$config = require "'$APP_DIR'/common/config/main-local.php"; echo $config["components"]["db"]["password"] ?? "";')
    DB_PASS_CONF=$(grep "^BACKUP_DB_PASS=" "$BACKUP_CONF" | cut -d= -f2 | tr -d '"')
    if [[ -n "$DB_PASS_ENV" && "$DB_PASS_ENV" != "$DB_PASS_CONF" ]]; then
        sed -i "s|^BACKUP_DB_PASS=.*|BACKUP_DB_PASS=\"${DB_PASS_ENV}\"|" "$BACKUP_CONF"
        log_info "backup.conf: BACKUP_DB_PASS diperbarui"
        BCRON_STATUS="missing"; BCRON_DETAIL="password diperbarui, cron perlu dipasang"
    fi
fi

if ask_step "Backup cron harian (02:00)" "$BCRON_STATUS" "$BCRON_DETAIL"; then
    cat > "/etc/cron.d/backup-siperisai" <<CRON
# Backup otomatis siperisai setiap hari jam 02:00
0 2 * * * root bash ${SCRIPT_DIR}/backup/backup.sh ${BACKUP_CONF} >> /var/log/backup-siperisai.log 2>&1
CRON
    chmod 644 "/etc/cron.d/backup-siperisai"
    log_ok "Backup cron dipasang: /etc/cron.d/backup-siperisai"
fi

# SSL
if ask_step "SSL certificate ($DOMAIN)" "$SSL_STATUS" "$SSL_DETAIL"; then
    bash "$SCRIPT_DIR/site/ssl.sh" "$SITE_CONF"
fi

# Firewall
if ask_step "Firewall UFW" "$UFW_STATUS" "$UFW_DETAIL"; then
    bash "$SCRIPT_DIR/install/firewall.sh"
fi

# ============================================================
# SUMMARY
# ============================================================
log_banner "Setup Selesai"

sleep 1

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "https://$DOMAIN" 2>/dev/null || echo "000")
HTTP_WWW=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "https://www.$DOMAIN" 2>/dev/null || echo "000")

echo ""
echo "  https://$DOMAIN       → HTTP $HTTP_CODE"
echo "  https://www.$DOMAIN   → HTTP $HTTP_WWW"
echo ""

if [[ "$HTTP_CODE" =~ ^(200|301|302)$ ]]; then
    log_ok "Website BISA DIAKSES — https://$DOMAIN"
else
    log_warn "Website belum bisa diakses (HTTP $HTTP_CODE)"
    echo "    tail -f /var/log/nginx/${DOMAIN}.error.log"
fi

echo ""
echo "  Services:"
for svc in nginx "php${PHP_ACTIVE}-fpm" postgresql redis-server; do
    if systemctl is-active --quiet "$svc" 2>/dev/null; then
        echo -e "  ${GREEN}✓${NC} $svc"
    else
        echo -e "  ${RED}✗${NC} $svc"
    fi
done

echo ""
echo "  Deploy update   : sudo bash $SCRIPT_DIR/app/deploy.sh $SITE_CONF"
echo "  Test backup     : sudo bash $SCRIPT_DIR/backup/backup.sh"
echo "  Renew SSL       : sudo bash $SCRIPT_DIR/site/ssl.sh $SITE_CONF --renew"
echo "  DB credentials  : sudo cat /root/${DOMAIN}_credentials.txt"
echo ""
