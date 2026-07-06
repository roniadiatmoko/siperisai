#!/bin/bash
# Hapus Nginx site (dan opsional revoke SSL)
# Usage: sudo bash site/remove.sh sites/haloatlet.id.conf [--with-ssl]

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/../lib/common.sh"

require_root

CONF_FILE="$1"
WITH_SSL="${2:-}"

[[ -n "$CONF_FILE" ]] || die "Usage: sudo bash site/remove.sh sites/DOMAIN.conf [--with-ssl]"
load_site_config "$CONF_FILE"

log_banner "Remove Site: $SITE_DOMAIN"

confirm "Hapus site ${SITE_DOMAIN}?" || exit 0

if [[ "$WITH_SSL" == "--with-ssl" ]]; then
    log_step "Revoking SSL certificate..."
    certbot delete --cert-name "$SITE_DOMAIN" --non-interactive 2>/dev/null || log_warn "SSL tidak ditemukan/gagal revoke"
fi

log_step "Menghapus Nginx config..."
rm -f "/etc/nginx/sites-enabled/${SITE_DOMAIN}"
rm -f "/etc/nginx/sites-available/${SITE_DOMAIN}"

nginx -t && systemctl reload nginx
log_ok "Site ${SITE_DOMAIN} dihapus."
