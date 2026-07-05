#!/bin/bash
# Install base system packages
# Usage: sudo bash install/system.sh

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/../lib/common.sh"

require_root

log_banner "System Base Setup"

log_step "Update & upgrade system..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get upgrade -y -qq

log_step "Install base packages..."
apt-get install -y -qq \
    curl wget git unzip zip \
    software-properties-common apt-transport-https \
    ca-certificates gnupg2 lsb-release \
    build-essential \
    ufw fail2ban \
    cron supervisor \
    htop ncdu tree jq \
    openssl \
    acl

log_step "Enable & start base services..."
systemctl enable cron supervisor
systemctl start cron supervisor 2>/dev/null || true

log_ok "System base packages installed."
