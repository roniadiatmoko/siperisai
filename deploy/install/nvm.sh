#!/bin/bash
# Install NVM (Node Version Manager) for a user - supports multiple Node versions
# Usage: sudo bash install/nvm.sh [username] [node_version1] [node_version2] ...
# Example: sudo bash install/nvm.sh wanforge 22 20 18
# Default user: current sudo user or 'www-data'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/../lib/common.sh"

require_root

TARGET_USER="${1:-${SUDO_USER:-wanforge}}"
shift
NODE_VERSIONS=("${@:-22}")
NVM_VERSION="v0.40.3"

TARGET_HOME=$(getent passwd "$TARGET_USER" | cut -d: -f6)
[[ -d "$TARGET_HOME" ]] || die "Home directory tidak ditemukan untuk user: $TARGET_USER"

NVM_DIR="$TARGET_HOME/.nvm"

log_banner "NVM Installer - User: $TARGET_USER"
log_info "Node versions: ${NODE_VERSIONS[*]}"

log_step "Installing NVM ${NVM_VERSION} untuk user ${TARGET_USER}..."

# Install NVM sebagai target user
sudo -u "$TARGET_USER" bash <<EOF
export NVM_DIR="$NVM_DIR"
curl -o- "https://raw.githubusercontent.com/nvm-sh/nvm/${NVM_VERSION}/install.sh" | bash
EOF

# Setup NVM di .bashrc dan .bash_profile (idempotent)
NVM_SNIPPET='
# NVM
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
[ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"
'

for RC in "$TARGET_HOME/.bashrc" "$TARGET_HOME/.bash_profile" "$TARGET_HOME/.profile"; do
    if [[ -f "$RC" ]] && ! grep -q "NVM_DIR" "$RC"; then
        echo "$NVM_SNIPPET" >> "$RC"
    fi
done

# Install Node versions
for NODE_VER in "${NODE_VERSIONS[@]}"; do
    log_step "Installing Node.js ${NODE_VER}..."
    sudo -u "$TARGET_USER" bash -c \
        "export NVM_DIR='$NVM_DIR'; source '$NVM_DIR/nvm.sh' && nvm install ${NODE_VER} && nvm alias ${NODE_VER} ${NODE_VER}"
    log_ok "Node ${NODE_VER} installed"
done

# Set default to first version
DEFAULT_VER="${NODE_VERSIONS[0]}"
sudo -u "$TARGET_USER" bash -c \
    "export NVM_DIR='$NVM_DIR'; source '$NVM_DIR/nvm.sh' && nvm alias default ${DEFAULT_VER}"

log_step "Installed Node versions:"
sudo -u "$TARGET_USER" bash -c \
    "export NVM_DIR='$NVM_DIR'; source '$NVM_DIR/nvm.sh' && nvm ls"

log_ok "NVM setup selesai untuk user $TARGET_USER. Default: Node ${DEFAULT_VER}"
log_info "Untuk menggunakan Node di project, tambahkan file .nvmrc:"
log_info "  echo '${DEFAULT_VER}' > /path/to/project/.nvmrc"
