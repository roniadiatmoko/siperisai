#!/bin/bash
# Install Redis
# Usage: sudo bash install/redis.sh [password]
# Password kosong = tanpa auth (hanya localhost)

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/../lib/common.sh"

require_root

REDIS_PASS="${1:-}"

log_banner "Redis Installer"

apt-get install -y redis-server

# Konfigurasi Redis
REDIS_CONF="/etc/redis/redis.conf"

if systemctl is-active --quiet redis-server 2>/dev/null && [[ -f "$REDIS_CONF" ]]; then
    log_info "Redis is already running. Skipping configuration overwrite of $REDIS_CONF to avoid disrupting other apps."
    # Define MAX_MEM for logging at the end
    TOTAL_MEM_MB=$(free -m | awk '/^Mem:/{print $2}')
    MAX_MEM=$((TOTAL_MEM_MB / 4))
else
    if [[ -f "$REDIS_CONF" ]]; then
        cp "$REDIS_CONF" "${REDIS_CONF}.bak"

        # Bind hanya localhost
        sed -i 's/^bind .*/bind 127.0.0.1/' "$REDIS_CONF"

        # Disable protected-mode jika sudah bind localhost
        sed -i 's/^protected-mode yes/protected-mode no/' "$REDIS_CONF"

        # Max memory policy
        grep -q "maxmemory-policy" "$REDIS_CONF" \
            && sed -i 's/^#\?maxmemory-policy.*/maxmemory-policy allkeys-lru/' "$REDIS_CONF" \
            || echo "maxmemory-policy allkeys-lru" >> "$REDIS_CONF"

        # Set max memory (25% RAM)
        TOTAL_MEM_MB=$(free -m | awk '/^Mem:/{print $2}')
        MAX_MEM=$((TOTAL_MEM_MB / 4))
        grep -q "^maxmemory " "$REDIS_CONF" \
            && sed -i "s/^maxmemory .*/maxmemory ${MAX_MEM}mb/" "$REDIS_CONF" \
            || echo "maxmemory ${MAX_MEM}mb" >> "$REDIS_CONF"

        # Password (opsional)
        if [[ -n "$REDIS_PASS" ]]; then
            sed -i "s/^# requirepass .*/requirepass $REDIS_PASS/" "$REDIS_CONF"
            sed -i "s/^requirepass .*/requirepass $REDIS_PASS/" "$REDIS_CONF"
            log_info "Redis password set."
        else
            sed -i "s/^requirepass .*/#requirepass foobared/" "$REDIS_CONF"
        fi
    fi

    systemctl enable redis-server
    systemctl restart redis-server
fi

# Test koneksi
if [[ -n "$REDIS_PASS" ]]; then
    redis-cli -a "$REDIS_PASS" ping | grep -q "PONG" && log_ok "Redis OK (with auth)" || log_error "Redis gagal"
else
    redis-cli ping | grep -q "PONG" && log_ok "Redis OK (no auth)" || log_error "Redis gagal"
fi

log_ok "Redis installed: $(redis-server --version | head -1)"
log_info "Max memory: ${MAX_MEM}MB | Bind: 127.0.0.1"
