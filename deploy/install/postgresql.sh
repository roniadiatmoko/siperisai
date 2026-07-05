#!/bin/bash
# Install PostgreSQL
# Usage: sudo bash install/postgresql.sh [version]
# Example: sudo bash install/postgresql.sh 16
# Default: latest from apt

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/../lib/common.sh"

require_root

PG_VER="${1:-}"

log_banner "PostgreSQL Installer"

if [[ -n "$PG_VER" ]]; then
    log_step "Adding PostgreSQL official APT repo..."
    curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor -o /usr/share/keyrings/postgresql.gpg
    echo "deb [signed-by=/usr/share/keyrings/postgresql.gpg] https://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" \
        > /etc/apt/sources.list.d/pgdg.list
    apt-get update -qq
    apt-get install -y "postgresql-${PG_VER}" "postgresql-client-${PG_VER}"
else
    log_step "Installing PostgreSQL (default)..."
    apt-get install -y postgresql postgresql-contrib
fi

systemctl enable postgresql
systemctl start postgresql

PG_VER_ACTUAL=$(psql --version | grep -oP '\d+' | head -1)
log_ok "PostgreSQL ${PG_VER_ACTUAL} installed."

# Tuning dasar
PG_CONF=$(find /etc/postgresql -name "postgresql.conf" | head -1)
if [[ -f "$PG_CONF" ]]; then
    log_step "Applying basic performance tuning..."
    # Detect RAM
    TOTAL_MEM_MB=$(free -m | awk '/^Mem:/{print $2}')
    SHARED_BUFFERS=$((TOTAL_MEM_MB / 4))
    EFFECTIVE_CACHE=$((TOTAL_MEM_MB * 3 / 4))

    sed -i "s/^#shared_buffers = .*/shared_buffers = ${SHARED_BUFFERS}MB/"       "$PG_CONF"
    sed -i "s/^#effective_cache_size = .*/effective_cache_size = ${EFFECTIVE_CACHE}MB/" "$PG_CONF"
    sed -i "s/^#work_mem = .*/work_mem = 16MB/"                                  "$PG_CONF"
    sed -i "s/^#maintenance_work_mem = .*/maintenance_work_mem = 256MB/"          "$PG_CONF"
    sed -i "s/^#max_connections = .*/max_connections = 100/"                      "$PG_CONF"
    sed -i "s/^#wal_buffers = .*/wal_buffers = 16MB/"                            "$PG_CONF"
    sed -i "s/^#timezone = .*/timezone = 'Asia\/Jakarta'/"                        "$PG_CONF"
    sed -i "s/^log_timezone = .*/log_timezone = 'Asia\/Jakarta'/"                 "$PG_CONF"

    systemctl restart postgresql
    log_ok "PostgreSQL tuned: shared_buffers=${SHARED_BUFFERS}MB, effective_cache=${EFFECTIVE_CACHE}MB"
fi
