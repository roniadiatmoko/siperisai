#!/bin/bash
# Configure UFW firewall
# Usage: sudo bash install/firewall.sh [extra_port1] [extra_port2] ...
# Example: sudo bash install/firewall.sh 3000 8080

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/../lib/common.sh"

require_root

EXTRA_PORTS=("$@")

log_banner "UFW Firewall Setup"

apt-get install -y -qq ufw

# Konfigurasi firewall policies (tanpa reset)
log_info "Configuring default policies without resetting existing rules..."
ufw default deny incoming 2>/dev/null || true
ufw default allow outgoing 2>/dev/null || true

# Wajib
ufw allow 22/tcp    comment "SSH"
ufw allow 80/tcp    comment "HTTP"
ufw allow 443/tcp   comment "HTTPS"
ufw allow 3389/tcp  comment "RDP"

# Port tambahan (opsional)
for port in "${EXTRA_PORTS[@]}"; do
    ufw allow "${port}/tcp" comment "Custom"
    log_info "Added port: $port"
done

# Rate limiting SSH (brute force protection)
ufw delete allow 22/tcp &>/dev/null
ufw limit 22/tcp comment "SSH (rate limited)"

ufw --force enable

log_ok "Firewall aktif:"
ufw status verbose
