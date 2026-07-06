#!/bin/bash
# Shared utilities for deploy scripts

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

log_info()    { echo -e "${BLUE}[INFO]${NC}  $*"; }
log_ok()      { echo -e "${GREEN}[OK]${NC}    $*"; }
log_warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }
log_error()   { echo -e "${RED}[ERROR]${NC} $*" >&2; }
log_step()    { echo -e "\n${BOLD}${CYAN}==> $*${NC}"; }
log_banner()  {
    local msg="$*"
    local len=${#msg}
    local line
    line=$(printf '%*s' "$((len+4))" '' | tr ' ' '=')
    echo -e "\n${BOLD}${CYAN}${line}${NC}"
    echo -e "${BOLD}${CYAN}  ${msg}  ${NC}"
    echo -e "${BOLD}${CYAN}${line}${NC}\n"
}

die() {
    log_error "$*"
    exit 1
}

require_root() {
    [[ $EUID -eq 0 ]] || die "Script harus dijalankan sebagai root: sudo bash $0"
}

require_arg() {
    [[ -n "$1" ]] || die "Argument diperlukan: $2"
}

check_cmd() {
    command -v "$1" &>/dev/null
}

require_cmd() {
    check_cmd "$1" || die "Command tidak ditemukan: $1. Install dulu."
}

confirm() {
    local prompt="${1:-Lanjutkan?} [y/N] "
    read -rp "$prompt" ans
    [[ "${ans,,}" == "y" || "${ans,,}" == "yes" ]]
}

# ask_step — tampilkan status komponen dan tanya aksi
# Usage: ask_step "Label" "found|missing" "detail"
# Return: 0 = jalankan, 1 = skip
ask_step() {
    local label="$1"
    local status="$2"
    local detail="${3:-}"
    echo ""
    if [[ "$status" == "found" ]]; then
        echo -e "  ${GREEN}✓${NC} ${BOLD}${label}${NC} — ${detail}"
        printf "    Sudah ada. [S]kip / [r]eplace ulang? "
        read -r ans
        [[ "${ans,,}" == "r" ]] && return 0 || return 1
    else
        echo -e "  ${YELLOW}○${NC} ${BOLD}${label}${NC} — belum ada / belum terkonfigurasi"
        printf "    [Y]a, jalankan / [s]kip? "
        read -r ans
        [[ "${ans,,}" == "s" ]] && return 1 || return 0
    fi
}

load_site_config() {
    local conf="$1"
    [[ -f "$conf" ]] || die "Config file tidak ditemukan: $conf"
    # shellcheck disable=SC1090
    source "$conf"

    # Defaults
    SITE_PHP_VERSION="${SITE_PHP_VERSION:-8.3}"
    SITE_NODE_VERSION="${SITE_NODE_VERSION:-22}"
    SITE_USER="${SITE_USER:-$(stat -c '%U' "$SITE_ROOT" 2>/dev/null || echo 'www-data')}"
    DB_HOST="${DB_HOST:-127.0.0.1}"
    DB_PORT="${DB_PORT:-5432}"
    SSL_EMAIL="${SSL_EMAIL:-admin@${SITE_DOMAIN}}"
    SITE_WWW_REDIRECT="${SITE_WWW_REDIRECT:-true}"

    [[ -n "$SITE_DOMAIN" ]] || die "SITE_DOMAIN harus diset di config"
    [[ -n "$SITE_ROOT"   ]] || die "SITE_ROOT harus diset di config"
}

gen_password() {
    openssl rand -base64 32 | tr -dc 'A-Za-z0-9' | head -c "${1:-32}"
}

nvm_exec() {
    local user="$1"; shift
    local nvmrc="${NVM_DIR:-/home/${user}/.nvm}"
    sudo -u "$user" bash -c "export NVM_DIR='${nvmrc}'; source '${nvmrc}/nvm.sh' && $*"
}
