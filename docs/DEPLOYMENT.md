# Migrasi navigasi React Router — 29 Oktober 2026

Inertia sudah dihapus dari dependency PHP dan JavaScript. Deploy source Laravel, `composer.lock`, `package-lock.json`, build client, dan build SSR sebagai satu release. Tidak ada migrasi database atau penghapusan akun/progres untuk perubahan router ini.

1. Jalankan `composer install --no-dev --optimize-autoloader --no-interaction`, `npm ci --ignore-scripts`, lalu `npm run build` sesuai prosedur release yang ada.
2. Ubah command program SSR lama menjadi `node /path/aplikasi/bootstrap/ssr/ssr.js` (gunakan path Node sebenarnya). Perintah `inertia:start-ssr` dan `inertia:check-ssr` tidak tersedia lagi. Hentikan program SSR lama sebelum menjalankan penggantinya pada port yang sama.
3. SSR tetap loopback port 13714; cek `curl --fail http://127.0.0.1:13714/health`. Konfigurasi memakai `REACT_SSR_ENABLED` (default true) dan `REACT_SSR_URL` (default `http://127.0.0.1:13714/render`). Hapus konfigurasi `INERTIA_SSR_*` lama. Jangan mengubah APP_KEY, cookie/sesi, database, worker AI/TTS, atau Reverb.
4. Bersihkan/bangun cache Laravel mengikuti prosedur di bawah, lalu restart hanya SSR ARTASWAHA setelah build. `deploy.sh` tidak mengganti command atau restart SSR otomatis. Setelah aplikasi keluar dari maintenance, skrip memeriksa `/health` pada origin `react.ssr.url` dari konfigurasi Laravel aktif (termasuk config cache). Respons harus HTTP sukses dengan JSON `status: "ok"`; koneksi dibatasi 3 detik dan permintaan 5 detik. Jika gagal, deploy keluar dengan error tanpa menutup situs kembali. SSR yang dinonaktifkan dilewati dengan pesan SKIP. Health check memastikan layanan tersambung, bukan memastikan proses sudah memuat build terbaru; restart SSR setelah build tetap diperlukan.
5. Minta tab lama dimuat ulang setelah cutover karena kontrak request berubah. Pertahankan aset hash release lama selama pergantian release, tetapi frontend Inertia lama tidak kompatibel dengan backend baru.
6. Periksa direct URL, refresh, Back/Forward, interview, akun/anonim, login/logout, upload avatar, editor, jawaban/hasil modul dan room, serta analitik admin. Metadata dan konten HTML awal harus tersedia saat SSR sehat.

React Router memakai loader yang meminta JSON dengan `X-React-Page: true`; Laravel tetap mengotorisasi setiap request. Respons pribadi `no-store`; isi halaman tidak disalin ke browser history/localStorage. Status form, penguncian modul, hasil kuis, CSRF, dan sesi tetap berasal dari server. Pergantian router bukan jaminan penurunan latensi server production.

### Memeriksa jeda navigasi

Setelah deploy source dan `php artisan optimize`, buka DevTools → Network → request Fetch/XHR → Timing atau Headers. Respons Laravel menyertakan `Server-Timing`: `app` mengukur rangkaian middleware global hingga respons siap (termasuk session dan controller); `db` mengukur eksekusi SQL selama rentang tersebut. `db` adalah bagian dari `app`, bukan durasi tambahan. SQL, parameter, dan data pengguna tidak disertakan dan query log tidak diaktifkan.

Bandingkan dengan Waiting for server response. Selisihnya dapat mencakup jaringan/proxy, antrean PHP-FPM, bootstrap PHP/Laravel sebelum middleware, dan pembentukan koneksi database; jangan menyebut seluruh selisih itu sebagai waktu jaringan. Pengukuran ini tidak mencakup render React, streaming respons, atau pekerjaan setelah respons dikirim. Endpoint/aset yang tidak melewati Laravel tidak memiliki header ini. Tidak perlu mengaktifkan APP_DEBUG atau mengubah konfigurasi SSR.

---

# Pembaruan production: Chatterbox CLI dalam folder ARTASWAHA

Panduan ini untuk aplikasi yang **sudah berjalan**, berdasarkan commit `e5e8eee` dan perubahan adapter CLI setelahnya. Kode adapter, requirements, dan installer sudah disediakan. Adapter CLI sudah tersedia di source; tidak perlu menulis wrapper/API baru. Pastikan perubahan setelah commit tersebut sudah di-commit/push sebelum pull di server.

Alur: admin → queue Laravel → Python CLI → WAV privat → review admin → pelajar memutar file. Tidak ada API/port/domain TTS baru. Proses Python selesai setelah job, sehingga model dimuat ulang pada generate berikutnya dan RAM dapat dilepas. Ollama/Qwen tetap ditunda; graf/Graphify dan statistik database dipertahankan.

## 1. Sebelum pull

Temukan folder, branch, binary PHP, dan user worker yang sebenarnya. Contoh di bawah memakai aaPanel, user `www`, folder `/www/wwwroot/artaswaha`, PHP 8.4. Jangan menjalankan contoh dengan path yang belum diverifikasi.

```bash
export APP_DIR=/www/wwwroot/artaswaha
export PHP_BIN=/www/server/php/84/bin/php
cd "$APP_DIR"
git status --short
git log -1 --oneline
"$PHP_BIN" -v
free -h
df -h "$APP_DIR"
supervisorctl status
```

Backup database, `.env`, `storage/app/private`, dan `storage/app/public` dengan fasilitas backup server. Simpan backup di luar public root. Jangan menampilkan secret. Jika ada perubahan source server yang belum disimpan, selesaikan terlebih dahulu tanpa reset/clean paksa.

Pertahankan APP_KEY, database, akun, domain/HTTPS, Redis, Reverb, OCR, dan AI draf kuis yang sudah bekerja. Tidak perlu membuat aplikasi/database/admin baru.

## 2. Tambahkan environment minimal

Edit `.env` yang ada, bukan menimpanya dengan template. Edit nilai yang sudah ada agar tidak duplikat:

```dotenv
TTS_ENABLED=false
TTS_ENGINE=chatterbox
TTS_DEVICE=cpu
TTS_PYTHON=/www/wwwroot/artaswaha/storage/app/private/tools/chatterbox/venv/bin/python
TTS_CHATTERBOX_MODEL_PATH=/www/wwwroot/artaswaha/storage/app/private/tools/chatterbox/models
REDIS_QUEUE_RETRY_AFTER=300
DB_QUEUE_RETRY_AFTER=300
```

Sesuaikan path. `cpu` adalah pilihan awal eksplisit, bukan pernyataan RAM 8 GB pasti cukup. Jika akan memakai CUDA, periksa GPU/VRAM dan driver dahulu, lalu gunakan `cuda` pada installer dan konfigurasi. Tidak perlu mengisi `TTS_API_*`, `TTS_MMS_MODEL_PATH`, atau menginstall Ollama untuk alur ini.

## 3. Pull dan deploy source

Gunakan branch production yang sebenarnya. Pastikan commit yang berisi `install-tts.sh`, `app/Services/tts_chatterbox.py`, dan perubahan service sudah tersedia pada remote. Commit `e5e8eee` saja belum memuat adapter CLI Chatterbox ini.

Dalam maintenance window singkat:

```bash
export PATH="$(dirname "$PHP_BIN"):$PATH"
"$PHP_BIN" artisan down --retry=60
git pull --ff-only origin main
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
composer check-platform-reqs --no-dev
"$PHP_BIN" artisan config:clear
"$PHP_BIN" artisan migrate --force
npm ci --no-audit --no-fund
npm run build
"$PHP_BIN" artisan optimize
"$PHP_BIN" artisan up
```

Gunakan Node yang memenuhi package/lock proyek; Vite saat persiapan memerlukan Node 20.19+ atau 22.12+. Jangan menjalankan `composer update`, `key:generate`, `migrate:fresh`, atau seeder demo pada database production yang sudah ada.

Aplikasi dapat tetap berjalan dengan TTS nonaktif selama runtime diunduh. Skrip deploy memeriksa worker learner sebelum menyalakan maintenance jika TTS atau analisis diaktifkan. Jika keduanya nonaktif, worker tersebut dilewati. Jika worker hilang selama deploy, kegagalan dilaporkan setelah aplikasi dibuka kembali; pemeriksaan ini tidak meninggalkan situs dalam maintenance.

`deploy.sh` tidak menjalankan `queue:restart` atau `reverb:restart`. Setelah deploy, gunakan tombol Restart hanya pada program Supervisor ARTASWAHA yang kode/konfigurasinya berubah. Untuk TTS, restart program `laravel-worker-tts-artaswaha`; perubahan TTS saja tidak memerlukan restart Reverb. Pemeriksaan proses aktif memverifikasi liveness, bukan memastikan proses sudah memuat kode terbaru. Supervisor CLI tetap dipakai untuk pemeriksaan status jika tersedia, tanpa memanggil restart otomatis.

## 4. Install runtime dan model: satu perintah

Lokasi yang dibuat installer:

```text
storage/app/private/tools/chatterbox/
  bin/uv                   alat instalasi lokal
  python/                  runtime Python 3.11 lokal
  venv/                    paket Chatterbox terpisah
  models/base/             ve.pt, s3gen.pt, tokenizer.json, conds.pt
  models/indonesian/        checkpoint t3_cfg.safetensors
  models/manifest.json     revision model yang dipin
  cache/                   cache paket/Hugging Face/Numba/Torch
  samples/indonesia.wav    hasil smoke test privat
  requirements-installed.txt
```

Tidak ada bobot dasar `t3_cfg.pt` yang diunduh: runner langsung memuat checkpoint Indonesia ke T3, sehingga tidak perlu memuat model T3 dasar lalu menimpanya. Loader `.pt` memakai `map_location=cpu` dan `weights_only=True`, kemudian memindahkan komponen ke device yang dipilih. Ini menghindari loader lama yang bisa gagal pada CPU karena checkpoint tersimpan di CUDA.

Periksa bahwa user worker bisa menulis `storage/app/private/tools`. Installer menolak root agar model/cache tidak menjadi tidak terbaca oleh worker. Jalankan sebagai user worker yang sebenarnya; jika bukan `www`, ganti nama berikut:

```bash
cd "$APP_DIR"
sudo -u www bash ./install-tts.sh --device cpu
```

Untuk GPU NVIDIA dengan driver dan hardware yang kompatibel dengan PyTorch 2.6/CUDA 12.4:

```bash
nvidia-smi
sudo -u www bash ./install-tts.sh --device cuda
```

Pilih satu jalur. Installer tidak mengganti driver, Python global, `.env`, database, atau konfigurasi aplikasi lain. Paket utama mengikuti Chatterbox 0.1.1 yang dirujuk model Indonesia, dan dipasang dalam virtualenv. Versi dependensi/komponen tambahan tercantum dalam `app/Services/tts_chatterbox_requirements.txt`; versi hasil resolusi direkam sesudah smoke test sukses.

Installer menjalankan download revision yang dipin, `check`, lalu satu generate offline dengan batas waktu 240 detik. CPU threads dibatasi dua. Jika smoke test gagal/timeout, installer exit gagal dan tidak mengaktifkan TTS. Jangan menganggap model siap hanya karena paket atau aset sudah terunduh.

Prasyarat Linux: `curl`, CA certificates, `timeout`, dan kemampuan mengeksekusi program dari storage. Jika library audio OS memang belum tersedia, gunakan package manager server. Contoh Debian/Ubuntu, hanya bila dibutuhkan:

```bash
sudo apt-get update
sudo apt-get install -y curl ca-certificates libsndfile1
```

FFmpeg, FastAPI, Nginx proxy TTS, dan Supervisor Python tidak diperlukan. Kecepatan audio diproses dengan `librosa.effects.time_stretch` yang tersedia dalam dependensi Chatterbox. Jika installer gagal karena permission, perbaiki kepemilikan/akses folder runtime untuk user worker; jangan `chmod 777` atau mengubah seluruh kepemilikan proyek tanpa pemeriksaan.

## 5. Periksa runtime sebagai user worker

Installer sudah melakukan pengujian ini, tetapi command berikut dapat dipakai untuk diagnosis atau pengujian ulang tanpa unduhan:

```bash
export TTS_ROOT="$APP_DIR/storage/app/private/tools/chatterbox"
sudo -u www "$TTS_ROOT/venv/bin/python" \
  "$APP_DIR/app/Services/tts_chatterbox.py" check \
  --model-dir "$TTS_ROOT/models" --device cpu
```

`check` memeriksa aset/revision, versi paket, import dependensi, dan ketersediaan device. Ia tidak memuat seluruh bobot atau membuktikan kualitas suara. Uji generate nyata:

```bash
printf 'Selamat datang. Mari belajar teka-teki tradisional Bali.\n' | \
  sudo -u www timeout --kill-after=10 240 "$TTS_ROOT/venv/bin/python" \
  "$APP_DIR/app/Services/tts_chatterbox.py" synthesize \
  --model-dir "$TTS_ROOT/models" --device cpu \
  -v id -s 150 -w "$TTS_ROOT/samples/indonesia.wav"
```

Ganti `--device cpu` menjadi `--device cuda` pada kedua perintah jika memang menggunakan runtime CUDA. Lakukan uji tambahan dengan `-s 180` dan output lain untuk memeriksa pengaturan tempo. Narasi dibaca dari stdin, bukan dieksekusi shell. Inferensi mengaktifkan mode offline dan menggunakan model/cache lokal tanpa memakai cache `/root`.

Jika sukses, runner mencetak JSON waktu proses dan ukuran WAV; pada Linux juga peak RSS, dan pada CUDA peak VRAM reserved. Ini hasil pengukuran proses tersebut, bukan jaminan kebutuhan semua narasi. Dengarkan WAV melalui akses privat yang sah; jangan membuat symlink folder model/sample ke public.

Pada RAM terbatas, ukur peak memory sebelum memakai generate bersamaan dengan pekerjaan lain. Model lebih berat dapat gagal dalam batas waktu aplikasi pada CPU. Jangan menaikkan timeout sendirian tanpa menyesuaikan job/reservation/lock/Supervisor; pertimbangkan hardware yang memadai atau narasi lebih pendek setelah pengukuran.

## 6. Worker Laravel sesuai QUEUE_CONNECTION

Seluruh job baru mengikuti `QUEUE_CONNECTION` serta antrean default koneksi tersebut: Redis memakai REDIS_QUEUE, database memakai DB_QUEUE, keduanya default `default`. Analisis, audio, laporan AI, draf kuis, dan tes massal tidak lagi dipaksa ke koneksi `learner-analysis`. Reverb tetap terpisah. Koneksi database lama `learner-analysis` dipertahankan hanya untuk menghabiskan pekerjaan yang sudah terantre sebelum perpindahan.

Analisis progres memakai `AI_API_URL`, `AI_API_KEY`, dan `AI_MODEL` yang sama dengan AI kuis. Jadwal `learning:analyze` memeriksa setiap jam dan memperbarui hasil yang sudah berusia enam jam melalui scheduler Laravel. Pastikan cron yang sudah ada menjalankan `php artisan schedule:run` setiap menit dari folder aplikasi; jangan menambahkan cron duplikat. Untuk lokal gunakan `php artisan schedule:work`. Worker sesuai QUEUE_CONNECTION tetap harus aktif untuk memproses antreannya. Analisis memakai ringkasan pelajaran selesai dan jawaban kuis 14 hari terakhir, bukan pelacakan durasi membaca.

Jeda enam jam berlaku untuk analisis yang sudah selesai. Percobaan gagal bisa dijadwalkan ulang setelah dua menit; antrean/pemrosesan yang tersangkut bisa diganti setelah 30 menit saat profil dibuka atau perintah `learning:analyze` dijalankan. Lock unik sekarang mengikuti waktu permintaan, sehingga percobaan lama tidak menghalangi penggantinya dan tidak menimpa status/hasil baru. Status menunggu antrean tidak menampilkan skeleton pemrosesan.

Setelah memperbarui perbaikan antrean, cache konfigurasi melalui alur deploy bagian 3, lalu Restart hanya program `laravel-worker-tts-artaswaha` melalui Supervisor aaPanel agar kode/config baru dipakai. Jalankan `php artisan learning:analyze` untuk menjadwalkan profil yang sudah memenuhi jeda percobaan ulang, lalu periksa `php artisan queue:failed` dan log terbaru. Pastikan `AI_API_URL` dapat diakses dari server production; `127.0.0.1` menunjuk mesin server itu sendiri. Worker ini juga mengerjakan audio: bila sedang membuat audio panjang, analisis menunggu gilirannya. Log `Learner analysis provider returned an error.` mencatat status HTTP tanpa key atau isi jawaban.

Untuk server production ini, gunakan program Supervisor aaPanel yang sudah ada, `laravel-worker-tts-artaswaha`. Edit program tersebut; tidak perlu memasang service systemd atau menjalankan `install-tts-worker.sh`. Isi:

```text
Name: laravel-worker-tts-artaswaha
Run User: www
Processes: 1
Process directory: /www/wwwroot/artaswaha.kerthajaya.com/
Start command: php artisan queue:work --sleep=3 --tries=3 --timeout=240 --max-time=3600
```

Pastikan autostart/autorestart aktif. Gunakan menu Log/Restart untuk program tersebut di Supervisor aaPanel. Jika PHP tidak ditemukan oleh Supervisor, gunakan path absolut PHP 8.4 hasil `command -v php` pada Start command. Gunakan satu program worker yang sesuai koneksi .env; nonaktifkan duplikat setelah antrean lama selesai. Jika service systemd dari langkah sebelumnya pernah dipasang, nonaktifkan agar tidak menjalankan worker ganda:

```bash
systemctl disable --now artaswaha-learner.service
```

`deploy.sh` menerima nama program yang sudah ada melalui `.env` atau environment shell. Jika Supervisor CLI tersedia, set nama group sesuai konfigurasi aaPanel, misalnya `LEARNER_QUEUE_SUPERVISOR_PROGRAM=laravel-worker-tts-artaswaha:*`. Jika nama dikosongkan atau Supervisor CLI tidak tersedia, deploy mencari proses queue:work lalu membaca argumen NUL-separated pada `/proc/<pid>/cmdline`. Path absolut ke artisan diterima walaupun working directory aaPanel berbeda; path relatif diverifikasi terhadap `/proc/<pid>/cwd`. Path artisan yang telah dikanonisasi harus tepat milik aplikasi ini, dan argumen koneksi eksplisit harus sama dengan QUEUE_CONNECTION. Tanpa argumen koneksi, worker mengikuti environment aplikasi. Opsi `--sleep=3` dan `--sleep 3`, termasuk opsi sebelum koneksi, diterima tanpa menjalankan/eval isi argumen. Worker aplikasi lain atau koneksi yang berbeda tetap ditolak. Jalankan deploy dengan user yang dapat membaca `/proc/<pid>/cmdline` dan cwd, atau isi nama program Supervisor yang benar. Skrip tidak mencetak seluruh command line agar argumen lain tidak membocorkan informasi.

Untuk tuning CPU, set `TTS_CPU_THREADS=4` dan `TTS_PERSISTENT=true` dalam `.env`, jalankan `php artisan config:cache`, lalu restart hanya program TTS di Supervisor aaPanel. Nilai bawaan thread tetap 2; coba 4 kemudian 8 pada server banyak core dengan narasi yang sama. Runner membatasi jumlah thread berdasarkan CPU yang tersedia dan menyisakan setidaknya satu CPU jika memungkinkan. Satu tugas inferensi tetap dijalankan pada satu waktu. Lebih banyak thread belum tentu lebih cepat, dan jumlah thread bukan jaminan batas penggunaan CPU seluruh proses.

Dengan `TTS_PERSISTENT=true`, worker Laravel mempertahankan satu proses Python lokal melalui stdin/stdout. Nilai bawaan tetap `false` sampai administrator mengaktifkannya setelah memastikan Processes=1 pada program TTS. Model dimuat saat tugas pertama, kemudian dipakai ulang untuk tugas berikutnya. Referensi suara yang path/ukuran/waktu modifikasinya sama tidak diproses ulang; referensi yang berubah dimuat ulang. Mengosongkan referensi mengembalikan suara bawaan. Tidak ada HTTP API, port baru, paket tambahan, atau service Supervisor tambahan.

Model menggunakan RAM selama proses Python hidup. Proses dilepas setelah 15 menit tanpa permintaan atau setelah 50 tugas; tugas berikutnya memuat model kembali. Restart/keluarnya worker Laravel juga menghentikan proses Python; di Linux child meminta sinyal ketika induk mati agar tidak tertinggal. Setiap tugas dibatasi 1800 detik (30 menit), dan crash/timeout mereset proses sebelum tugas berikutnya. Jika perlu kembali ke perilaku satu proses per tugas, set `TTS_PERSISTENT=false`, cache konfigurasi, lalu restart program TTS.

Narasi mendukung hingga 16.000 karakter dan audio hasil generate hingga 64 MB; unggahan rekaman tetap maksimal 10 MB. Potongan Chatterbox tetap dibatasi 220 karakter, tetapi kalimat pendek digabung selama muat dalam satu potongan untuk mengurangi pemanggilan decoder berulang. Ukuran kamus token decoder tidak diubah. Batas ini bukan jaminan seluruh narasi panjang selesai dalam 30 menit pada CPU; bagi materi jika melebihi kapasitas mesin.

Untuk menguji pemakaian ulang, buat audio pendek melalui admin pada dua modul/soal percobaan yang berbeda. Menyimpan ulang narasi yang sama tidak memicu inferensi. Gunakan narasi/referensi sama agar perbandingan masuk akal. Log `Chatterbox TTS selesai` di `storage/logs/laravel.log` mencatat `model_reused`, `model_load_seconds`, `reference_seconds`, `generation_seconds`, `seconds`, dan `threads` tanpa isi narasi. Tugas kedua diharapkan memiliki `model_reused=true` saat ditangani proses yang sama. Waktu sintesis dan pemakaian RAM sebenarnya perlu diukur di server; biaya pemuatan yang berkurang tidak menjamin seluruh proses menjadi instan.

Jalankan migrasi setelah menarik pembaruan timer audio. Kolom `generation_started_at` dan `generation_finished_at` menyimpan waktu proses aktual dari worker, termasuk ketika tugas gagal; menyimpan narasi baru mengosongkan waktu versi lama. Durasi pada UI menghitung waktu pemrosesan, tanpa waktu menunggu antrean. Jam awal/akhir memakai zona waktu aplikasi.

Dalam mode persisten, Python melaporkan fase dan bagian yang sudah selesai melalui pipe yang sama. Metadata progres disimpan dalam cache per ID/versi audio selama satu jam, diperbarui per bagian (bukan per token). UI mengecek status setiap tiga detik saat tab terlihat; timer lokal berdetak tiap detik tanpa request tambahan. Bar mengikuti jumlah bagian yang benar-benar selesai. Estimasi sisa dihitung dari waktu generasi dan jumlah karakter yang sudah diproses pada tugas tersebut; sebelum bagian pertama selesai estimasi belum tersedia. Estimasi dapat berubah atau terlewati, dan tidak digunakan sebagai penanda tugas selesai. Untuk adapter selain mode persisten, waktu aktual tetap tercatat, tetapi progres per bagian/estimasi dapat belum tersedia.

Perintah CLI `synthesize` untuk diagnosis tetap memuat model baru setiap eksekusi. Gunakan perintah itu untuk membandingkan thread 4 dan 8 dengan teks/referensi/perangkat yang sama, bukan untuk mengukur pemakaian ulang model. Pengukuran mode persisten dilakukan lewat antrean admin seperti di atas. JSON CLI menampilkan `model_load_seconds` (termasuk startup/import), `reference_seconds`, `generation_seconds`, `model_reused`, serta jumlah `threads` efektif.

Contoh konfigurasi Supervisor mentah jika diperlukan (sesuaikan nama/path dengan program aaPanel yang sudah ada):

```ini
[program:artaswaha-learner]
process_name=%(program_name)s_%(process_num)02d
directory=/www/wwwroot/artaswaha
command=/www/server/php/84/bin/php /www/wwwroot/artaswaha/artisan queue:work --sleep=3 --tries=3 --timeout=240 --max-time=3600
user=www
numprocs=1
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
stopwaitsecs=1860
redirect_stderr=true
stdout_logfile=/www/wwwroot/artaswaha/storage/logs/learner-worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl status
```

Jika program sudah ada, periksa dan sesuaikan; jangan membuat duplikat. `retry_after` Redis/database minimal 1860 detik; timeout inferensi 1800, job 1830, lock 1845, dan stopwaitsecs 1860. Setelah perubahan ini, cache konfigurasi dan restart worker TTS agar reservasi antrean dan batas proses memakai nilai baru. Pertahankan cache lock bersama yang sudah digunakan aplikasi. Pastikan PHP CLI mengizinkan `proc_open`, `proc_get_status`, `proc_terminate`, dan `proc_close` yang digunakan untuk proses anak. Pengaturan CLI aaPanel dapat berbeda dari PHP-FPM.

## 7. Aktifkan setelah smoke test sukses

Installer mencetak path environment yang benar. Isi pada `.env` production:

```dotenv
TTS_ENABLED=true
TTS_ENGINE=chatterbox
TTS_DEVICE=cpu
TTS_PYTHON=/www/wwwroot/artaswaha/storage/app/private/tools/chatterbox/venv/bin/python
TTS_CHATTERBOX_MODEL_PATH=/www/wwwroot/artaswaha/storage/app/private/tools/chatterbox/models
```

Referensi suara Indonesia (opsional): simpan rekaman bersih satu penutur Indonesia dalam WAV PCM sepanjang 6–10 detik di `storage/app/private/tools/chatterbox/voices/indonesia.wav`. Gunakan suara sendiri atau suara yang pemiliknya mengizinkan. Pastikan file dapat dibaca pengguna worker. Isi `.env` dengan path absolut sesuai folder aplikasi, misalnya:

```dotenv
TTS_CHATTERBOX_REFERENCE_AUDIO=/www/wwwroot/artaswaha.kerthajaya.com/storage/app/private/tools/chatterbox/voices/indonesia.wav
```

Jalankan `php artisan config:cache` lalu Restart hanya program TTS di Supervisor aaPanel. Model tidak perlu diunduh ulang. Referensi digunakan untuk semua potongan narasi dan dapat dipakai ulang antar tugas dalam mode persisten; sintesis tetap offline. Untuk pengecekan CLI, tambahkan `--reference-audio /path/ke/indonesia.wav` pada perintah `check` maupun `synthesize`. Path salah atau WAV di luar durasi tersebut akan ditolak; konfigurasi yang kosong tetap memakai suara bawaan. Perbaikan aksen harus dinilai dari hasil audio, bukan hanya status tugas.

Audio lama tidak berubah otomatis setelah mengganti referensi. Uji referensi baru pada audio draf atau materi percobaan baru, lalu dengarkan dan setujui hasilnya. Simpan narasi tanpa perubahan mempertahankan audio lama.

Lalu:

```bash
cd "$APP_DIR"
"$PHP_BIN" artisan config:clear
"$PHP_BIN" artisan optimize
supervisorctl status
```

Restart program TTS melalui Supervisor aaPanel dan tunggu status Running. Default cache/store production harus dapat dipakai oleh user web dan worker yang sama. Tidak perlu mengubah Reverb/domain atau membuka port baru untuk TTS.

## 8. Uji melalui admin

1. Buka Admin → Audio pembelajaran → pilih modul uji atau draft yang sah.
2. Masukkan narasi Indonesia, kecepatan 150, tekan Buat audio offline.
3. Status harus menjadi queued → processing → review. Perbarui status bila diperlukan.
4. Dengarkan preview; admin menyetujui setelah memeriksa suara. Hasil baru menjadi ready dan dapat digunakan pelajar.
5. Uji perubahan narasi, kegagalan proses, dan tempo lain. Pastikan file sementara dibersihkan saat gagal.
6. Pastikan audio soal masa depan tetap ditolak dan kuis realtime tetap bekerja.
7. Periksa graf/Graphify dan statistik database tetap tersedia. Analisis LLM belum aktif karena Ollama ditunda.

Tidak perlu seed materi demo secara massal di production. Audio sudah disetujui tetap diputar dari file tanpa memanggil model lagi. Simpan narasi tanpa perubahan mempertahankan file dan status; deduplikasi generate menggunakan kembali hasil yang masih berlaku. Perubahan narasi, bahasa, atau tempo memerlukan audio baru dan persetujuan ulang.

Diagnosis awal:

```bash
"$PHP_BIN" artisan queue:failed
supervisorctl status
```

Periksa log Laravel dan `storage/logs/learner-worker.log`. Bedakan error lama dari pengujian baru. `/up` yang sukses tidak membuktikan model/worker berhasil. Jika terjadi kegagalan pemasangan, pertahankan `TTS_ENABLED=false`; modul manual, unggah rekaman, dan audio yang sudah disetujui tetap dapat dipakai.

Jika versi skrip deploy lama berhenti pada pemeriksaan worker setelah migration/build selesai dan situs menampilkan 503 maintenance, jalankan `php artisan up` dari folder proyek untuk membuka aplikasi kembali. Periksa pesan kegagalan deploy sebelum mengulanginya. Jangan menghapus atau me-rollback database untuk menangani maintenance yang tertinggal.

## 9. Git, backup, dan pembaruan berikutnya

Source yang dikirim: runner Python, requirements, installer, perubahan Laravel, tests, template environment, dan panduan ini. Model, cache, virtualenv, sample WAV, `.env`, dan audio pengguna berada di storage privat yang diabaikan Git. Jangan memakai `git add -f` untuk runtime/model. Pemeriksaan:

```bash
git status --short
git diff --check
git ls-files '*.pt' '*.safetensors' '*.gguf' '*.onnx'
git check-ignore storage/app/private/tools/chatterbox/models/manifest.json
```

Update kode tidak perlu menginstall ulang model setiap deploy. Simpan folder storage persisten dan backup audio/database/APP_KEY. Installer dapat dijalankan ulang untuk melengkapi runtime yang gagal; ia tidak mengubah `.env` dan tidak menghapus audio pengguna. Jangan menghapus runtime saat worker masih memakainya. Pemilihan ulang cpu/cuda memilih build Torch yang sesuai secara eksplisit, dan smoke test tetap harus lolos.

Model/revision dan API library diverifikasi dari paket resmi, tetapi inferensi Chatterbox dengan bobot nyata harus diuji pada server target. Pengujian aplikasi dengan process fake dan unit test runner tidak boleh dilaporkan sebagai benchmark kualitas/model production.

Referensi: [model Indonesia](https://huggingface.co/grandhigh/Chatterbox-TTS-Indonesian), [metadata paket 0.1.1](https://pypi.org/pypi/chatterbox-tts/0.1.1/json), [uv installer](https://docs.astral.sh/uv/reference/installer/), [PyTorch versions](https://pytorch.org/get-started/previous-versions/).

## Keyboard aksara Bali: pembaruan aplikasi

Implementasi keyboard tersedia pada commit `741b445`. Fitur menggunakan JavaScript browser dan font Vimala lokal yang sudah ada; tidak memerlukan paket baru, `.env` baru, model, API, port, atau service tambahan.

Sesudah menarik source, jalankan migrasi `2026_10_26_214845_add_keyboard_auto_open_to_lessons_and_quiz_questions`, build frontend, lalu perbarui cache Laravel melalui alur deploy bagian 3. `bash deploy.sh` sudah mencakup langkah tersebut. Jika deploy dikerjakan manual, jalankan dari folder aplikasi setelah dependensi pada bagian 3 tersedia:

```bash
cd /www/wwwroot/artaswaha.kerthajaya.com
php artisan migrate --force
npm run build
php artisan optimize
```

Gunakan PHP 8.4 seperti pada bagian 1. Migrasi menambahkan boolean `keyboard_auto_open` pada `lessons` dan `quiz_questions`, dengan nilai awal `false` untuk data lama. Tidak perlu menjalankan seeder. Perubahan keyboard ini tidak memerlukan restart worker TTS, worker antrean lain, atau Reverb.

Pengaturan dan pemeriksaan setelah deploy:

1. **Admin → Modul → Buat/Ubah modul:** pilih kolom materi, pertanyaan latihan, pilihan, atau penjelasan, lalu buka **Keyboard aksara Bali**. Pastikan karakter masuk ke kolom yang dipilih dan tetap sama setelah disimpan/muat ulang. Pengaturan buka otomatis untuk peserta tidak lagi ditampilkan pada modul pilihan ganda. Perubahan modul tetap mengikuti alur simpan draf → preview → terbitkan.
2. **Buat/Edit kuis:** keyboard tetap tersedia untuk menulis pertanyaan dan pilihan pada kolom editor yang dipilih. Uji juga soal dan pilihan yang ditambah/dihapus melalui editor. Kuis yang sudah memiliki room tetap membuat versi baru ketika diedit; room lama mempertahankan pengaturannya.
3. **Pengguna:** keyboard tidak tampil pada materi, soal pilihan ganda/pilihan banyak, preview pilihan, atau setelah pelajaran selesai, termasuk data lama dengan `keyboard_auto_open=true`. Keyboard jawaban tersedia hanya untuk soal esai; lihat bagian tipe soal di bawah. Kolom database lama dipertahankan tanpa migrasi penghapusan.
4. **Input dan HP:** uji aksara dasar, pangangge, gantungan, adeg-adeg manual tanpa duplikasi, sisip/ganti seleksi, hapus, spasi, enter, serta pindah kolom. Periksa lebar 320–390 px, orientasi, ketukan berulang, scroll panjang dan berhenti, serta pergantian keyboard perangkat. Memakai keyboard tidak menambahkan request per karakter.

Tes PHP modul/kuis, tes JavaScript keyboard, dan build frontend telah lolos secara lokal. Rendering gabungan aksara, gestur, dan perilaku keyboard perangkat pada browser/HP sebenarnya masih perlu diperiksa; pengujian otomatis tersebut bukan verifikasi visual atau jaminan kelancaran semua perangkat.

## Kelola kuis: penyimpanan soal, audio, dan arsip

Editor mengirim ID soal tersimpan agar perubahan kuis tanpa room mempertahankan soal dan audionya. Perubahan pertanyaan/pilihan menandai audio `stale`; perubahan urutan, kunci jawaban, penjelasan, atau keyboard tidak mewajibkan generate ulang. Soal yang dihapus dibersihkan bersama file audionya setelah transaksi berhasil.

Kuis yang sudah memiliki room tetap membuat versi baru ketika diedit. Audio `ready`/`review` dengan hash sumber yang cocok dan file yang tersedia disalin ke file privat baru untuk soal yang dipertahankan. Duplikasi kuis memakai aturan sama dan tidak menyalin room/jawaban. Audio kedaluwarsa atau masih diproses tidak disalin; generate tetap melalui alur audio admin. Pengarsipan manual menyembunyikan kuis dari daftar aktif dan melarang room baru; riwayat room tetap tersedia. Untuk memakai arsip lagi, buat salinan agar versi lama tidak diubah.

Pembaruan ini tidak menambah migration, dependency, atau environment. Gunakan alur deploy bagian 3 untuk memuat route dan frontend baru. Tidak perlu restart TTS/Reverb untuk perubahan pengelolaan kuis ini.

Uji setelah deploy: ubah judul/urutan dan pastikan audio lama tetap dapat diputar; ubah teks/pilihan dan pastikan audio lama tidak diputar peserta; edit kuis yang sudah punya room dan periksa riwayat/audio versi lama; duplikasikan kuis dan cek keyboard/audio salinan; arsipkan kuis dan cari pada filter arsip. Aksi dan ID soal harus ditolak jika berasal dari kuis milik pengguna lain.

Alur editor sekarang **Pengaturan → Kelola soal → Preview & simpan → Detail kuis → Buat room**. Menu **Kelola kuis** pada admin menuju koleksi kuis milik akun tersebut; akses tidak diperluas ke kuis milik akun lain. Daftar menyediakan pencarian judul, filter aktif/arsip, edit, duplikasi, dan arsip dengan konfirmasi. Detail kuis menampilkan pilihan/kunci jawaban khusus pemilik, pengaturan keyboard, status audio, serta akses preview peserta.

Pada **Kelola soal**, soal dapat dilipat, diduplikasikan, dipindah naik/turun, dan dihapus. Penomoran ulang mempertahankan jawaban benar. Duplikasi satu soal menyalin isi/kunci/keyboard sebagai soal baru tanpa ID atau audio lama; simpan dahulu untuk menyiapkan audio sendiri. Duplikasi seluruh kuis mengikuti aturan salinan audio di atas. Batas tetap 20 soal dan 2–5 pilihan per soal.

Keyboard editor mengisi kolom terakhir yang dipilih. Preview pilihan ganda tidak menampilkan keyboard jawaban. Status dan tombol **Kelola audio soal** berada pada soal tersimpan; pengaturan/generate/unggah/persetujuan audio tetap khusus admin. Simpan perubahan sebelum membuka pengaturan audio. Preview hanya memutar audio `ready` yang disetujui dan cocok dengan pertanyaan/pilihan saat ini; teks yang berubah tidak memakai rekaman lama. Preview tidak menjalankan inferensi, timer, penilaian, atau pengiriman jawaban. Audio berhenti saat pindah tahap/soal atau meninggalkan/menyembunyikan halaman.

Editor menampilkan validasi di dekat kolom, membuka soal yang perlu dilengkapi, memperingatkan ketika meninggalkan perubahan belum tersimpan, dan mengunci tombol simpan selama pengiriman. Peringatan keluar memakai mekanisme browser dan memerlukan interaksi pengguna. Tidak ada autosave atau penyimpanan setiap karakter. Modul JavaScript editor dimuat hanya pada halaman editor; tidak ada dependency baru.

Sesudah deploy/build, muat ulang halaman editor lama sebelum mengedit. Uji ketiga tahap, validasi soal yang dilipat, duplikasi/pindah/hapus pilihan tanpa perubahan kunci yang tidak disengaja, keyboard pada kolom editor dan preview audio, batal/konfirmasi arsip, pencarian/filter/pagination, serta tombol kembali setelah menyimpan. Uji pada HP 320–390 px dan orientasi berbeda, termasuk scroll panjang, fokus input, dan posisi panel keyboard. Tes aplikasi dan JavaScript lokal tidak menggantikan pemeriksaan visual/gestur pada browser sebenarnya; browser pengujian tidak tersedia dalam sesi implementasi.

## Durasi khusus soal dan batas total kuis

Sesudah pull, jalankan migrasi `2026_10_27_101856_add_question_and_total_time_limits_to_quizzes`, build frontend, dan perbarui cache melalui alur deploy bagian 3 atau `bash deploy.sh`. Migrasi menambahkan `time_limit_seconds` nullable pada `quizzes`, `quiz_questions`, dan `rooms`, serta `rooms.ends_at`. Data lama tetap mengikuti durasi bawaan per soal tanpa batas total. Tidak ada environment, dependency, seeder, atau service baru; perubahan timer ini tidak memerlukan restart worker TTS/Reverb.

- **Pengaturan kuis:** waktu bawaan per soal tetap 5–300 detik. Batas total opsional 1–180 menit dimulai ketika host menekan **Mulai kuis**, bukan ketika room dibuat, dan berlaku bersama untuk seluruh peserta.
- **Kelola soal:** waktu khusus 5–300 detik menggantikan bawaan untuk soal tersebut. Jika dikosongkan, soal mengikuti bawaan; jika keduanya kosong, soal tidak mempunyai batas waktu sendiri. Pengaturan ikut dalam preview, duplikasi soal/kuis, dan versi baru. Mengubah durasi saja tidak menandai audio kedaluwarsa.
- **Peserta:** waktu soal dan total diperlihatkan terpisah. Batas total tidak diperpanjang oleh jawaban, jeda feedback, pemutaran audio, atau reload. Saat batas total habis, server menutup kuis dan menolak jawaban baru; jawaban yang sudah dikirim tetap tersimpan. Audio berhenti pada batas yang lebih awal. Soal tanpa timer sendiri tetap tunduk pada batas total.

Countdown memakai waktu server dan satu interval browser per detik untuk kedua jam, dijeda ketika tab tersembunyi. Permintaan pemeriksaan saat habis dibatasi paling sering tiap tiga detik dan tidak bertumpuk. Server memeriksa batas pada akses/status/jawaban/heartbeat. Scheduler room yang sudah berjalan juga menutup room kedaluwarsa setiap menit; tanpa akses/browser aktif, pencatatan status dapat menunggu tick cron, tetapi waktu selesai tetap batas `ends_at` dan jawaban terlambat tetap ditolak. Pertahankan cron `schedule:run` yang sudah ada; jangan membuat cron/worker duplikat.

QA: uji bawaan saja, override saja, campuran soal bertimer/tanpa timer, total saja, kedua batas bersamaan, total habis ketika feedback/audio berjalan, serta reload atau kembali dari tab tersembunyi. Pastikan durasi soal berikutnya memakai nilai soal itu dan timeout beberapa soal dihitung dari batas sebelumnya, bukan diulang dari waktu reload. Periksa penolakan jawaban tepat pada batas, pelestarian jawaban lama, heartbeat melaporkan `finished` saat waktu habis, dan room lama tetap memakai pengaturan versi lama setelah edit. Uji isian kosong, batas minimum/maksimum, angka pecahan, dan nilai di luar batas. Verifikasi visual/gestur pada browser dan HP sebenarnya tetap diperlukan.

## Instalasi manual langkah demi langkah

Bagian ini menjalankan langkah installer satu per satu agar prosesnya dapat dipahami. Pilih cara manual ini atau installer pada bagian 4. Contoh memakai CPU; jalankan setelah source baru dipull dan backup tersedia.

Masuk shell user worker yang sebenarnya, lalu tetapkan lokasi (sesuaikan user/folder):

```bash
sudo -u www /bin/bash
export APP_DIR=/www/wwwroot/artaswaha
export TTS_ROOT="$APP_DIR/storage/app/private/tools/chatterbox"
cd "$APP_DIR"
umask 027
mkdir -p "$TTS_ROOT/samples"
export UV_CACHE_DIR="$TTS_ROOT/cache/uv"
export UV_PYTHON_INSTALL_DIR="$TTS_ROOT/python"
export PYTHONUTF8=1
```

Siapkan alat instalasi dan Python 3.11 khusus proyek. Ini tidak mengganti Python sistem:

```bash
curl --fail --location --silent --show-error https://astral.sh/uv/install.sh --output "$TTS_ROOT/install-uv.sh"
UV_UNMANAGED_INSTALL="$TTS_ROOT/bin" sh "$TTS_ROOT/install-uv.sh"
"$TTS_ROOT/bin/uv" venv --python 3.11 --seed "$TTS_ROOT/venv"
export TTS_PY="$TTS_ROOT/venv/bin/python"
```

Pasang Torch CPU dan paket yang sudah disesuaikan. Setuptools dipin karena Librosa lama membutuhkan pkg_resources:

```bash
"$TTS_ROOT/bin/uv" pip install --python "$TTS_PY" torch==2.6.0+cpu torchaudio==2.6.0+cpu --index-url https://download.pytorch.org/whl/cpu
"$TTS_ROOT/bin/uv" pip install --python "$TTS_PY" --requirement "$APP_DIR/app/Services/tts_chatterbox_requirements.txt"
"$TTS_ROOT/bin/uv" pip check --python "$TTS_PY"
```

Download aset model sesuai revision yang dipin, lalu cek runtime dan asetnya:

```bash
"$TTS_PY" app/Services/tts_chatterbox.py download --model-dir "$TTS_ROOT/models"
"$TTS_PY" app/Services/tts_chatterbox.py check --model-dir "$TTS_ROOT/models" --device cpu
```

Uji satu audio offline. Langkah ini harus berhasil sebelum mengaktifkan TTS pada Laravel:

```bash
printf 'Selamat datang. Mari belajar bersama.\n' | timeout --kill-after=10 240 "$TTS_PY" app/Services/tts_chatterbox.py synthesize --model-dir "$TTS_ROOT/models" --device cpu -v id -s 150 -w "$TTS_ROOT/samples/indonesia.wav"
"$TTS_ROOT/bin/uv" pip freeze --python "$TTS_PY" > "$TTS_ROOT/requirements-installed.txt"
exit
```

Jika salah satu perintah gagal, perbaiki terlebih dahulu dan biarkan TTS_ENABLED=false. Setelah WAV berhasil dibuat dan diperiksa, lanjutkan konfigurasi Supervisor pada bagian 6, environment pada bagian 7, dan pengujian admin pada bagian 8. Ollama/Qwen tetap nonaktif.

## React Router, SSR, dan animasi

Seluruh halaman menggunakan React Router, termasuk publik, autentikasi, kuis, dan admin. Tidak ada `REACT_UI_ENABLED` atau mode Blade aktif; hapus baris lama tersebut jika masih ada di `.env`. Template Blade lama tetap menjadi referensi. Tidak ada migration atau seeder baru untuk cutover UI. Gunakan `composer install`, `npm ci`, `npm run build`, dan pembaruan cache Laravel pada alur bagian 3. Build sekarang menghasilkan `public/build` dan `bootstrap/ssr`; keduanya harus berada pada release yang sama. Jangan menghapus aset hash versi sebelumnya ketika masih ada tab pengguna terbuka; gunakan release/deployment aset yang mempertahankannya selama transisi.

Gunakan PHP 8.4 dan Node 22.12+ yang cocok dengan lockfile (baseline lokal Node 22.22.3). SSR aktif secara default dan memerlukan proses Node yang dikelola Supervisor. Pastikan user program dapat menjalankan `node --version` melalui PATH yang sama dengan program Supervisor; PATH shell interaktif belum tentu sama.

Contoh program **baru**, sesuaikan path dan user sebelum dipakai:

```ini
[program:artaswaha-ssr]
directory=/www/wwwroot/artaswaha
command=/usr/bin/node /www/wwwroot/artaswaha/bootstrap/ssr/ssr.js
user=www
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
stopwaitsecs=15
redirect_stderr=true
stdout_logfile=/www/wwwroot/artaswaha/storage/logs/react-ssr.log
```

Jangan menimpa konfigurasi Supervisor lain. Server SSR bind hanya ke `127.0.0.1:13714`; jangan membuka port tersebut ke publik. Jika PHP dan Node berada pada container berbeda, konfigurasi jaringan internal SSR perlu disesuaikan dahulu. `REACT_SSR_ENABLED=false` hanya opsi darurat untuk menonaktifkan SSR; UI tetap React, tetapi HTML konten publik awal tidak tersedia sampai JavaScript berjalan sehingga SEO menurun.

Setelah build/deploy, restart **hanya** program SSR ARTASWAHA agar memuat bundle terbaru. `deploy.sh` belum mengelola restart proses ini. Worker AI/TTS dan Reverb tetap program yang berbeda; restart hanya yang berubah sesuai panduan sebelumnya. Periksa dengan binary PHP yang sama:

```bash
curl --fail --max-time 3 http://127.0.0.1:13714/health
```

Jika health check gagal, periksa log program SSR, executable Node pada PATH program, akses file bundle, dan port loopback. Request SSR dibatasi 3 detik; fallback client menjaga aplikasi tetap dapat dibuka, tetapi bukan pengganti SSR publik yang sehat. Rollback memakai release source + build sebelumnya sebagai satu paket, bukan sakelar renderer atau rollback database.

QA setelah deploy: lihat source HTML awal beranda, katalog, pelajaran, dan privasi; pastikan heading/konten dan metadata sudah ada sebelum JavaScript. Periksa hydration/konsol, direct visit, refresh, Back/Forward, form validasi, login/logout, profil anonim, crop avatar, multi-select interview, editor kuis, feedback/timer, host reload/keluar, dan admin. PHP tetap memeriksa akses; props tidak boleh membawa jawaban masa depan, kunci tersembunyi, email peserta, atau path file privat.

Overlay muncul **setiap reload dokumen** pada semua halaman, menunggu bootstrap React/font dengan batas waktu, mencapai 100%, hold sekitar 450 ms, lalu fade. Persentase adalah kesiapan tampilan, bukan byte jaringan atau progres antrean AI. Navigasi React Router mempertahankan halaman lama selama data diambil dan tidak menampilkan progress bar atas, tanpa skeleton mengambang di atas halaman lama; submit jawaban, countdown, dan deadline tidak ditunda. Uji lewati, kegagalan chunk, reduced motion, reveal kartu di bawah layar, desktop/mobile, serta job queued/completed/failed. Skeleton analisis berhenti pada error/batas polling dan menampilkan status yang jelas.

Sebelum rilis: `php artisan test --compact`, `npm run test:react`, dan `npm run build`. Cakupan, hasil, serta batas pengujian yang masih perlu dilakukan tercatat di `docs/REACT.md`. UI Blade lama dan initializer DOM-nya sudah dihapus; entry build tetap React. Pertahankan root Blade, komponen `react-loader`, dan sitemap XML. Tes helper edit aksara tetap berjalan dengan `node --test tests/aksara-keyboard.test.js`; tes initializer Blade dan tes worker dengan target skrip yang sudah tidak tersedia telah dihapus.

Interview awal kini wajib bagi pengunjung tanpa akun login/profil anonim. Kunjungan beranda, materi, kuis, dan pendaftaran baru diarahkan oleh middleware ke halaman interview penuh setelah loader. Login, pemulihan, privasi, robots, dan sitemap tetap terbuka. Uji resume langkah, banyak pilihan materi, mode anonim/akun, cookie hilang, serta kembali ke URL tujuan semula. Akun yang sudah login tetap langsung masuk. Tidak ada migrasi atau konfigurasi tambahan; konten yang dilindungi ini tidak lagi tersedia langsung bagi pengunjung baru/crawler tanpa identitas.

Login email, Google, dan setelah registrasi kini selalu memakai cookie remember Laravel, tanpa checkbox. Sesi singkat tetap memakai konfigurasi yang ada; tidak perlu memperbesar `SESSION_LIFETIME`. Cookie remember bawaan sekitar 400 hari, tetap dapat berakhir karena logout/reset sandi/penghapusan akun atau pembersihan browser. Pengguna yang masuk sebelum perubahan perlu login ulang sekali untuk menerima cookie remember baru. Uji pemulihan akun setelah sesi hilang dan pastikan middleware interview mengenalinya; tombol Keluar harus tetap membatalkan token.

Urutan perkenalan: pilihan anonim/akun → materi (bisa banyak) → peran → nama/avatar untuk anonim. Pilihan Masuk langsung membuka login. Pilihan Daftar melewati materi dan peran terlebih dahulu, kemudian membuka formulir pendaftaran. Mengganti mode mengosongkan jawaban langkah berikutnya. Profil yang sudah selesai tetap tidak mengulang interview.

## Materi awal Aksara Bali

Untuk menambahkan lima modul Aksara Bali beserta satu latihan pilihan ganda pada masing-masing modul, jalankan seeder khusus setelah migrasi aplikasi tersedia:

```bash
php artisan db:seed --class=AksaraBaliLessonSeeder --force --no-interaction
```

Seeder mencakup Wreastra/gantungan/gempelan, pengangge suara, tengenan, ardasuara, dan angka Bali. Modul baru langsung terbit dan memakai alur materi → latihan → progres yang sudah ada; tidak membuat kuis room, audio, atau panggilan AI. Nol memakai ᭐, pepet memakai ᭂ, dan tanda vokal disimpan setelah aksara dasar menurut urutan Unicode. Peninjauan pengajar tetap diperlukan untuk ragam tutur serta contoh kata.

Perintah ini tidak menjadi bagian dari seeder default agar deploy tidak otomatis menambahkan materi. Aman dijalankan ulang: slug yang sudah ada dipertahankan, termasuk suntingan admin, status arsip, ID pelajaran, dan progres. Karena itu, perubahan berikutnya pada isi seeder tidak memperbarui modul yang sudah terpasang; gunakan editor admin untuk menyuntingnya. Jalur yang sudah diarsipkan juga tidak diterbitkan ulang. Tidak ada migrasi, dependency, build frontend, atau restart worker tambahan untuk seeder ini.

## Tipe soal esai dan pilihan banyak

Jalankan `php artisan migrate --force --no-interaction`, build client/SSR dengan `npm run build`, lalu lakukan pembaruan cache dan restart SSR melalui alur deploy React di atas. Migrasi `2026_10_27_230437_add_question_types_to_learning_and_quizzes` menambahkan tipe, kunci pilihan banyak, alternatif jawaban esai, serta jawaban teks/daftar pilihan peserta. Soal dan jawaban lama tetap bertipe `single`; ID, progres, room aktif, timer, serta skor lama dipertahankan. Tidak ada paket, environment, worker, atau panggilan AI baru.

Pada editor modul dan kuis, pilih **Pilihan ganda**, **Pilihan banyak**, atau **Esai**. Modul tetap memakai empat opsi untuk tipe pilihan, sedangkan kuis memakai dua sampai lima. Checkbox kunci harus mencakup semua jawaban benar; penilaian pilihan banyak memakai kecocokan himpunan penuh tanpa poin parsial dan mengabaikan urutan klik. Esai menerima maksimal sepuluh jawaban acuan, satu per baris, masing-masing maksimal 2.000 karakter. Jawaban peserta maksimal 2.000 karakter; pencocokan merapikan kapital, spasi berlebih, dan normalisasi Unicode NFC, tanpa menghapus tanda aksara. Ini bukan penilaian semantik atau koreksi otomatis oleh AI. Hindari soal esai terbuka yang membutuhkan penilaian manusia; gunakan isian dengan jawaban acuan yang jelas.

Keyboard jawaban hanya tersedia pada esai dan mengisi kolom jawaban sebenarnya. Keyboard editor tetap dapat dipakai untuk menulis materi/soal. Preview tidak mewajibkan jawaban sebelum menyimpan. Kunci baru tidak dikirim ke peserta sebelum feedback diizinkan; hasil akhir mengikuti aturan pengungkapan yang sudah ada. Teks esai room disimpan bersama jawaban dan ikut terhapus ketika room/akun terkait dihapus; analisis AI tetap menerima ringkasan ketepatan, bukan teks esai mentah. Pengeditan kuis yang sudah memiliki room tetap membuat versi baru, dan duplikasi menyertakan tipe/kuncinya.

Uji ketiga tipe pada modul, room bertimer/tanpa timer, feedback aktif/nonaktif, jawaban kosong/terlalu panjang, checkbox kurang/lebih/duplikat, variasi kapital/spasi/Unicode, edit/duplikasi/pergantian tipe, serta keyboard HP. Jangan rollback ke versi lama setelah membuat soal tipe baru: migrasi menolak rollback selama esai/pilihan banyak masih ada agar kunci dan jawaban tidak hilang. Cadangkan dan konversi data terlebih dahulu jika rollback diperlukan.

## Analisis pelajar: hasil awal, pembaruan, dan diagnosis admin

Deploy perubahan ini dengan migrasi `2026_10_28_002814_add_analysis_events_to_learner_profiles`, build client/SSR, pembaruan cache konfigurasi, dan restart **worker sesuai QUEUE_CONNECTION serta SSR**. Tidak ada dependency, installer Python, model, atau environment baru. Gunakan binary PHP dan direktori release yang sama dengan Supervisor:

```bash
php artisan migrate --force --no-interaction
npm run build
php artisan config:cache
php artisan route:cache
php artisan queue:restart
php artisan schedule:list --no-interaction
```

Restart program SSR ARTASWAHA melalui Supervisor sesuai bagian React. Pastikan worker membaca koneksi QUEUE_CONNECTION dan antrean defaultnya. Gunakan perintah bawaan `php artisan queue:work --sleep=3 --tries=3 --timeout=240 --max-time=3600` untuk seluruh pekerjaan aplikasi; lihat bagian berikut. Supervisor harus `autorestart=true`. Pesan `Maximum run time exceeded` setiap sekitar satu jam sesuai `--max-time=3600` bukan dengan sendirinya error model; pastikan proses kembali RUNNING. Job analisis mempunyai timeout sendiri 120 detik dan HTTP timeout 90 detik.

**Worker tidak menggantikan scheduler.** Pertahankan satu cron `* * * * * cd /direktori/aplikasi && /path/php artisan schedule:run >> /dev/null 2>&1` dengan user yang benar; jangan membuat duplikat cron yang sudah ada. Scheduler memeriksa tiap jam dan hanya mengantrekan profil yang jatuh tempo setelah enam jam sejak hasil berhasil. Membuka profil juga dapat mengantrekan analisis pertama atau yang jatuh tempo. Untuk memulai pemeriksaan segera setelah deploy, jalankan `php artisan learning:analyze --no-interaction` sekali; perintah ini tidak memanggil model secara sinkron. Pastikan konfigurasi AI_API_URL/AI_MODEL yang telah di-cache dapat diakses dari lingkungan worker; alamat 127.0.0.1 menunjuk lingkungan worker sendiri. Restart worker setelah perubahan konfigurasi. Referensi: [Laravel scheduler](https://laravel.com/docs/13.x/scheduling#running-the-scheduler), [worker dan deploy](https://laravel.com/docs/13.x/queues#queue-workers-and-deployment).

Sebelum hasil AI pertama tersedia, profil menampilkan ringkasan faktual awal dengan label **belum merupakan hasil AI**. Hasil AI yang sudah berhasil tetap tampil selama antrean, pemrosesan, penundaan, atau kegagalan pembaruan. Perubahan jawaban interview atau konfigurasi model membatalkan kecocokan hasil lama. Pembaruan waktu pada kartu tetap menunjukkan keberhasilan terakhir, bukan waktu masuk antrean. Hasil yang telah terhapus oleh kode versi lama tidak dapat dipulihkan otomatis; jalankan tes ulang.

Buka **Admin → Pusat AI → Analisis interview** atau **Status layanan**. Tombol **Tes semua pelajar** mengantrekan profil anonim dan profil interview terbaru tiap akun, dipecah menjadi 100 profil per pekerjaan, tanpa mengikuti filter daftar. Tes langsung masuk antrean tanpa menunggu pembaruan otomatis enam jam maupun jeda pengiriman ulang dua menit. Permintaan aktif di bawah 30 menit tetap tidak digandakan. Worker harus aktif; tes diproses bergiliran sesuai antrean, bukan dijalankan sinkron di request admin. Akun yang tidak memiliki profil interview tidak dibuatkan data/minat rekaan. Tes massal memakai kuota/biaya penyedia AI; permintaan model tetap mengikuti pembatas antrean yang ada. Membuka halaman admin tidak menjalankan AI.

Daftar analisis tanpa filter tanggal mencakup seluruh profil; jika tanggal diterapkan, filter mengikuti waktu permintaan analisis, bukan tanggal pembuatan akun. Detail pelajar menampilkan 20 kejadian terakhir: antrean, worker mulai, sukses beserta hasil yang divalidasi, error HTTP, kegagalan format, atau konfigurasi usang. Catatan disimpan pada profil dan terhapus bersama profil. Riwayat sebelum migrasi tidak direkonstruksi. Respons mentah dan API key tidak disalin ke riwayat; informasi provider tambahan tetap pada log Laravel. Polling detail dibatasi; muat ulang setelah pemeriksaan otomatis dijeda. Status error permanen pada worker nyata sekarang ditandai failed, sehingga tidak lagi dilaporkan DONE hanya karena validasi ditangani.

Waktu pemeriksaan berkala dan worker terakhir pada admin adalah riwayat eksekusi dalam cache bersama, **bukan status online proses**; cache yang dibersihkan akan mengosongkan indikator sampai eksekusi berikutnya. Bedakan `queued` (belum diambil), `processing` (worker mulai), dan `completed` (hasil divalidasi/disimpan). Untuk pekerjaan yang dilewati, cari `Learner analysis skipped` di log Laravel; `DONE` saja tidak membuktikan inferensi berhasil. Uji hasil pertama, kegagalan provider, pelestarian hasil lama, tes ulang individual/massal, serta cron setelah deploy. Verifikasi production harus memakai tes admin pada server sebenarnya; tes lokal menggunakan respons AI tiruan.


## Satu worker bawaan yang mengikuti environment

Command tambahan `php artisan queue` telah dihapus. Semua job baru mengikuti `QUEUE_CONNECTION` dan antrean default pada konfigurasi koneksi tersebut. Gunakan command Laravel bawaan tanpa argumen koneksi agar mengikuti .env:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=240 --max-time=3600
```

Contoh `QUEUE_CONNECTION=redis` memakai Redis/REDIS_QUEUE; `QUEUE_CONNECTION=database` memakai database/DB_QUEUE. Keduanya memakai antrean `default` bila nama antrean tidak diatur. Jika ingin memaksa Redis terlepas dari QUEUE_CONNECTION, command ini juga didukung:

```bash
php artisan queue:work redis --sleep=3 --tries=3 --timeout=240 --max-time=3600
```

Argumen koneksi eksplisit **harus cocok dengan tujuan job**. Jangan memakai command `redis` bila .env mengirim ke `database`. `QUEUE_CONNECTION=sync` mengikuti perilaku Laravel: tugas berjalan pada request dan tidak masuk worker. Untuk pemrosesan background production gunakan Redis atau database. Setelah mengubah .env, jalankan `php artisan config:cache`, lalu restart program worker yang benar melalui Supervisor agar command serta environment yang baru dipakai. Job lama yang sudah terserialisasi mempertahankan koneksi/antreannya; perubahan .env tidak memindahkannya.

Job audio tetap memiliki timeout 1830 detik karena inferensi dapat memerlukan 1800 detik. **Timeout job mengesampingkan `--timeout=240` pada command**; audio tidak dipotong menjadi empat menit. Redis dan database memakai `retry_after` minimum 1860 detik (env yang lebih tinggi tetap dihormati), sehingga satu audio tidak diambil ulang sebelum batas prosesnya. Job AI 120 detik dan draf kuis 240 detik tetap memakai batas masing-masing. Pertahankan lock/cache bersama, dan set Supervisor `stopwaitsecs=1860`, `stopasgroup=true`, `killasgroup=true`, `autorestart=true`, `autostart=true`, serta `numprocs=1` pada konfigurasi RAM saat ini. Satu worker mengerjakan satu tugas pada satu waktu, sehingga audio panjang dapat menunda analisis. `--max-time=3600` menyebabkan keluar berkala secara normal dan memerlukan autorestart.

Sebelum menonaktifkan worker database `learner-analysis` lama, habiskan pekerjaan yang masih ada dengan program lamanya atau perintah berikut dari folder release yang baru:

```bash
php artisan queue:work learner-analysis --queue=learner-analysis,default --stop-when-empty --sleep=3 --tries=3 --timeout=1830
```

Jalankan sekali untuk antrean lama yang memang ada, jangan buat program permanen tambahan. Pertahankan worker backend lain yang masih mempunyai pekerjaan hingga habis; jangan memakai queue:clear/flush atau menghapus jobs. Setelah antrean lama kosong dan worker utama RUNNING, nonaktifkan program duplikat. Tidak ada migrasi antrean otomatis, perubahan .env lokal, dependency, atau installer Python pada penyesuaian ini.

`deploy.sh` menerima worker tanpa argumen koneksi atau dengan koneksi yang cocok .env dan memeriksa working directory. Jika memakai pemeriksaan nama Supervisor, arahkan QUEUE_SUPERVISOR_PROGRAM dan LEARNER_QUEUE_SUPERVISOR_PROGRAM ke group worker yang sama yang dipertahankan. Tidak ada penyuntingan Supervisor production otomatis. Status antrean di admin dibaca dari koneksi aktif, bukan tabel jobs yang lama; kegagalan koneksi menampilkan status tidak tersedia tanpa membocorkan kredensial. Reverb, SSR, dan cron scheduler tetap proses terpisah. Tes memakai database SQLite sebagai backend antrean lokal; koneksi Redis nyata di production perlu diperiksa setelah deploy.


### Diagnosis worker tidak ditemukan di aaPanel

Pengecekan tidak lagi mengharuskan cwd worker sama dengan direktori aplikasi jika command memakai path absolut ke artisan. Bila nama program tidak diisi, skrip juga tidak mengasumsikan group `artaswaha-queue:*`; ia memeriksa proses aplikasi. QUEUE_SUPERVISOR_PROGRAM dibaca dari environment shell atau .env, dan menjadi fallback bagi LEARNER_QUEUE_SUPERVISOR_PROGRAM untuk worker bersama. Nama yang diisi secara eksplisit tetap diperiksa dan tidak diam-diam diabaikan jika statusnya gagal.

Pesan kegagalan menyebut koneksi yang dibutuhkan, jumlah kandidat, jumlah proses yang path aplikasinya cocok, serta peringatan jika argumen proses tidak dapat dibaca. Koneksi yang berbeda dilaporkan per PID tanpa menampilkan argumen proses lainnya. Pastikan program Supervisor benar-benar RUNNING, path artisan mengarah ke instalasi ini, koneksinya sesuai QUEUE_CONNECTION, dan user deploy mempunyai izin membaca /proc. Contoh command aaPanel untuk instalasi ini:

```bash
/www/server/php/84/bin/php /www/wwwroot/artaswaha.kerthajaya.com/artisan queue:work --sleep=3 --tries=3 --timeout=240 --max-time=3600
```

Sesuaikan lokasi binary PHP dengan instalasi sebenarnya. Skrip tidak otomatis membuat, menjalankan, atau restart worker. Jika deploy lama terhenti sebelum menarik source karena preflight worker, perbarui source/deploy.sh di server terlebih dahulu sebelum menjalankannya lagi; menjalankan ulang file lama tidak memuat perbaikan. Tidak perlu migration, build frontend, atau installer untuk perubahan pengecekan Bash ini. Tes deploy menggunakan proses/aaPanel tiruan, bukan akses ke Supervisor production.


## Interview: masuk melalui halaman login

Kartu akun pada langkah awal hanya menyediakan tombol **Masuk / daftar** menuju halaman login. Pilihan daftar dan Google tersedia di halaman login. Login tidak menanyakan ulang materi/peran; akun email dan Google yang sudah ada tetap langsung masuk meskipun tidak memiliki profil interview lama. Tautan **Ayo daftar** dari halaman login memulai pertanyaan materi dan peran, lalu diarahkan ke `/daftar`, bukan `/masuk`. Profil anonim tetap menyelesaikan materi/peran/nama-avatar; profil yang sudah selesai tidak mengulang interview saat ditautkan ke akun.

Google mengidentifikasi akun melalui callback Socialite dengan verifikasi state dan email yang sudah ada. Akun Google/email yang sudah terdaftar langsung masuk. Untuk identitas Google baru tanpa profil interview perangkat, server menyimpan ID, nama, dan email terverifikasi dalam sesi pendaftaran sementara (maksimal satu jam), tanpa menyalin access/refresh token. Belum ada akun dibuat pada tahap ini. Setelah materi/peran selesai, server menyelesaikan pendaftaran Google dan menautkan jawaban interview melalui cookie perangkat. Konfirmasi yang kedaluwarsa meminta Google kembali; jawaban yang sudah disimpan tidak perlu diisi ulang. Masuk/daftar yang berhasil membersihkan sesi interview/Google tertunda. Memilih ulang mode awal juga membersihkan konfirmasi Google sebelumnya.

Tidak ada migration, dependency, environment, atau worker baru. Deploy source, build client/SSR, dan restart SSR sesuai alur React sebelumnya. Uji login email, login Google akun lama, daftar email dari interview maupun tautan login, Google baru dari halaman login, Google setelah interview, state/email tidak valid, konfirmasi kedaluwarsa, serta pemulihan halaman tujuan. Tes lokal memakai Socialite fake; popup/redirect dan callback Google sebenarnya tetap perlu diperiksa pada domain production dengan konfigurasi OAuth yang benar.

### Diagnosis analisis berhenti pada queued

Worker kini mencatat `Learner analysis job received by worker.` sebelum middleware lock/rate-limit, dengan ID job, koneksi, antrean, dan nomor percobaan tanpa payload pribadi. Jika middleware mengembalikan job ke antrean, muncul `Learner analysis job released back to queue.`. Pesan ini bukan error respons AI: tanpa `started`, pemanggilan model belum dimulai. Lock bersama `learner-llm` juga dipakai audio dan laporan AI; audio yang sedang diproses dapat menunda analisis. Log release tidak membedakan lock dan rate-limit; periksa proses audio/laporan serta keluaran worker. Jika tidak ada log received, periksa proses worker, queue yang dibaca, dan versi source yang dimuat. Log queued saja tidak membuktikan worker telah mengambil pekerjaan.

Deploy source lalu restart worker melalui `php artisan queue:restart` dan pastikan Supervisor menjalankannya kembali. Tidak ada migration, build frontend, atau perubahan env untuk penambahan log ini. Worker `--once` hanya mengambil satu pekerjaan, yang mungkin berupa koordinator tes massal; gunakan worker berkelanjutan agar pekerjaan analisis turunannya ikut diproses. Tidak perlu menghapus queue atau cache untuk diagnosis ini. Referensi event: https://laravel.com/docs/13.x/queues#job-events.

### Hash input analisis pada MySQL JSON

Hash analisis sekarang mengurutkan key object JSON secara rekursif sebelum hashing, tanpa mengubah urutan list (materi pilihan dan aktivitas harian). MySQL dapat mengubah urutan key saat menyimpan JSON; sebelumnya ini membuat input yang sama dianggap `outdated`, job selesai cepat tanpa meminta AI dan tanpa failed job. Kondisi outdated sekarang juga dicatat di log Laravel, dengan tahap `before_request` atau `after_response` tanpa isi data pribadi.

Tidak ada migration atau build frontend. Setelah deploy, jalankan `php artisan queue:restart`, pastikan worker dimulai kembali oleh Supervisor, lalu gunakan **Tes semua pelajar** di admin untuk membentuk ulang hash lama dan mengantrekan analisis. Hasil yang memakai hash lama mungkin dianggap usang dan dibuat ulang; jawaban interview serta progres tetap dipertahankan. Tes regresi menyimulasikan perubahan urutan JSON di database; MySQL production dan koneksi AI nyata tetap perlu diperiksa melalui hasil tes admin.

## Modul Aksara Bali bertahap dan penguncian urutan

Materi pelajaran React kini dibagi menjadi bagian bacaan dan contoh aksara berukuran besar, diselingi latihan per bagian. Bank latihan disimpan pada kolom JSON `lessons.exercises`; editor dan preview admin mendukung pilihan tunggal, pilihan banyak, serta esai dengan daftar jawaban yang diterima. Kunci jawaban soal berikutnya tidak dikirim ke pelajar. Esai dinilai dengan normalisasi dan pencocokan jawaban yang ditetapkan pengelola, bukan penilaian prosa oleh AI. Keyboard esai juga menyediakan angka Bali dan tanda carik.

Jalankan setelah source diperbarui, memakai binary PHP dan direktori aplikasi production yang benar:

```bash
php artisan migrate --force
php artisan db:seed --class=AksaraBaliLessonSeeder --force
npm run build
```

Restart SSR mengikuti panduan React di atas. Tidak ada dependency, env, worker, atau installer Python baru. Migration menambahkan kolom bank latihan; rollback migration menghapus bank latihan tersebut, sehingga backup database tetap diperlukan.

Seeder menyediakan **10 modul baru dengan 125 soal**: lima modul berisi 10 soal dan lima modul berisi 15 soal. Modul meliputi Wreastra, pengangge suara, tengenan, gantungan/gempelan, ardasuara, dan angka Bali. Materi dan latihan ditulis ulang berdasarkan rujukan Dinas Kebudayaan Buleleng, Unicode, dan contoh bentuk asesmen penelitian Undiksha; sumber dicantumkan pada masing-masing modul. Seeder memakai `firstOrCreate`, sehingga tidak menimpa materi suntingan admin, menghapus modul lama, atau mereset progres. Pada database lama, sepuluh modul ini ditambahkan ke modul yang sudah ada; jumlah total dapat lebih dari sepuluh. Jangan memakai `migrate:fresh` untuk memperbarui production. Modul lama yang tidak lagi ingin ditampilkan dapat diarsipkan melalui admin.

Urutan modul dalam setiap kategori mengikuti `sort_order`, lalu ID bila urutannya sama. Semua modul terbit yang sudah ditinjau sebelumnya harus selesai sebelum modul berikutnya terbuka. Draf tidak menghalangi urutan, dan kategori lain tetap dapat dimulai secara mandiri. Modul terkunci ditampilkan tanpa tautan; server juga memeriksa halaman langsung, pengiriman jawaban, penyelesaian modul, serta audio pelajaran. Progres akun dikenali berdasarkan akun lintas perangkat, sedangkan anonim dikenali berdasarkan cookie perangkat. Perubahan urutan atau penerbitan modul sebelumnya dapat membuat prasyarat baru bagi modul setelahnya.

Progres soal kini disimpan di tabel percobaan, sesuai pembaruan berikut. Penyelesaian modul dan 10 poin disimpan setelah semua soal dijawab, termasuk jawaban salah. Progres selesai yang sudah ada tetap dipertahankan. Uji bacaan, jawaban salah/ulang, esai dengan keyboard, pilihan banyak, penyelesaian soal terakhir, dan pembukaan modul berikutnya pada desktop serta mobile. Tes otomatis mencakup bank soal, akses terkunci, editor admin, dan komponen React; tampilan browser/perangkat nyata tetap perlu diperiksa setelah deploy.

### Perbaikan validasi jawaban dari form React

Form jawaban mengirim seluruh field, termasuk `answer_indexes: []` saat menjawab pilihan tunggal atau esai. Validasi awal pelajaran dan room kini menerima daftar kosong tersebut; `QuestionAnswerService` tetap memvalidasi field yang wajib sesuai tipe soal, termasuk minimal satu pilihan untuk soal pilihan banyak. Perbaikan ini mencakup latihan lama dan bank latihan baru. Deploy source PHP saja; tidak perlu migration, seeder, build frontend, atau perubahan env. Tes regresi mengirim payload lengkap seperti form React untuk ketiga tipe soal.

### Navigasi modul berikutnya

Setelah modul selesai, halaman pelajaran menyediakan tombol **Modul berikutnya** beserta judulnya bila masih ada modul terbit dan sudah ditinjau dalam kategori yang sama. Urutan mengikuti `sort_order` lalu ID, sama dengan daftar dan penguncian modul. Tombol tidak muncul sebelum selesai atau pada modul terakhir. Berlaku untuk latihan tunggal lama maupun bank soal baru. Deploy source, build client/SSR, lalu restart SSR sesuai panduan; tidak ada migration, seed ulang, atau env baru.

## Seeder modul Paribasa Bali

`ParibasaLessonSeeder` menambahkan 6 modul untuk setiap jalur `cecimpedan`, `peparikan`, dan `sesonggan`: total 18 modul dan 180 soal. Setiap modul memiliki 8 pilihan tunggal, 1 esai jawaban singkat, dan 1 pilihan banyak. Sepuluh modul menyediakan materi dan delapan modul berupa latihan langsung. Modul tanpa materi tetap menyimpan bagian sumber; halaman React langsung menampilkan soal pertama tanpa bacaan kosong atau tombol kembali ke materi yang tidak ada. Esai Paribasa menggunakan teks Latin dan tidak otomatis membuka keyboard aksara.

Rujukan awal adalah tiga dokumen Scribd dari pengguna: `Paribasa-Basa-Bali` (294471338), `Bahasa-Bali` (399384717), dan `CECIMPEDAN` (645712970), dengan pembanding *Kasusastran Bali miwah Basita Paribasa Bali* oleh I Nyoman Suwija pada repositori Mahadewa (2025). URL rujukan tersedia pada tiap modul. Petunjuk, skenario, dan pembahasan ditulis ulang; bank ini bukan salinan keseluruhan dokumen. Perbedaan ejaan/jawaban tradisi lisan dijelaskan pada sumber. Nama menu menggunakan Sesonggan; materi tetap membedakan istilah sesonggan dan sesenggakan. Penjelasan memakai Bahasa Indonesia dengan istilah/contoh Bali. Penelaahan kebahasaan oleh pengajar tetap disarankan sebelum digunakan sebagai asesmen resmi.

Setelah source diperbarui dan migration bank latihan sebelumnya sudah dijalankan:

```bash
php artisan db:seed --class=ParibasaLessonSeeder --force
npm run build
```

Restart SSR sesuai prosedur React sebelumnya. Tidak ada migration, env, dependency, worker, atau installer Python baru untuk perubahan ini. Seeder dijalankan terpisah dari `DatabaseSeeder`, sehingga tidak otomatis mengubah deployment yang hanya memerlukan jalur belajar/admin. `firstOrCreate` menjaga modul, metadata jalur, status terbit, suntingan admin, dan progres yang sudah ada; menjalankan ulang tidak menggandakan data. Modul lama tidak dihapus. Penguncian dan tombol modul berikutnya mengikuti urutan terbit pada masing-masing jalur. Tes mencakup validasi bank, seluruh 180 jawaban melalui HTTP, pengulangan seeder, perlindungan kunci jawaban, progres penyelesaian, serta tampilan kuis langsung React. Tampilan pada perangkat dan browser nyata perlu diperiksa setelah deploy.


## Percobaan kuis modul dan analisis jawaban

Seluruh latihan modul (termasuk latihan tunggal lama) menerima jawaban valid yang salah dan langsung maju. Status benar/salah dan pembahasan jawaban yang baru dikirim ditampilkan langsung, sedangkan rincian seluruh percobaan tersedia setelah soal terakhir. Pilih **Ulangi kuis** untuk membuat baris `lesson_attempts` baru; percobaan sebelumnya, salinan bank soal, jawaban, ketepatan, dan waktu menjawab tetap disimpan. Pengulangan tidak menggandakan 10 poin atau penyelesaian modul. Riwayat menampilkan lima percobaan selesai terakhir; data sebelumnya tetap tersedia untuk analisis. Esai tetap dinilai dengan jawaban acuan, bukan AI.

```bash
php artisan migrate --force
npm run build
php artisan queue:restart
```

Restart SSR sesuai prosedur sebelumnya dan pastikan Supervisor memulai kembali worker. Tidak perlu seed ulang, dependency, env, atau installer Python. Migration menambah satu tabel tanpa mengubah progres selesai lama. Data jawaban dari sebelum perubahan ini tidak dapat dibuat ulang; percobaan sesi yang belum selesai dimulai dari soal pertama. Perubahan bank soal memulai versi percobaan baru dan tetap menyimpan jawaban versi sebelumnya. Akun melanjutkan percobaan lintas perangkat; anonim menggunakan cookie browser. Riwayat anonim ikut ditautkan saat masuk dan ikut dihapus melalui penghapusan akun/profil. Rollback migration menghapus riwayat percobaan, sehingga cadangkan database terlebih dahulu.

Analisis pelajar kini memasukkan jawaban room **dan modul**, jumlah percobaan selesai, serta ketepatan percobaan pertama/terakhir per modul dalam jendela 14 hari. Teks esai mentah tidak dikirim ke AI. Jadwal sekitar enam jam tetap berlaku; gunakan tes analisis di admin bila ingin pembaruan langsung. Tes otomatis memeriksa payload AI dengan HTTP palsu, bukan provider production. Setelah deploy, cek satu latihan salah sampai selesai, hasil akhir, pengulangan tanpa poin ganda, percobaan anonim/login, dan hasil tes admin. Kebijakan privasi diperbarui untuk penyimpanan percobaan dan penggunaan agregat jawaban ini.

### Sapaan langsung dalam analisis AI

Prompt ringkasan dan alasan rekomendasi kini menyapa pelajar langsung dengan “kamu”, memberi saran sesuai minat/aktivitas, dan menghindari gaya laporan tentang pengguna. Ketiadaan catatan aktivitas tidak dianggap sebagai bukti belum pernah belajar. Deploy source lalu jalankan `php artisan queue:restart` agar worker memuat prompt baru. Tidak ada migration, build frontend, atau env baru. Hasil yang sudah tersimpan tetap tampil sampai analisis berikutnya; gunakan tes analisis di admin untuk memperbaruinya langsung. Tes memeriksa instruksi yang dikirim, sedangkan gaya keluaran provider nyata perlu ditinjau dari hasil analisis setelah deploy.

## Menjalankan seluruh seeder sekaligus

`DatabaseSeeder` kini memanggil semua seeder: jalur belajar, admin lokal, admin production, modul Aksara Bali, modul Paribasa, metadata audio, dan laporan AI. Jalur dibuat sebelum modul; metadata audio dibuat setelah semua modul tersedia. Perintah ini juga membuat/mempromosikan admin production sesuai `ADMIN_NAME`, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD` dari `.env`, sehingga konfigurasi admin yang valid wajib tersedia.

```bash
php artisan db:seed --force
```

Pada database kosong, hasilnya 8 jalur, 28 modul, dan 28 metadata audio. Seed ulang mempertahankan data yang sudah ada. Metadata audio hanya disimpan sebagai draf, bukan langsung membangkitkan suara. `AiReportSeeder` tetap mengikuti perilaku yang sudah ada: membuat snapshot berstatus unavailable saat AI belum dikonfigurasi, dan melewati pembuatan laporan bila AI sudah dikonfigurasi; tidak menghubungi provider AI. Tidak ada migration, build frontend, dependency, atau restart worker baru untuk penggabungan seeder ini. Panduan lama yang menyebut seeder modul/admin harus dipanggil terpisah kini bersifat opsional: masing-masing class masih dapat dijalankan sendiri.


## Umpan balik jawaban dengan animasi dan efek suara

Seluruh kuis modul menampilkan panel hijau/merah untuk jawaban yang baru dikirim, termasuk pembahasan dan jawaban acuan bila salah. Panel otomatis lanjut setelah 3 detik atau melalui tombol Lanjutkan; jawaban tersimpan dan tidak dapat diganti. Beralih bagian tetap membuka bacaan bagian berikutnya, bukan melewatinya. Muat ulang halaman tidak memutar ulang panel jawaban sebelumnya. Riwayat percobaan dan poin tetap mengikuti mekanisme sebelumnya.

Kuis room memakai panel animasi/audio hanya ketika server mengizinkan feedback sesuai `show_answers_immediately`. Pengaturan off tetap menyembunyikan hasil selama permainan; tidak ada suara benar/salah yang membocorkannya. Timer dan kelanjutan room tetap mengikuti server. Tidak ada toast tambahan untuk jawaban benar: umpan balik muncul di area soal.

Efek memakai aset `public/audio/benar.mp3` dan `public/audio/salah.mp3` yang disediakan pengguna. Tombol suara menyimpan pilihan di `localStorage` dengan key `artaswaha-answer-muted`; pemblokiran audio oleh browser tidak menghalangi pengiriman jawaban. Animasi menghormati reduced motion. Deploy source dan kedua aset, jalankan `npm run build`, lalu restart SSR sesuai prosedur. Tidak ada migration, seeder, dependency, atau konfigurasi worker baru. Tes memeriksa pengiriman, penguncian jawaban salah, suara benar/salah, mute, kelanjutan otomatis, dan feedback room off. Pemutaran suara pada Safari/iOS dan speaker perangkat nyata perlu dicek setelah deploy.

### Editor kuis dan transisi hasil

Editor membuat/mengedit kuis memakai navigasi tiga langkah: pengaturan, soal, lalu preview dan simpan. Desktop menampilkan langkah di sisi kiri; mobile menampilkannya di atas, dengan tombol navigasi tetap terlihat di atas navigasi aplikasi. Bantuan AI bersifat opsional pada langkah soal. Satu soal terbuka saat diedit; tambah/duplikasikan soal membuka soal baru, sedangkan kesalahan validasi membuka soal yang perlu diperbaiki. Perubahan tetap baru tersimpan setelah tombol simpan ditekan.

Pergantian soal dan feedback modul/room sekarang menunggu animasi keluar sebelum panel berikutnya masuk. Hasil akhir keduanya memakai ring persentase, jumlah benar/salah/tidak dijawab, dan pembahasan yang terbuka/tertutup dengan transisi tinggi. Membuka pembahasan memutar efek suara sesuai hasilnya dan mengikuti pilihan mute. Tidak ada suara ringkasan otomatis saat halaman dimuat. Room dengan feedback nonaktif tetap menampilkan hasil hanya setelah kuis selesai; refresh status room tidak memutar ulang efek jawaban yang sama.

Deploy source, jalankan `npm run build`, lalu restart SSR. Tidak ada migration, env, dependency, installer Python, atau perubahan worker. Tes React mencakup editor, pembahasan, pengaturan feedback, dan transisi keluar/masuk dengan Motion asli. Periksa tampilan serta audio di perangkat mobile nyata setelah deploy, termasuk reduced motion dan browser yang memblokir autoplay.

### Tampilan bermain kuis bersama

Halaman room diselaraskan dengan latihan modul: judul kuis ringkas, soal rata kiri dengan Aksara Bali yang terbaca, pilihan berpenanda huruf dan status terpilih, serta tombol kirim selebar layar pada mobile. Pilihan tetap berupa radio/checkbox native dengan fokus keyboard; penanda dekoratif tidak ikut dibaca sebagai nama jawaban. Kontrol yang sama digunakan modul dan preview editor. Progres room menghitung jawaban yang telah dikirim, bukan soal yang baru dibuka. Countdown menandai lima detik terakhir dengan warna merah tanpa mengubah perhitungan server.

Lobby memakai panel kode teal lembut, jumlah soal/peserta dari data room, dan avatar peserta asli. Ringkasan selesai tetap mengikuti pengaturan feedback dan transisi/audio yang sudah ada. Deploy frontend dengan `npm run build` lalu restart SSR; tidak ada perubahan database, queue, env, atau dependency. Tes mencakup pengiriman jawaban, tipe soal, progres, identitas lobby, serta penyembunyian hasil sebelum server mengizinkan. Verifikasi visual dan audio di HP fisik tetap diperlukan.

### Navigasi tanpa animasi ulang seluruh layar

Menu utama desktop/mobile, dropdown materi/profil, dan sidebar admin menyiapkan chunk React saat pointer diarahkan, fokus keyboard masuk, atau layar disentuh. Hanya kode halaman yang diunduh; tidak ada prefetch respons GET, penciptaan percobaan, join room, atau antrean AI akibat menyentuh menu. Browser menggunakan kembali modul yang sudah diunduh. Pemuatan spekulatif dilewati pada Save-Data atau koneksi 2G bila browser menyediakan informasinya.

Halaman lama tetap terlihat selama request Inertia, tanpa skeleton navigasi atau remount paksa pembungkus halaman. Animasi reveal konten bawah layar tetap tersedia. Progress bar navigasi bawaan Inertia dinonaktifkan; status aksesibilitas tetap tersedia. Overlay pembuka tetap hanya berjalan saat dokumen dimuat ulang. Request data server tetap diperlukan sehingga ini bukan jaminan seluruh navigasi instan atau perbaikan latensi production yang belum diukur.

Perubahan indikator navigasi memerlukan build frontend dan SSR terbaru (`npm run build`), lalu restart proses SSR jika digunakan. Tab lama perlu dimuat ulang agar memakai aset baru. Tidak ada perubahan migrasi, environment, atau worker antrean.

Deploy source, `npm run build`, lalu restart SSR. Tidak ada migration, env, dependency, atau perubahan worker. Periksa navigasi pertama/ulang, Back, jaringan lambat, fokus keyboard, dan mobile. Tes mencakup pemanasan chunk, kegagalan preload, Save-Data/2G, serta konten/state yang bertahan selama navigasi menunggu. Ukuran build dan tes komponen tidak menggantikan pengukuran TTFB/INP di production.

### Optimasi respons navigasi Inertia

Deploy PHP dan frontend bersama, jalankan `npm run build`, lalu restart SSR agar kontrak analitik baru tidak tercampur dengan bundle lama. `deploy.sh` sudah menjalankan optimasi cache Laravel; tidak ada migration, seed, env, dependency, installer, atau perubahan worker untuk optimasi ini. Muat ulang tab yang masih memakai bundle lama setelah deployment.

Menu materi memakai satu query dengan batas 12 per kategori. Identitas/progres/CSRF tidak di-cache lintas request; URL shell dan brand memakai once props Inertia. Katalog akun/anonim boleh diprefetch saat hover/click selama 15 detik; mode hemat data/2G dan tamu dikecualikan. Pelajaran, room, profil, beranda, dan admin tidak diprefetch datanya. Pagination katalog/modul memakai partial reload pada komponen yang sama. Analitik admin mengembalikan kerangka lebih dulu, lalu mengambil satu prop `statistics`; error deferred dilaporkan Laravel dan UI menyediakan coba lagi tanpa skeleton tak terbatas.

Sesudah deploy, periksa perpindahan katalog/materi/profil, pagination, Back, logout/login identitas lain, dan admin analitik (termasuk koneksi gagal). Periksa Network: hover katalog mengirim prefetch satu kali dalam TTL, hover pelajaran tidak mengirim data, pagination tidak mengirim ulang menu, analitik memuat statistik melalui satu request tambahan, dan presence tidak diulang karena berganti layout. Panduan audit dan batas bukti pengukuran ada di `docs/REACT.md` bagian 6. Tes query memakai SQLite; TTFB, PHP-FPM, indeks/slow query MySQL, dan tampilan mobile production belum diukur.

### Heartbeat presence tunggal

Heartbeat kini dipasang sekali pada bootstrap React, bukan pada masing-masing layout. Berganti halaman pengguna/admin/auth tidak memulai ping baru. Ping tetap setiap 30 detik, dilewati saat tab tersembunyi atau request sebelumnya belum selesai, dan perubahan visibility dibatasi dengan cadence yang sama. Timeout tetap 10 detik; cleanup membatalkan timer/request. Deploy client dengan `npm run build`, restart SSR sesuai prosedur, lalu muat ulang tab lama. Tidak ada migration, env, dependency, atau perubahan proses worker. Tes Strict Mode memeriksa tidak ada ping ganda dan timer dibersihkan. Jumlah pengunjung tetap memakai expiry server dua menit; latency presence production perlu diukur terpisah.

### Navigasi mobile pada pelajaran

Halaman pelajaran memakai navigasi bawah pill yang sama dengan halaman aplikasi lain untuk akun terdaftar dan anonim, dengan menu Belajar aktif dan ruang bawah konten agar tombol tidak tertutup. Hamburger tidak tampil pada kedua identitas tersebut. Tamu tetap memakai menu tamu; kuis room aktif tetap menyembunyikan navigasi aplikasi. Jalankan `npm run build` lalu restart SSR setelah deploy. Tidak ada perubahan env atau dependency. Tes komponen mencakup kedua identitas, menu aktif, ruang konten, dan pengecualian room aktif; tampilan HP fisik belum diverifikasi.

### URL Sesonggan dan tautan lama

Migration lanjutan `2026_10_29_234310_rename_sesonggakan_slugs_to_sesonggan.php` menggantikan keputusan mempertahankan URL lama di bagian berikut: slug jalur dan modul sekarang memakai `sesonggan`. ID, progres, dan riwayat jawaban tetap utuh. URL lama dialihkan ke URL baru (GET 301, POST 307 agar isian tetap dikirim). Jika slug tujuan sudah dipakai baris lain, seluruh transaksi dibatalkan tanpa menggabungkan atau menghapus data; periksa duplikat sebelum menjalankan ulang.

Deploy source lalu `php artisan migrate --force`. Perbarui route cache jika digunakan (`php artisan route:cache`). Tidak perlu seed ulang atau reset database. Rollback sengaja mempertahankan slug canonical agar tautan baru tetap berfungsi. Tes mencakup redirect, pengiriman formulir lama, seed ulang, dan pembatalan transaksi saat bentrok. Migration production belum dijalankan oleh Codex.

### Koreksi nama Sesonggan tanpa menghapus data production

Nama tampil lama diperbaiki menjadi **Sesonggan** pada FAQ/profil, seeder, nama/deskripsi jalur, teks materi modul, judul/deskripsi kuis, serta prompt/pembahasan soal kuis. Migration `2026_10_29_214459_rename_sesonggakan_display_text_to_sesonggan.php` hanya memperbarui teks yang cocok dalam transaksi, membaca per 100 baris. Tidak ada tabel/baris yang dihapus atau dibuat ulang, dan ID, foreign key, slug/URL lama, timestamps, progres, skor, opsi/kunci jawaban, JSON bank soal, serta snapshot/riwayat percobaan tetap dipertahankan. Riwayat dan keluaran AI yang sudah tersimpan tidak ditulis ulang; analisis berikutnya memakai nama jalur yang sudah diperbaiki.

Seeder database baru memakai slug `sesonggan`. Bila production sudah memiliki slug lama, kedua seeder tetap memakai ID/slug tersebut dan modul di bawahnya, sehingga menjalankan seed lagi tidak menggandakan jalur atau modul. Bila kedua slug sudah ada sebelum deploy, data keduanya tetap dipertahankan; perubahan ini tidak menggabungkan jalur otomatis. Kemunculan istilah lama pada kode kompatibilitas dan fixture tes disengaja.

Setelah cadangan database tersedia, deploy source lalu jalankan:

```bash
php artisan migrate --force
npm run build
```

Restart SSR sesuai prosedur sebelumnya. Tidak perlu menjalankan seeder atau mengubah env. Gunakan `migrate`, bukan `migrate:fresh`, `migrate:refresh`, atau reset database. Tidak ada perubahan worker atau dependency. Rollback migration sengaja tidak membalikkan koreksi teks karena tidak bisa membedakan istilah yang sejak awal sudah benar. Gunakan cadangan jika perlu memulihkan teks persis seperti sebelumnya. Tes memeriksa koreksi, eksekusi ulang, riwayat jawaban/progres yang utuh, seed database baru, dan seed ulang data dengan URL lama. Migrasi production belum dijalankan oleh Codex.

### Audio materi dan audio latihan modul

Migration `2026_10_30_220538_add_lesson_audio_slots_to_content_audio.php` menambah slot audio: 0 untuk materi, 1 dan seterusnya untuk nomor soal latihan. Metadata dan file lama tetap disimpan. Jalankan `php artisan migrate --force`, `npm run build`, perbarui route cache jika digunakan, dan restart SSR sesuai prosedur sebelumnya. Muat ulang hanya worker aplikasi ARTASWAHA melalui Supervisor agar job memakai pemeriksaan sumber terbaru; tidak perlu restart Reverb, memasang model, mengubah env, atau seed ulang.

Di admin **Audio pembelajaran / Modul**, buka **Pilih audio materi atau soal**. Tautan audio per soal juga tersedia pada preview modul, termasuk latihan tunggal lama. Narasi materi mengabaikan bagian `Sumber belajar`, URL, serta penanda judul/tebal/tautan Markdown. Narasi soal memuat pertanyaan dan pilihan saja; kunci jawaban serta pembahasan tetap mengikuti pengungkapan setelah menjawab, tidak dimasukkan ke audio pertanyaan. Buat audio lalu dengarkan dan setujui setiap slot yang diperlukan. Membuka editor atau mengerjakan latihan tidak memicu inferensi TTS.

Audio materi lama ditandai kedaluwarsa karena aturan sumber berubah. Buka editor untuk mendapatkan narasi bersih, lalu buat dan tinjau ulang audio; file lama tidak otomatis dihapus sebelum penggantian. Audio soal yang belum dibuat/disetujui tidak menampilkan pemutar. Pengguna hanya menerima audio soal aktif; berpindah soal menghentikan pemutar sebelumnya dan tidak mengunduh audio soal berikutnya terlebih dahulu. Mengubah pertanyaan/pilihan atau menghapus soal membuat audio terkait kedaluwarsa tanpa mengubah riwayat jawaban atau progres. Hindari menulis kunci jawaban dalam narasi pertanyaan kustom.

Pemeriksaan setelah deploy: buka satu modul bersumber, pastikan narasi tidak memuat URL/catatan sumber; buat dan setujui audio dua soal; periksa pemutar soal pertama, kirim jawaban, lalu periksa audio soal kedua. Periksa juga esai, latihan tunggal, audio belum ditinjau, serta soal yang diedit. Tes otomatis memakai antrean/storage palsu, bukan inferensi model. Pemutaran dan tampilan pada perangkat nyata tetap perlu dicek. Rollback migration ditolak bila slot latihan sudah berisi data agar metadata tidak terhapus; gunakan perbaikan maju di production.

### Simpan narasi sesudah membuat audio

Tombol **Buat audio offline** sudah menyimpan narasi sebelum mengantrekan pembuatan suara. Setelah selesai, dengarkan dan **Setujui** hasilnya; tidak wajib menekan Simpan narasi lagi. Menyimpan teks, bahasa, dan tempo yang sama mempertahankan file, persetujuan, versi, waktu generasi, dan tugas yang sedang diproses, termasuk rekaman unggahan. Perubahan sumber/teks/bahasa/tempo tetap membatalkan audio lama agar pengguna tidak mendengar versi yang tidak cocok. Audio yang sudah terhapus oleh perilaku lama perlu dibuat ulang atau dipulihkan dari backup yang tersedia.

Deploy PHP dan build frontend/SSR (`npm run build`), lalu restart hanya SSR ARTASWAHA. Perbaikan tombol simpan ini tidak menambah migration, dependency, env, atau restart worker/Reverb. Tes terarah memeriksa simpan lalu buka ulang audio materi/kuis siap pakai, audio latihan yang menunggu tinjauan/dalam antrean/diproses, serta pengosongan waktu generasi ketika narasi berubah.

### Hapus audio dari admin

Tombol **Hapus audio** berada di samping persetujuan pada panel audio, juga tersedia untuk audio siap pakai atau proses yang masih mengantre/berjalan. Konfirmasi diperlukan. Hanya admin dengan versi audio terkini yang boleh menghapus; aksi menghapus file audio, mengosongkan persetujuan/waktu generasi, dan mengembalikan metadata menjadi draf dengan versi baru. Narasi, pengaturan bahasa/tempo, materi/soal, serta slot audio lain tetap tersimpan. Buat audio kembali dari draf bila diperlukan.

Job lama tidak boleh menimpa versi baru. Menghapus saat inferensi berjalan tidak langsung menghentikan proses Python; hasilnya dibuang saat selesai. Audio yang terhapus tidak dapat diputar melalui URL lama. Deploy PHP dan frontend bersama, perbarui route cache bila dipakai, jalankan `npm run build`, lalu restart hanya SSR dan worker ARTASWAHA melalui Supervisor agar pemeriksaan versi terbaru dipakai. Tidak ada migration, env, dependency, model, atau restart Reverb baru. Uji hapus/batal pada audio review dan ready, pastikan narasi bertahan saat dibuka lagi, slot lain tetap dapat diputar, dan hasil job yang sudah dihapus tidak muncul kembali. Tes memakai file/proses palsu; verifikasi perangkat nyata tetap diperlukan.
