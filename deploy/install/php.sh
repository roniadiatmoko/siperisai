#!/bin/bash
# Install one or more PHP versions with Laravel extensions
# Usage: sudo bash install/php.sh [version1] [version2] ...
# Example: sudo bash install/php.sh 8.2 8.3 8.4
# Default: 8.3

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/../lib/common.sh"

require_root

VERSIONS=("${@:-8.3}")

PHP_EXTENSIONS=(
    fpm cli
    pgsql mysql
    mbstring xml curl zip bcmath gd intl
    redis opcache
    tokenizer fileinfo dom
    soap xmlrpc
    imagick
    imap
)

log_banner "PHP Multi-Version Installer"
log_info "Versi yang akan diinstall: ${VERSIONS[*]}"
log_info "OS: $(lsb_release -ds)"

# Cek apakah versi tersedia di repo default (Ubuntu 26.04+ punya PHP 8.5)
SYSTEM_PHP_VER=$(apt-cache show php-cli 2>/dev/null | grep "^Version:" | head -1 | grep -oP '\d+\.\d+' | head -1)
log_info "PHP default di repo sistem: ${SYSTEM_PHP_VER:-tidak diketahui}"

# Add ondrej/php PPA jika versi yang diminta tidak ada di repo default
PPA_NEEDED=false
for VER in "${VERSIONS[@]}"; do
    if ! apt-cache show "php${VER}-cli" &>/dev/null; then
        PPA_NEEDED=true
        break
    fi
done

if [[ "$PPA_NEEDED" == "true" ]]; then
    log_step "Versi PHP diminta tidak ada di repo default, menambahkan PPA ondrej/php..."
    add-apt-repository -y ppa:ondrej/php 2>/tmp/ppa_err || true
    apt-get update -qq 2>/dev/null

    # Verifikasi apakah PPA benar-benar berfungsi (cek package tersedia)
    if apt-cache show "php${VERSIONS[0]}-cli" &>/dev/null; then
        log_ok "PPA ondrej/php aktif, PHP ${VERSIONS[0]} tersedia"
    else
        log_warn "PPA gagal/belum support Ubuntu $(lsb_release -cs) (404). Fallback ke PHP sistem: ${SYSTEM_PHP_VER}"
        # Hapus PPA yang gagal agar tidak mengganggu apt
        add-apt-repository -y --remove ppa:ondrej/php 2>/dev/null || true
        apt-get update -qq 2>/dev/null
        # Ganti semua versi yang tidak tersedia dengan versi sistem
        NEW_VERSIONS=()
        for VER in "${VERSIONS[@]}"; do
            if apt-cache show "php${VER}-cli" &>/dev/null; then
                NEW_VERSIONS+=("$VER")
            else
                log_warn "PHP ${VER} tidak tersedia → diganti ke ${SYSTEM_PHP_VER}"
                NEW_VERSIONS+=("$SYSTEM_PHP_VER")
            fi
        done
        # Deduplicate
        mapfile -t VERSIONS < <(printf '%s\n' "${NEW_VERSIONS[@]}" | sort -u)
        log_info "Versi final yang akan diinstall: ${VERSIONS[*]}"
    fi
else
    log_ok "Semua versi tersedia di repo default, skip PPA"
    apt-get update -qq
fi

# Install ImageMagick dependency (shared across versions)
apt-get install -y -qq libmagickwand-dev imagemagick 2>/dev/null || true

for VER in "${VERSIONS[@]}"; do
    log_step "Installing PHP ${VER}..."

    PKGS=()
    for ext in "${PHP_EXTENSIONS[@]}"; do
        PKGS+=("php${VER}-${ext}")
    done

    if ! apt-get install -y "${PKGS[@]}" 2>/dev/null; then
        log_warn "Beberapa extension tidak tersedia untuk PHP ${VER}, install yang tersedia..."
        for pkg in "${PKGS[@]}"; do
            apt-get install -y "$pkg" 2>/dev/null || log_warn "Skip: $pkg"
        done
    fi

    # Configure php.ini
    for sapi in fpm cli; do
        INI="/etc/php/${VER}/${sapi}/php.ini"
        [[ -f "$INI" ]] || continue
        sed -i "s/^upload_max_filesize = .*/upload_max_filesize = 64M/"    "$INI"
        sed -i "s/^post_max_size = .*/post_max_size = 64M/"                "$INI"
        sed -i "s/^memory_limit = .*/memory_limit = 256M/"                 "$INI"
        sed -i "s/^max_execution_time = .*/max_execution_time = 60/"       "$INI"
        sed -i "s/^;date.timezone.*/date.timezone = Asia\/Jakarta/"        "$INI"
        # OPcache settings for production
        sed -i "s/^;opcache.enable=.*/opcache.enable=1/"                   "$INI"
        sed -i "s/^;opcache.memory_consumption=.*/opcache.memory_consumption=256/" "$INI"
        sed -i "s/^;opcache.max_accelerated_files=.*/opcache.max_accelerated_files=20000/" "$INI"
        sed -i "s/^;opcache.validate_timestamps=.*/opcache.validate_timestamps=0/" "$INI"
    done

    # PHP-FPM pool config
    POOL="/etc/php/${VER}/fpm/pool.d/www.conf"
    if [[ -f "$POOL" ]]; then
        sed -i "s/^pm = .*/pm = dynamic/"                        "$POOL"
        sed -i "s/^pm.max_children = .*/pm.max_children = 20/"  "$POOL"
        sed -i "s/^pm.start_servers = .*/pm.start_servers = 4/" "$POOL"
        sed -i "s/^pm.min_spare_servers = .*/pm.min_spare_servers = 2/" "$POOL"
        sed -i "s/^pm.max_spare_servers = .*/pm.max_spare_servers = 8/" "$POOL"
    fi

    systemctl enable "php${VER}-fpm"
    systemctl restart "php${VER}-fpm"

    PHP_BIN="php${VER}"
    log_ok "PHP ${VER} installed: $("$PHP_BIN" --version | head -1)"
done

log_step "Installed PHP versions:"
for VER in "${VERSIONS[@]}"; do
    PHP_BIN="php${VER}"
    echo "  php${VER}: $("$PHP_BIN" --version 2>/dev/null | head -1 || echo 'ERROR')"
    echo "         socket: /var/run/php/php${VER}-fpm.sock"
done
