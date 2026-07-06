#!/bin/bash
# Install and configure Nginx
# Usage: sudo bash install/nginx.sh

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/../lib/common.sh"

require_root

log_banner "Nginx Installer"

apt-get install -y nginx

# Hardened nginx.conf
if [[ -f /etc/nginx/nginx.conf ]]; then
    log_info "Nginx configuration (/etc/nginx/nginx.conf) already exists. Keeping it to prevent breaking other sites."
    cp /etc/nginx/nginx.conf /etc/nginx/nginx.conf.backup-siperisai
else
    log_step "Writing hardened nginx.conf..."
    cat > /etc/nginx/nginx.conf <<'NGINXCONF'
user www-data;
worker_processes auto;
pid /run/nginx.pid;
include /etc/nginx/modules-enabled/*.conf;

events {
    worker_connections 1024;
    multi_accept on;
}

http {
    ##
    # Basic Settings
    ##
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    server_tokens off;
    client_max_body_size 64M;

    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    ##
    # Logging
    ##
    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log;

    ##
    # Gzip
    ##
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain text/css text/xml text/javascript
        application/json application/javascript application/xml+rss
        application/atom+xml image/svg+xml;

    ##
    # Security Headers (global defaults)
    ##
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    ##
    # Virtual Host Configs
    ##
    include /etc/nginx/conf.d/*.conf;
    include /etc/nginx/sites-enabled/*;
}
NGINXCONF
fi

# Disable default site
if [[ -L /etc/nginx/sites-enabled/default || -f /etc/nginx/sites-enabled/default ]]; then
    rm -f /etc/nginx/sites-enabled/default
    log_info "Disabled default Nginx site."
fi

systemctl enable nginx

if systemctl is-active --quiet nginx 2>/dev/null; then
    log_step "Reloading Nginx (zero-downtime)..."
    systemctl reload nginx
    log_ok "Nginx reloaded."
else
    log_step "Starting Nginx..."
    systemctl start nginx
    log_ok "Nginx started."
fi

nginx -t && log_ok "Nginx installed & configured. Version: $(nginx -v 2>&1)"
