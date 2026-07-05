# Production Deploy Toolkit — Si-PERISAI (Yii2 Advanced + MySQL)

Toolkit ini digunakan untuk melakukan setup server, SSL, database, backup, serta deployment otomatis (zero-downtime) untuk aplikasi **Yii2 Advanced** berbasis database **MySQL/MariaDB** di server Linux Ubuntu.

---

## Daftar Isi

- [Struktur Folder](#struktur-folder)
- [Alur Kerja](#alur-kerja)
  - [Setup Server (Interactive)](#setup-server-interactive)
  - [Deploy Update (Zero-Downtime)](#deploy-update-zero-downtime)
  - [Backup Harian (Cron)](#backup-harian-cron)
- [Setup Server Baru](#setup-server-baru)
- [Script Referensi](#script-referensi)
  - [setup-server.sh](#setup-serversh)
  - [install/system.sh](#installsystemsh)
  - [install/php.sh](#installphpsh)
  - [install/nginx.sh](#installnginxsh)
  - [install/mysql.sh](#installmysqlsh)
  - [install/redis.sh](#installredissh)
  - [install/composer.sh](#installcomposersh)
  - [install/firewall.sh](#installfirewallsh)
  - [site/create.sh](#sitecreatesh)
  - [site/ssl.sh](#sitesslsh)
  - [site/remove.sh](#siteremovesh)
  - [app/first-setup.sh](#appfirst-setupsh)
  - [app/deploy.sh](#appdeploysh)
  - [backup/import-backup.sh](#backupimport-backupsh)
  - [backup/backup.sh](#backupbackupsh)
  - [backup/restore.sh](#backuprestoresh)
- [Site Config](#site-config)
- [Backup Config](#backup-config)
- [Cron & Backup Automation](#cron--backup-automation)
- [Perintah Harian (Daily Commands)](#perintah-harian-daily-commands)
- [Troubleshooting](#troubleshooting)

---

## Struktur Folder

```text
deploy/
├── README.md                    ← Panduan lengkap deployment (berkas ini)
├── setup-server.sh              ← Satu-satunya script setup utama (interaktif)
│
├── lib/
│   └── common.sh                ← Utility & logger helpers (jangan dijalankan langsung)
│
├── install/                     ← Script instalasi software dasar
│   ├── system.sh                ← Base packages (git, curl, ufw, fail2ban, dll)
│   ├── php.sh                   ← PHP multi-version (FPM, CLI, mysql, gd, mbstring, dll)
│   ├── nginx.sh                 ← Nginx + hardened configuration
│   ├── mysql.sh                 ← MariaDB / MySQL Server + Client
│   ├── redis.sh                 ← Redis server (localhost bind, max memory)
│   ├── nvm.sh                   ← Node Version Manager (optional)
│   ├── composer.sh              ← Composer global installer
│   └── firewall.sh              ← UFW (SSH rate-limit, HTTP, HTTPS)
│
├── site/
│   ├── create.sh                ← Buat virtual host Nginx (frontend & backend subdomains)
│   ├── ssl.sh                   ← Request/renew SSL via Certbot (Let's Encrypt)
│   ├── remove.sh                ← Hapus virtual host & revoke SSL
│   └── templates/
│       └── nginx-yii.conf       ← Template Nginx untuk Yii2 Advanced
│
├── app/
│   ├── first-setup.sh           ← Inisialisasi awal aplikasi (php init, DB config, composer, migration)
│   └── deploy.sh                ← Deploy update aplikasi (git pull, composer, migration, reload FPM)
│
├── backup/
│   ├── backup.conf.example      ← Template konfigurasi backup
│   ├── backup.conf              ← Konfigurasi backup aktif (credentials & S3/FTP target)
│   ├── backup.sh                ← Menjalankan dump DB + backup files
│   ├── restore.sh               ← Restore DB dari file backup (.sql.gz)
│   └── import-backup.sh         ← Import file .sql ke database MySQL
│
└── sites/
    └── siperisai.my.id.conf     ← Berkas konfigurasi utama untuk domain siperisai.my.id
```

> **Catatan:** `deploy/backup/backup.conf` sudah ditambahkan ke `.gitignore` agar password database dan token API rahasia Anda tidak ter-commit ke git.

---

## Alur Kerja

### Setup Server (Interactive)
Script `setup-server.sh` dirancang berjalan secara interaktif:
```text
1. Deteksi system packages, PHP, Composer, Nginx, MySQL, Redis.
2. Tanya versi PHP yang diinginkan (default: 8.3).
3. Untuk setiap software yang missing/found:
   - Tawarkan [S]kip atau [r]eplace/install.
4. Bagian 2 (Konfigurasi Aplikasi):
   - Pasang Nginx vhost (menggunakan template Nginx-Yii).
   - Buat database MySQL siperisai + generate user & password aman.
   - Composer install & inisialisasi Yii2 Production environment (php init).
   - Menulis konfigurasi database otomatis ke common/config/main-local.php.
   - Menjalankan Yii database migrations (php yii migrate).
   - Memasang Cron backup harian otomatis.
   - Request SSL Let's Encrypt (dengan HTTP -> HTTPS redirect otomatis).
   - Mengaktifkan Firewall UFW.
```

### Deploy Update (Zero-Downtime)
Proses deployment harian menggunakan `deploy.sh` berlangsung cepat dan meminimalkan downtime:
```text
1. Git pull branch aktif secara otomatis.
2. Composer install --no-dev (optimasi autoloader).
3. Database migrations (php yii migrate) tanpa interaksi.
4. Set permission folder writable (backend/runtime, frontend/runtime, web/assets).
5. Reload service PHP-FPM untuk mereset OPCache di memory cache PHP.
```

### Backup Harian (Cron)
Mekanisme backup diatur otomatis setiap hari pada pukul 02:00:
```text
1. mysqldump database -> .sql.gz
2. tar gzip berkas uploads -> .tar.gz (menyimpan file gambar & dokumen terunggah)
3. Upload backup ke Local Storage (/var/backups) dan/atau S3 / FTP / SFTP.
4. Hapus berkas backup lama (retensi 30 hari).
5. Kirim notifikasi status (sukses/gagal) ke Telegram Bot.
```

---

## Setup Server Baru

Untuk mengkonfigurasi server kosong (fresh VPS) hingga aplikasi berjalan penuh, Anda cukup login ke server melalui SSH, clone repositori ini, dan jalankan satu perintah berikut:

```bash
sudo bash deploy/setup-server.sh
```

---

## Script Referensi

### `setup-server.sh`
Script interaktif utama untuk instalasi seluruh software dan konfigurasi awal.
* **Penggunaan**: `sudo bash deploy/setup-server.sh`
* **Cara Kerja**: Mendeteksi kelengkapan dependency, meminta konfirmasi user, lalu mengeksekusi script di folder `install/`, `site/`, dan `app/`.

### `install/system.sh`
Menginstall paket-paket esensial sistem operasi Linux.
* **Daftar Paket**: `curl`, `wget`, `git`, `unzip`, `zip`, `ufw`, `fail2ban`, `cron`, `htop`, `jq`.
* **Fail2ban**: Otomatis aktif untuk memblokir IP yang mencoba brute force SSH port 22.

### `install/php.sh`
Menginstall PHP beserta extension-extension yang dibutuhkan oleh framework Yii2.
* **Penggunaan**: `sudo bash deploy/install/php.sh [versi_php]` (Contoh: `8.3` atau `8.4`).
* **Extension Terpasang**: `fpm`, `cli`, `mysql`, `mbstring`, `xml`, `curl`, `zip`, `gd`, `intl`, `redis`, `opcache`.
* **Optimasi php.ini**:
  * `upload_max_filesize = 64M` & `post_max_size = 64M` (mendukung upload berkas besar).
  * `memory_limit = 256M`.
  * `date.timezone = Asia/Jakarta`.
  * OPCache diaktifkan penuh untuk kecepatan render engine PHP.

### `install/nginx.sh`
Menginstall web server Nginx dengan best-practices keamanan.
* **Keamanan**: Menonaktifkan informasi versi server (`server_tokens off`) dan menyuntikkan security headers (`X-Frame-Options`, `X-Content-Type-Options`).

### `install/mysql.sh`
Menginstall database server MariaDB (MySQL-compatible) secara lokal.
* **Proses**: Mengaktifkan service, menyetel autostart di systemd, dan mengaktifkan unix socket authentication untuk user `root`.

### `install/redis.sh`
Menginstall memory cache Redis untuk session caching dan query cache Yii2.
* **Keamanan**: Membatasi listening port hanya di `127.0.0.1` (localhost) dan menyetel limit RAM maksimum sebesar 25% dari total memori RAM server.

### `install/composer.sh`
Mengunduh dan memasang Composer global secara aman (`/usr/local/bin/composer`) disertai verifikasi checksum sha384.

### `install/firewall.sh`
Mengaktifkan firewall UFW (Uncomplicated Firewall) dengan aturan ketat:
* Buka Port: `80` (HTTP), `443` (HTTPS).
* Buka Port `22` (SSH) disertai pembatasan frekuensi login (`limit`) untuk mencegah serangan bruteforce.

### `site/create.sh`
Menerapkan virtual host Nginx dari berkas konfigurasi situs.
* **Penggunaan**: `sudo bash deploy/site/create.sh CONF_FILE [--no-ssl] [--ssl-only]`
* **Contoh**: `sudo bash deploy/site/create.sh deploy/sites/siperisai.my.id.conf`
* **Cara Kerja**: Membaca template `nginx-yii.conf`, mensubstitusi variabel domain dan path direktori, lalu mendaftarkannya ke folder `/etc/nginx/sites-enabled/`.

### `site/ssl.sh`
Mengajukan dan memperbarui sertifikat SSL HTTPS Let's Encrypt via Certbot.
* **Penggunaan**: `sudo bash deploy/site/ssl.sh CONF_FILE [--renew]`
* **Domain Terdaftar**: Otomatis mendaftarkan `siperisai.my.id`, `www.siperisai.my.id` (jika redirect aktif), dan `admin.siperisai.my.id`.
* **Auto-Renewal**: Memasang cron job certbot harian pada pukul 03:00 secara otomatis.

### `site/remove.sh`
Menghapus konfigurasi Nginx vhost situs dan dapat sekalian mencabut (revoke) sertifikat SSL.
* **Penggunaan**: `sudo bash deploy/site/remove.sh CONF_FILE [--with-ssl]`

### `app/first-setup.sh`
Inisialisasi aplikasi Yii2 pertama kali setelah source code siap.
* **Penggunaan**: `sudo bash deploy/app/first-setup.sh CONF_FILE [SQL_FILE]`
* **Fungsi**:
  1. Menjalankan `php init --env=Production --overwrite=all`.
  2. Membuat database `siperisai` dan user `siperisai` di MySQL.
  3. Menulis file kredensial [main-local.php](file:///home/wanforge/www/roniadiatmoko/siperisai/common/config/main-local.php).
  4. Menjalankan Composer install.
  5. Mengatur hak akses folder runtime/assets menjadi `777` dan owner ke user `wanforge:www-data`.
  6. Mengimpor berkas backup `.sql` (jika dilampirkan) atau menjalankan fresh migration (`php yii migrate`).
  7. Menyimpan file info kredensial di `/root/siperisai.my.id_credentials.txt`.

### `app/deploy.sh`
Script untuk deployment update mingguan/harian.
* **Penggunaan**: `sudo bash deploy/app/deploy.sh CONF_FILE [--skip-pull] [--skip-composer] [--skip-migrate]`
* **Contoh**: `sudo bash deploy/app/deploy.sh deploy/sites/siperisai.my.id.conf`

### `backup/import-backup.sh`
Mengimpor dump SQL database MySQL/MariaDB dari hosting/server lama.
* **Penggunaan**: `sudo bash deploy/backup/import-backup.sh SQL_FILE [db_name] [db_user] [db_pass]`

### `backup/backup.sh`
Script backup database & berkas upload manual atau terjadwal.
* **Penggunaan**: `sudo bash deploy/backup/backup.sh [CONFIG_FILE]`
* **Target Upload**: Ditentukan di `backup.conf` (mendukung disk lokal, Amazon S3, FTP server, SFTP SSH).

### `backup/restore.sh`
Memulihkan (restore) skema database dari file backup `.sql` atau `.sql.gz`.
* **Penggunaan**: `sudo bash deploy/backup/restore.sh CONFIG_FILE BACKUP_FILE`

---

## Site Config

Berkas konfigurasi utama per-situs disimpan di [siperisai.my.id.conf](file:///home/wanforge/www/roniadiatmoko/siperisai/deploy/sites/siperisai.my.id.conf):

```bash
# === SITE ===
SITE_DOMAIN="siperisai.my.id"          # Nama domain utama website
SITE_WWW_REDIRECT=true                 # Redirect permanen www.siperisai.my.id ke siperisai.my.id
SITE_ROOT="/home/wanforge/www/roniadiatmoko/siperisai" # Path absolut ke root project
SITE_USER="wanforge"                   # Linux user pemilik files & repo git
SITE_PHP_VERSION="8.3"                 # Versi PHP socket FPM yang digunakan
SITE_NODE_VERSION="22"                 # Versi Node.js (jika digunakan)

# === DATABASE ===
DB_TYPE="mysql"                        # Driver database (wajib mysql)
DB_HOST="127.0.0.1"                    # Host server database
DB_PORT="3306"                         # Port MySQL (default 3306)
DB_NAME="siperisai"                    # Nama database yang akan dibuat
DB_USER="siperisai"                    # Nama user database
DB_PASS=""                             # Kosongkan untuk digenerate acak saat first-setup

# === SSL ===
SSL_EMAIL="sugeng.sulistiyawan@gmail.com" # Alamat email verifikasi Let's Encrypt
```

---

## Backup Config

Berkas konfigurasi backup disimpan di `deploy/backup/backup.conf`. Konfigurasi ini mengatur jenis database, direktori uploads yang akan dibackup, target eksternal, retensi, serta webhook notifikasi Telegram.

```bash
BACKUP_APP_NAME="siperisai"
BACKUP_APP_ROOT="/home/wanforge/www/roniadiatmoko/siperisai"

# Database yang di-backup
BACKUP_DB=true
BACKUP_DB_TYPE="mysql"
BACKUP_DB_HOST="127.0.0.1"
BACKUP_DB_PORT="3306"
BACKUP_DB_NAME="siperisai"
BACKUP_DB_USER="siperisai"
BACKUP_DB_PASS="PASSWORD_MYSQL_DI_SINI"

# Direktori yang ikut di-backup (Uploads folder)
BACKUP_FILES=true
BACKUP_FILES_DIRS=(
    "frontend/web/uploads"
    "frontend/web/public/uploads"
)

# Folder yang dikecualikan agar file arsip tidak membengkak
BACKUP_FILES_EXCLUDE=(
    "backend/runtime"
    "frontend/runtime"
    "console/runtime"
    "node_modules"
    "vendor"
    ".git"
)

# Retensi (lamanya backup lokal dipertahankan)
BACKUP_RETAIN_DAYS=30

# Pilihan penyimpanan: "local" atau ditambah "s3", "ftp", "sftp"
BACKUP_BACKENDS="local"
BACKUP_LOCAL_DIR="/var/backups/siperisai"

# Notifikasi Telegram Bot jika terjadi error/sukses backup
BACKUP_NOTIFY_TELEGRAM_TOKEN="TOKEN_BOT_TELEGRAM"
BACKUP_NOTIFY_TELEGRAM_CHAT_ID="ID_CHAT_TELEGRAM"
```

---

## Cron & Backup Automation

Pembuatan tugas cron job backup otomatis telah disatukan di dalam script setup. 
Berkas cron diletakkan pada direktori sistem `/etc/cron.d/backup-siperisai` dengan aturan:

```cron
# Membaca config, mengeksekusi backup.sh, dan menulis log setiap jam 02:00 pagi
0 2 * * * root bash /home/wanforge/www/roniadiatmoko/siperisai/deploy/backup/backup.sh /home/wanforge/www/roniadiatmoko/siperisai/deploy/backup/backup.conf >> /var/log/backup-siperisai.log 2>&1
```

---

## Perintah Harian (Daily Commands)

### Monitoring Log
* **Log Error Web Server Nginx (Frontend)**:
  ```bash
  tail -f /var/log/nginx/siperisai.my.id.error.log
  ```
* **Log Error Web Server Nginx (Backend / Admin Subdomain)**:
  ```bash
  tail -f /var/log/nginx/admin.siperisai.my.id.error.log
  ```
* **Log Aplikasi Yii2**:
  ```bash
  tail -f frontend/runtime/logs/app.log
  tail -f backend/runtime/logs/app.log
  ```

### Layanan & Database
* **Melihat Informasi Kredensial Database Tergenerate**:
  ```bash
  sudo cat /root/siperisai.my.id_credentials.txt
  ```
* **Cek Status Seluruh Layanan (Nginx, PHP-FPM, MySQL, Redis)**:
  ```bash
  systemctl status nginx php8.3-fpm mariadb redis-server
  ```
* **Masuk ke CLI Database MySQL**:
  ```bash
  mysql -u siperisai -p -h 127.0.0.1 siperisai
  ```

---

## Troubleshooting

### Error: Database connection refused / Connection timed out
* **Penyebab**: Service MariaDB/MySQL tidak berjalan atau host/port di file config salah.
* **Solusi**: Cek status service dengan `systemctl status mariadb`. Pastikan DSN di `common/config/main-local.php` menggunakan port `3306` dan host `127.0.0.1` (bukan localhost jika menggunakan driver TCP).

### Error: 403 Forbidden / Permission Denied
* **Penyebab**: Folder runtime atau web assets tidak bisa ditulis oleh Nginx/PHP-FPM (user `www-data`).
* **Solusi**: Jalankan perbaikan permission manual dari root project:
  ```bash
  sudo chown -R wanforge:www-data .
  sudo chmod -R 755 .
  sudo chmod -R 777 backend/runtime backend/web/assets frontend/runtime frontend/web/assets console/runtime
  ```

### Let's Encrypt SSL gagal saat setup-server.sh
* **Penyebab**: DNS A-Record domain `siperisai.my.id` atau subdomain `admin.siperisai.my.id` belum diarahkan (pointing) ke IP server. Let's Encrypt membutuhkan validasi tantangan HTTP-01.
* **Solusi**: Arahkan DNS Anda terlebih dahulu. Jalankan setup vhost tanpa SSL (`--no-ssl`). Setelah DNS aktif penuh (bisa dicek dengan ping), buat SSL manual menggunakan:
  ```bash
  sudo bash deploy/site/ssl.sh deploy/sites/siperisai.my.id.conf
  ```
