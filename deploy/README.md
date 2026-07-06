# Production Deploy Guide — Si-PERISAI

Panduan deployment singkat, aman, dan *zero-downtime* untuk aplikasi **Yii2 Advanced** di server Ubuntu produksi (multi-app / multi-PHP).

---

## 1. Setup Awal Server (First Setup)
Jalankan script interaktif ini saat pertama kali deploy ke server baru:
```bash
sudo bash deploy/setup-server.sh
```
> [!IMPORTANT]
> Karena server Anda sudah memiliki aplikasi berjalan lainnya (Nginx, MySQL, Redis sudah ada):
> - Pilih **`Skip`** (tekan `Enter`) saat ditanya tentang instalasi **Nginx, MySQL, dan Redis**.
> - Masukkan versi PHP sesuai kebutuhan (contoh: **`8.3`**). PHP CLI utama sistem (PHP 8.5) akan otomatis diamankan/dikembalikan semula agar aplikasi lain tidak terganggu.

---

## 2. Deploy Update Aplikasi (Daily Update)
Gunakan perintah ini setiap kali ada pembaruan kode di git untuk deploy tanpa downtime (*zero-downtime*):
```bash
sudo bash deploy/app/deploy.sh deploy/sites/siperisai.my.id.conf
```
*Proses otomatis: git pull, composer install, database migration, perbaikan chmod folder, reload PHP-FPM 8.3.*

---

## 3. Otomatisasi & Manual Backup
### A. Jalankan Backup Manual
```bash
sudo bash deploy/backup/backup.sh deploy/backup/backup.conf
```
*Konfigurasi backup (kredensial, Telegram bot, target disk) diatur di `deploy/backup/backup.conf`.*

### B. Restore Database
```bash
sudo bash deploy/backup/restore.sh deploy/backup/backup.conf /var/backups/siperisai/nama_file_backup.sql.gz
```

### C. Cron Job Otomatis
Cron job otomatis dipasang saat *first setup* pada `/etc/cron.d/backup-siperisai` dan berjalan setiap **jam 02:00 pagi**.

---

## 4. Perintah Monitoring Harian
### A. Cek Logs
* **Nginx Error (Frontend)**:
  ```bash
  tail -f /var/log/nginx/siperisai.my.id.error.log
  ```
* **Nginx Error (Backend/Admin)**:
  ```bash
  tail -f /var/log/nginx/admin.siperisai.my.id.error.log
  ```
* **Yii2 App Logs**:
  ```bash
  tail -f frontend/runtime/logs/app.log
  tail -f backend/runtime/logs/app.log
  ```

### B. Cek Status Layanan & DB
* **Status Layanan**:
  ```bash
  systemctl status nginx php8.3-fpm mariadb redis-server
  ```
* **Kredensial Database Tergenerate**:
  ```bash
  sudo cat /root/siperisai.my.id_credentials.txt
  ```
* **Akses MySQL CLI**:
  ```bash
  mysql -u siperisai -p -h 127.0.0.1 siperisai
  ```
