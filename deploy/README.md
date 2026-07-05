# Production Deploy Toolkit — Si-PERISAI

Toolkit ini digunakan untuk melakukan setup server, SSL, database, backup, serta deployment otomatis (zero-downtime) untuk aplikasi **Yii2 Advanced** di server Linux Ubuntu.

---

## Struktur Folder

```text
deploy/
├── setup-server.sh              ← Script setup utama (interaktif): install software + konfigurasi awal
│
├── lib/
│   └── common.sh                ← Utility & logger helpers (jangan dijalankan langsung)
│
├── install/                     ← Script instalasi software dasar
│   ├── system.sh                ← Git, curl, ufw, fail2ban, dll
│   ├── php.sh                   ← PHP 8.3 & extensions (FPM, CLI, pgsql, gd, mbstring, dll)
│   ├── nginx.sh                 ← Nginx + hardened configuration
│   ├── postgresql.sh            ← PostgreSQL + auto-tuning RAM
│   ├── redis.sh                 ← Redis server
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
│   ├── backup.conf              ← Konfigurasi backup (Local, S3, FTP/SFTP, Telegram Notify)
│   ├── backup.sh                ← Menjalankan dump DB + backup files
│   ├── restore.sh               ← Restore DB dari file backup (.sql.gz)
│   └── import-backup.sh         ← Import file .sql ke database PostgreSQL
│
└── sites/
    └── siperisai.my.id.conf     ← File site config utama
```

---

## 1. Setup Server Baru (First-Time Setup)

Jalankan perintah ini pada server baru untuk menginstall seluruh software, database, Nginx vhost, SSL, dan backup cron:

```bash
sudo bash deploy/setup-server.sh
```

Script ini berjalan secara **interaktif**:
1. Mendeteksi software yang sudah terinstall di server.
2. Meminta input versi PHP (direkomendasikan: `8.3`).
3. Menawarkan pilihan untuk menginstall `System packages`, `PHP`, `Composer`, `Nginx`, `PostgreSQL`, dan `Redis`.
4. Membuat database PostgreSQL `siperisai`, menulis credentials ke `common/config/main-local.php`, dan menjalankan inisialisasi awal Yii.
5. Memasang **cron backup harian** otomatis jam 02:00.
6. Meminta **sertifikat SSL Let's Encrypt** untuk `siperisai.my.id`, `www.siperisai.my.id`, dan `admin.siperisai.my.id`.
7. Mengaktifkan firewall **UFW**.

---

## 2. Deploy Update Aplikasi

Setiap kali ada update code di repositori git, jalankan script ini untuk memperbarui aplikasi secara cepat tanpa downtime:

```bash
sudo bash deploy/app/deploy.sh deploy/sites/siperisai.my.id.conf
```

**Proses yang berjalan:**
1. Git pull otomatis pada branch saat ini.
2. Composer install (`--no-dev --optimize-autoloader`).
3. Menjalankan database migration Yii (`php yii migrate`).
4. Mengatur ulang permissions folder assets dan runtime.
5. Reload PHP-FPM socket untuk mengosongkan OPcache.

---

## 3. Manajemen Database & Backup

### Mengimpor Backup Database (Untuk Migrasi)
Jika Anda memiliki file `.sql` backup dari server lain dan ingin mengimpornya ke database `siperisai`:
```bash
sudo bash deploy/backup/import-backup.sh /path/to/backup.sql
```
*Script ini akan otomatis membuat database/role, mengimpor skema, mengupdate file `main-local.php`, serta menyimpan credentials di `/root/siperisai_my_id_credentials.txt`.*

### Menjalankan Backup Manual
Untuk menguji proses backup database dan file upload secara manual:
```bash
sudo bash deploy/backup/backup.sh
```

---

## Perintah Harian yang Berguna

* **Cek status log Nginx (Error)**:
  ```bash
  tail -f /var/log/nginx/siperisai.my.id.error.log
  tail -f /var/log/nginx/admin.siperisai.my.id.error.log
  ```
* **Cek Log Aplikasi Yii**:
  ```bash
  tail -f frontend/runtime/logs/app.log
  tail -f backend/runtime/logs/app.log
  ```
* **Force Renew SSL Manual**:
  ```bash
  sudo bash deploy/site/ssl.sh deploy/sites/siperisai.my.id.conf --renew
  ```
* **Melihat DB Credentials**:
  ```bash
  sudo cat /root/siperisai.my.id_credentials.txt
  ```
