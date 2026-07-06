#!/bin/bash
# SSL Certificate Management (Let's Encrypt via Certbot)
# Usage:
#   sudo bash site/ssl.sh sites/haloatlet.id.conf          # Issue certificate
#   sudo bash site/ssl.sh sites/haloatlet.id.conf --renew  # Force renew
#   sudo bash site/ssl.sh --renew-all                      # Renew semua cert

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/../lib/common.sh"

require_root

# Install certbot jika belum ada
if ! check_cmd certbot; then
    log_step "Installing Certbot..."
    apt-get install -y certbot python3-certbot-nginx
fi

# Renew semua
if [[ "$1" == "--renew-all" ]]; then
    log_banner "Renew All SSL Certificates"
    certbot renew --quiet --nginx
    systemctl reload nginx
    log_ok "Semua certificate diperbarui."
    exit 0
fi

CONF_FILE="$1"
ACTION="${2:-issue}"

[[ -n "$CONF_FILE" ]] || die "Usage: sudo bash site/ssl.sh sites/DOMAIN.conf [--renew]"
load_site_config "$CONF_FILE"

log_banner "SSL - ${SITE_DOMAIN}"

DOMAINS=("-d" "$SITE_DOMAIN" "-d" "admin.${SITE_DOMAIN}")
[[ "$SITE_WWW_REDIRECT" == "true" ]] && DOMAINS+=("-d" "www.${SITE_DOMAIN}")

if [[ "$ACTION" == "--renew" ]]; then
    log_step "Force renewing certificate untuk ${SITE_DOMAIN}..."
    certbot renew --cert-name "$SITE_DOMAIN" --force-renewal --nginx --quiet
    systemctl reload nginx
    log_ok "Certificate renewed: $SITE_DOMAIN"
else
    log_step "Issuing certificate untuk ${SITE_DOMAIN}..."

    # Cek apakah domain sudah resolve ke server ini
    SERVER_IP=$(curl -s ifconfig.me 2>/dev/null || curl -s ipinfo.io/ip 2>/dev/null)
    DOMAIN_IP=$(dig +short "$SITE_DOMAIN" 2>/dev/null | tail -1)

    if [[ -n "$SERVER_IP" && -n "$DOMAIN_IP" && "$SERVER_IP" != "$DOMAIN_IP" ]]; then
        log_warn "DNS mismatch! Server IP: ${SERVER_IP}, Domain DNS: ${DOMAIN_IP}"
        log_warn "SSL mungkin gagal jika DNS belum pointing ke server ini."
        # Jika non-interactive (pipe/script), otomatis lanjut. Jika TTY, tanya konfirmasi.
        if tty -s 2>/dev/null; then
            confirm "Tetap lanjutkan?" || exit 0
        else
            log_warn "Non-interactive mode: lanjutkan otomatis..."
        fi
    fi

    certbot --nginx \
        "${DOMAINS[@]}" \
        --non-interactive \
        --agree-tos \
        --email "$SSL_EMAIL" \
        --redirect \
        --keep-until-expiring

    # Setup auto-renewal cron (idempotent)
    if ! crontab -l 2>/dev/null | grep -q "certbot renew"; then
        (crontab -l 2>/dev/null; echo "0 3 * * * certbot renew --quiet --nginx && systemctl reload nginx") | crontab -
        log_ok "Auto-renewal cron ditambahkan (daily 03:00)"
    fi

    log_ok "SSL certificate berhasil untuk ${SITE_DOMAIN}"
    certbot certificates --cert-name "$SITE_DOMAIN" 2>/dev/null || true
fi
