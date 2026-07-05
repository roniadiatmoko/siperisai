#!/bin/bash
# Buat Nginx virtual host baru dari site config
# Usage: sudo bash site/create.sh sites/haloatlet.id.conf
# Options:
#   --no-ssl   Jangan issue SSL (berguna jika DNS belum pointing)
#   --ssl-only Hanya issue SSL, skip nginx config

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/../lib/common.sh"

require_root

CONF_FILE=""
NO_SSL=false
SSL_ONLY=false

for arg in "$@"; do
    case "$arg" in
        --no-ssl)   NO_SSL=true ;;
        --ssl-only) SSL_ONLY=true ;;
        *)          CONF_FILE="$arg" ;;
    esac
done

[[ -n "$CONF_FILE" ]] || die "Usage: sudo bash site/create.sh sites/DOMAIN.conf [--no-ssl|--ssl-only]"

load_site_config "$CONF_FILE"

log_banner "Create Site: $SITE_DOMAIN"

NGINX_AVAILABLE="/etc/nginx/sites-available/${SITE_DOMAIN}"
NGINX_ENABLED="/etc/nginx/sites-enabled/${SITE_DOMAIN}"
TEMPLATE="$SCRIPT_DIR/templates/nginx-yii.conf"

# --- Generate www redirect block ---
if [[ "$SITE_WWW_REDIRECT" == "true" ]]; then
    WWW_BLOCK="# Redirect www ke non-www
server {
    listen 80;
    listen [::]:80;
    server_name www.${SITE_DOMAIN};
    return 301 \$scheme://${SITE_DOMAIN}\$request_uri;
}"
else
    WWW_BLOCK=""
fi

if [[ "$SSL_ONLY" != "true" ]]; then
    log_step "Membuat Nginx config untuk ${SITE_DOMAIN}..."

    # Cek PHP-FPM socket — auto-detect versi jika socket tidak ada
    PHP_SOCK="/var/run/php/php${SITE_PHP_VERSION}-fpm.sock"
    if [[ ! -S "$PHP_SOCK" ]]; then
        log_warn "PHP-FPM socket tidak ditemukan: $PHP_SOCK"
        DETECTED_SOCK=$(find /var/run/php -name 'php*-fpm.sock' -type s 2>/dev/null | sort -V | tail -1)
        if [[ -S "$DETECTED_SOCK" ]]; then
            SITE_PHP_VERSION=$(basename "$DETECTED_SOCK" | grep -oP '\d+\.\d+')
            PHP_SOCK="$DETECTED_SOCK"
            log_warn "Auto-detect: pakai PHP ${SITE_PHP_VERSION} (${PHP_SOCK})"
        else
            log_warn "Tidak ada PHP-FPM socket ditemukan. Pastikan PHP sudah terinstall."
        fi
    fi

    # Cek root directory
    [[ -d "$SITE_ROOT" ]] || log_warn "Root directory belum ada: $SITE_ROOT"

    # Generate nginx config dari template
    # {{WWW_BLOCK}} ditulis ke file terpisah lalu digabung (sed tidak support multiline replace)
    MAIN_CONFIG=$(sed \
        -e "s|{{DOMAIN}}|${SITE_DOMAIN}|g" \
        -e "s|{{ROOT}}|${SITE_ROOT}|g" \
        -e "s|{{PHP_VERSION}}|${SITE_PHP_VERSION}|g" \
        -e "/{{WWW_BLOCK}}/d" \
        "$TEMPLATE")

    {
        [[ -n "$WWW_BLOCK" ]] && printf '%s\n\n' "$WWW_BLOCK"
        echo "$MAIN_CONFIG"
    } > "$NGINX_AVAILABLE"
    ln -sf "$NGINX_AVAILABLE" "$NGINX_ENABLED"

    # Pastikan www-data bisa traverse home directory owner site
    SITE_USER_HOME=$(getent passwd "$SITE_USER" | cut -d: -f6 2>/dev/null)
    if [[ -d "$SITE_USER_HOME" ]]; then
        chmod o+x "$SITE_USER_HOME"
        log_info "Home $SITE_USER_HOME: execute permission untuk www-data diset"
    fi

    nginx -t || die "Nginx config tidak valid!"
    systemctl reload nginx
    log_ok "Nginx config dibuat: $NGINX_AVAILABLE"
fi

# --- SSL ---
if [[ "$NO_SSL" != "true" ]]; then
    log_step "Mengambil SSL certificate untuk ${SITE_DOMAIN}..."
    bash "$SCRIPT_DIR/ssl.sh" "$CONF_FILE"
fi

log_ok "Site ${SITE_DOMAIN} berhasil dibuat!"
log_info "Nginx config : $NGINX_AVAILABLE"
log_info "Access log   : /var/log/nginx/${SITE_DOMAIN}.access.log"
log_info "Error log    : /var/log/nginx/${SITE_DOMAIN}.error.log"
