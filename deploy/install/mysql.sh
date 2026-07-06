#!/bin/bash
# Install MySQL/MariaDB Server
# Usage: sudo bash install/mysql.sh

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/../lib/common.sh"

require_root

log_banner "MySQL/MariaDB Installer"

log_step "Installing MariaDB Server..."
apt-get update -qq
apt-get install -y mariadb-server mariadb-client

systemctl enable mariadb
systemctl start mariadb

log_ok "MariaDB installed."
