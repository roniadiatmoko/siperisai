#!/bin/bash
# Install or update Composer globally
# Usage: sudo bash install/composer.sh

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/../lib/common.sh"

require_root

log_banner "Composer Installer"

# Deteksi PHP binary (support php, php8.5, php8.3, dst)
PHP_BIN=""
for bin in php php8.5 php8.4 php8.3 php8.2 php8.1; do
    if command -v "$bin" &>/dev/null; then
        PHP_BIN="$bin"
        break
    fi
done
[[ -n "$PHP_BIN" ]] || die "PHP tidak ditemukan. Install PHP dulu."
log_info "Menggunakan PHP: $PHP_BIN ($(${PHP_BIN} -v | head -1))"

# Buat symlink /usr/local/bin/php jika belum ada (agar composer bisa dipakai via 'php')
if [[ ! -x /usr/local/bin/php ]] && [[ "$PHP_BIN" != "php" ]]; then
    ln -sf "$(command -v "$PHP_BIN")" /usr/local/bin/php
    log_info "Symlink dibuat: /usr/local/bin/php -> $(command -v "$PHP_BIN")"
fi

EXPECTED_CHECKSUM="$("$PHP_BIN" -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
"$PHP_BIN" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$("$PHP_BIN" -r "echo hash_file('sha384', 'composer-setup.php');")"

if [[ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]]; then
    rm composer-setup.php
    die "Composer installer checksum tidak valid!"
fi

"$PHP_BIN" composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php

composer self-update --stable 2>/dev/null || true

log_ok "Composer installed: $(composer --version)"
