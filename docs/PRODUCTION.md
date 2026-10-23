# Panduan Production ARTASWAHA

Panduan ini untuk deploy Laravel di VPS aaPanel tanpa Docker, dengan MySQL, Redis, OCR API, 9Router, Laravel Queue, dan Reverb. Queue worker dan Reverb dijaga Supervisor.

## 1. Prasyarat VPS

- PHP CLI versi 8.4 beserta ekstensi yang dibutuhkan (`composer check-platform-reqs`).
- Composer, Node.js/npm, Git, `curl`, `timeout`, Redis, MySQL, dan Supervisor.
- Domain aplikasi mengarah ke direktori `public/`; aktifkan HTTPS.
- Reverb memakai port internal `8080`, diproksikan lewat Nginx/aaPanel ke domain WebSocket dengan TLS/WSS.
- OCR API dan 9Router dapat diakses dari VPS melalui endpoint lokal yang telah diuji.

Pastikan PHP CLI yang digunakan Composer, Artisan, Queue, dan Reverb sama versinya dengan PHP situs aaPanel:

```bash
php -v
composer check-platform-reqs
node --version
npm --version
redis-cli ping
```

## 2. Siapkan kode dan `.env`

Clone repository ke direktori aplikasi, lalu siapkan `.env` production secara manual. Jangan pernah commit atau menyalin `.env` lokal ke GitHub. Isi secret OAuth, OCR, AI, database, Redis, dan Reverb melalui `.env` di VPS.

Contoh nilai penting (sesuaikan domain, secret, dan konfigurasi aaPanel):

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mac.digisite.id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=artaswaha
DB_USERNAME=artaswaha_user
DB_PASSWORD=GANTI_DENGAN_SECRET_DATABASE

CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_QUEUE_RETRY_AFTER=300

OCR_API_URL=http://127.0.0.1:18001
OCR_API_KEY=GANTI_DENGAN_API_KEY_OCR
AI_API_URL=http://127.0.0.1:20128/v1
AI_API_KEY=GANTI_DENGAN_API_KEY_9ROUTER
AI_MODEL=ag/gemini-3.8-flash-medium

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=100001
REVERB_APP_KEY=GANTI_DENGAN_KEY_REVERB
REVERB_APP_SECRET=GANTI_DENGAN_SECRET_REVERB
REVERB_HOST=ws.digistik.id
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_ALLOWED_ORIGINS=mac.digisite.id
REVERB_BROADCAST_HOST=127.0.0.1
REVERB_BROADCAST_PORT=8080
REVERB_BROADCAST_SCHEME=http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

`REVERB_HOST/PORT/SCHEME` adalah alamat publik yang dipakai browser dan Laravel untuk mengirim event. `REVERB_BROADCAST_*` adalah alamat internal koneksi Laravel → Reverb. `REVERB_SERVER_*` adalah alamat bind proses Reverb. Untuk `REVERB_ALLOWED_ORIGINS`, gunakan hostname website tanpa skema atau path; pisahkan beberapa hostname dengan koma.

Laravel memakai `GenerateQuizDraft::$timeout = 240` detik. Karena itu contoh Redis menetapkan `retry_after=300`, dan worker Supervisor di bawah memakai `--timeout=240`. `retry_after` harus lebih panjang daripada timeout job agar job yang lama tidak diproses dua kali.

> Jangan jalankan `php artisan key:generate` pada aplikasi yang sudah memiliki data/session production; mengganti `APP_KEY` dapat membuat data terenkripsi lama tidak terbaca. Bila instalasi baru, buat key satu kali sebelum menerima traffic.

Atur ownership agar user web server (umumnya `www` di aaPanel) dapat menulis ke `storage` dan `bootstrap/cache`, tetapi batasi akses file `.env` (`chmod 600 .env`). Pastikan akun DB memiliki hak minimum dan Redis tidak terbuka ke internet.

## 3. Setup awal aplikasi

Jalankan dari direktori proyek sebagai user deploy yang memiliki akses ke Supervisor:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci --no-audit --no-fund
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

Sebelum migrasi production, review migrasi baru dan pastikan backup database terbaru tersedia. Perintah migrasi mengubah schema database; siapkan pemulihan sebelum deploy.

## 4. Konfigurasi Supervisor

Pastikan `supervisord` aktif. Buat dua program di `/etc/supervisor/conf.d/`. Ganti `/www/wwwroot/mac.digisite.id` dan path PHP bila berbeda. Periksa `which php` dan gunakan binary PHP 8.4 CLI yang sama dengan aaPanel.

`/etc/supervisor/conf.d/artaswaha-queue.conf`:

```ini
[program:artaswaha-queue]
process_name=%(program_name)s_%(process_num)02d
directory=/www/wwwroot/mac.digisite.id
command=/usr/bin/php artisan queue:work redis --sleep=3 --tries=3 --timeout=240 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www
numprocs=1
redirect_stderr=true
stdout_logfile=/www/wwwroot/mac.digisite.id/storage/logs/queue-worker.log
stopwaitsecs=300
```

`/etc/supervisor/conf.d/artaswaha-reverb.conf`:

```ini
[program:artaswaha-reverb]
directory=/www/wwwroot/mac.digisite.id
command=/usr/bin/php artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www
numprocs=1
redirect_stderr=true
stdout_logfile=/www/wwwroot/mac.digisite.id/storage/logs/reverb.log
stopwaitsecs=30
```

> Gunakan nama program di atas karena `deploy.sh` mengecek `artaswaha-queue:*` dan `artaswaha-reverb`. Bila nama berbeda, set `QUEUE_SUPERVISOR_PROGRAM` atau `REVERB_SUPERVISOR_PROGRAM` sebelum menjalankan deploy.

Muat dan mulai program:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

Keduanya harus berstatus `RUNNING`. Supervisor perlu hak untuk menjalankan Artisan dan menulis log; jangan menjalankan worker sebagai root.

## 5. Proxy WebSocket aaPanel/Nginx

Di konfigurasi virtual host `ws.digistik.id`, proxy ke `http://127.0.0.1:8080` dan teruskan header WebSocket upgrade. Contoh blok server (sertifikat TLS dikelola aaPanel):

```nginx
location / {
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header Scheme $scheme;
    proxy_set_header SERVER_PORT $server_port;
    proxy_set_header REMOTE_ADDR $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_read_timeout 120s;
    proxy_pass http://127.0.0.1:8080;
}
```

> Sesuaikan hanya pada virtual host domain WebSocket; jangan mengganti konfigurasi utama situs Laravel. Validasi konfigurasi Nginx (`nginx -t`) sebelum reload. Batasi port `8080` agar hanya dapat diakses lokal/proxy.

## 6. Deploy berikutnya

Commit dan push perubahan ke GitHub terlebih dahulu. Di VPS, pastikan branch deploy bersih dan jalankan:

```bash
cd /www/wwwroot/mac.digisite.id
chmod +x deploy.sh
./deploy.sh
```

Script akan menolak working tree yang tidak bersih, mengaktifkan maintenance mode, fast-forward branch `main` dari `origin`, memasang Composer production dependencies, membangun aset npm, menjalankan migrasi, membuat storage link, mengoptimasi Laravel, memberi sinyal restart graceful pada queue dan Reverb, memeriksa status worker, lalu mengaktifkan kembali aplikasi dan mengecek `/up`, OCR `/health`, endpoint AI `/models`, serta port WebSocket internal.

Jika `supervisorctl` tersedia, script memeriksa status program Supervisor. Jika tidak tersedia, script memakai `pgrep` untuk memastikan proses worker dan Reverb terlihat aktif; ini hanya mengecek proses, bukan memastikan Supervisor akan otomatis menghidupkannya kembali. Atur Supervisor/aaPanel untuk `autorestart` dan pastikan queue serta Reverb tetap dikelola oleh process manager.

Deploy menjalankan maintenance mode selama proses berlangsung. Bila perintah gagal sebelum tahap `php artisan up`, aplikasi sengaja dibiarkan dalam maintenance agar tidak melayani traffic dengan rilis setengah jadi. Setelah memperbaiki penyebabnya, jalankan `php artisan up` secara manual.

Untuk branch/remote atau nama Supervisor yang berbeda:

```bash
DEPLOY_BRANCH=main DEPLOY_REMOTE=origin \
QUEUE_SUPERVISOR_PROGRAM='artaswaha-queue:*' \
REVERB_SUPERVISOR_PROGRAM=artaswaha-reverb ./deploy.sh
```

Jika `supervisorctl` terpasang di lokasi yang tidak ada pada `PATH`, set `SUPERVISORCTL_BIN=/path/ke/supervisorctl`. Bila command memang tidak tersedia, script memakai pemeriksaan proses langsung.

Script tidak melakukan rollback otomatis. Bila migrasi atau health check gagal, jangan menghapus/mereset database; periksa output dan log, pulihkan backup bila migrasi memang merusak data, lalu perbaiki penyebabnya.

## 7. Pemeriksaan dan log

```bash
sudo supervisorctl status
sudo supervisorctl tail -f artaswaha-queue:artaswaha-queue_00
sudo supervisorctl tail -f artaswaha-reverb
tail -f storage/logs/laravel.log
php artisan queue:failed
```

Health check Reverb di script menguji port proses di VPS, bukan keberhasilan subscribe channel dari browser. Uji juga dari perangkat eksternal: buka room di dua browser, join/keluar peserta, lalu pastikan daftar anggota berubah langsung. Browser harus tersambung ke `wss://` domain publik dan channel presence harus lolos otorisasi.

Jika proses restart terus-menerus, cek path PHP, direktori kerja, user/permission, `.env`, Redis, dan log Supervisor. Jika queue berhenti memproses, pastikan `QUEUE_CONNECTION=redis`, Redis sehat, worker `RUNNING`, lalu cek `php artisan queue:failed` dan `storage/logs/laravel.log`.

## Referensi resmi

- [Laravel deployment](https://laravel.com/docs/13.x/deployment)
- [Laravel queues dan Supervisor](https://laravel.com/docs/13.x/queues)
- [Laravel Reverb production](https://laravel.com/docs/13.x/reverb)
