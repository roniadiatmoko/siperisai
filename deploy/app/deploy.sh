#!/bin/bash
# Deploy / update aplikasi Yii2 Advanced
# Usage: sudo bash app/deploy.sh sites/siperisai.my.id.conf [options]
# Options:
#   --skip-pull        skip git pull
#   --skip-composer    skip composer install
#   --skip-migrate     skip database migrations

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
source "$SCRIPT_DIR/../lib/common.sh"

require_root

CONF_FILE=""
SKIP_PULL=false
SKIP_COMPOSER=false
SKIP_MIGRATE=false

for arg in "$@"; do
    case "$arg" in
        --skip-pull)        SKIP_PULL=true ;;
        --skip-composer)    SKIP_COMPOSER=true ;;
        --skip-migrate)     SKIP_MIGRATE=true ;;
        *)                  CONF_FILE="$arg" ;;
    esac
done

[[ -n "$CONF_FILE" ]] || die "Usage: sudo bash app/deploy.sh sites/DOMAIN.conf [--skip-pull] [--skip-composer] [--skip-migrate]"
load_site_config "$CONF_FILE"

log_banner "Deploy: $SITE_DOMAIN"
START_TIME=$(date +%s)

cd "$SITE_ROOT"

# --- Pull latest code ---
if [[ "$SKIP_PULL" != "true" ]] && [[ -d ".git" ]]; then
    log_step "Git pull..."
    sudo -u "$SITE_USER" git pull origin "$(git rev-parse --abbrev-ref HEAD)"
    log_info "Commit: $(git log -1 --oneline)"
fi

# --- Composer install ---
if [[ "$SKIP_COMPOSER" != "true" ]]; then
    log_step "Composer install..."
    sudo -u "$SITE_USER" composer install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --prefer-dist \
        --ignore-platform-reqs
fi

# --- Database migrate ---
if [[ "$SKIP_MIGRATE" != "true" ]]; then
    log_step "Running migrations..."
    sudo -u "$SITE_USER" "php${SITE_PHP_VERSION}" yii migrate --interactive=0
fi

# --- Fix permissions ---
log_step "Fixing assets & runtime permissions..."
chown -R "${SITE_USER}:www-data" "$SITE_ROOT"
find "$SITE_ROOT" -type d -exec chmod 755 {} +
find "$SITE_ROOT" -type f -exec chmod 644 {} +
chmod +x "$SITE_ROOT/yii" 2>/dev/null || true
find "$SITE_ROOT/deploy" -name "*.sh" -exec chmod +x {} +

for dir in backend/runtime backend/web/assets frontend/runtime frontend/web/assets console/runtime frontend/web/public/uploads; do
    mkdir -p "$dir"
    chown -R "${SITE_USER}:www-data" "$dir"
    find "$dir" -type d -exec chmod 777 {} +
    find "$dir" -type f ! -name ".gitignore" -exec chmod 777 {} + 2>/dev/null || true
done

# --- Create frontend public symlink in backend ---
if [[ ! -L "$SITE_ROOT/backend/web/public" ]] && [[ ! -d "$SITE_ROOT/backend/web/public" ]]; then
    ln -s ../../frontend/web/public "$SITE_ROOT/backend/web/public"
    chown -h "${SITE_USER}:www-data" "$SITE_ROOT/backend/web/public"
    log_ok "Symlink dibuat: backend/web/public -> ../../frontend/web/public"
fi

# --- Reload PHP-FPM to clear OPCache ---
log_step "Reloading PHP-FPM..."
systemctl reload "php${SITE_PHP_VERSION}-fpm" 2>/dev/null || service "php${SITE_PHP_VERSION}-fpm" reload 2>/dev/null || true

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))
log_ok "Deploy selesai dalam ${DURATION}s"
log_info "Commit  : $(git log -1 --oneline 2>/dev/null || echo 'N/A')"
log_info "URL     : https://${SITE_DOMAIN}"
