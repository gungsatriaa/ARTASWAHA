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

## 6. Worker Laravel learner-analysis

Worker default dan Reverb yang sudah ada tetap digunakan. Analisis/TTS memakai koneksi bernama `learner-analysis`, driver database, antrean `learner-analysis`; worker Redis default tidak mengonsumsinya. Nama antrean ini tidak mengaktifkan Ollama.

Analisis progres memakai `AI_API_URL`, `AI_API_KEY`, dan `AI_MODEL` yang sama dengan AI kuis. Jadwal `learning:analyze` berjalan setiap enam jam melalui scheduler Laravel. Pastikan cron yang sudah ada menjalankan `php artisan schedule:run` setiap menit dari folder aplikasi; jangan menambahkan cron duplikat. Untuk lokal gunakan `php artisan schedule:work`. Worker `learner-analysis` tetap harus aktif untuk memproses antreannya. Analisis memakai ringkasan pelajaran selesai dan jawaban kuis 14 hari terakhir, bukan pelacakan durasi membaca.

Jeda enam jam berlaku untuk analisis yang sudah selesai. Percobaan gagal bisa dijadwalkan ulang setelah dua menit; antrean/pemrosesan yang tersangkut bisa diganti setelah 30 menit saat profil dibuka atau perintah `learning:analyze` dijalankan. Lock unik sekarang mengikuti waktu permintaan, sehingga percobaan lama tidak menghalangi penggantinya dan tidak menimpa status/hasil baru. Status menunggu antrean tidak menampilkan skeleton pemrosesan.

Setelah memperbarui perbaikan antrean, cache konfigurasi melalui alur deploy bagian 3, lalu Restart hanya program `laravel-worker-tts-artaswaha` melalui Supervisor aaPanel agar kode/config baru dipakai. Jalankan `php artisan learning:analyze` untuk menjadwalkan profil yang sudah memenuhi jeda percobaan ulang, lalu periksa `php artisan queue:failed` dan log terbaru. Pastikan `AI_API_URL` dapat diakses dari server production; `127.0.0.1` menunjuk mesin server itu sendiri. Worker ini juga mengerjakan audio: bila sedang membuat audio panjang, analisis menunggu gilirannya. Log `Learner analysis provider returned an error.` mencatat status HTTP tanpa key atau isi jawaban.

Untuk server production ini, gunakan program Supervisor aaPanel yang sudah ada, `laravel-worker-tts-artaswaha`. Edit program tersebut; tidak perlu memasang service systemd atau menjalankan `install-tts-worker.sh`. Isi:

```text
Name: laravel-worker-tts-artaswaha
Run User: www
Processes: 1
Process directory: /www/wwwroot/artaswaha.kerthajaya.com/
Start command: php artisan queue:work learner-analysis --queue=learner-analysis --sleep=2 --tries=3 --timeout=1830 --max-time=3600
```

Pastikan autostart/autorestart aktif. Gunakan menu Log/Restart untuk program tersebut di Supervisor aaPanel. Jika PHP tidak ditemukan oleh Supervisor, gunakan path absolut PHP 8.4 hasil `command -v php` pada Start command. Tiga worker Redis default tetap terpisah. Jika service systemd dari langkah sebelumnya pernah dipasang, nonaktifkan agar tidak menjalankan worker ganda:

```bash
systemctl disable --now artaswaha-learner.service
```

`deploy.sh` menerima nama program yang sudah ada melalui `.env` atau environment shell. Jika Supervisor CLI tersedia, set nama group sesuai konfigurasi aaPanel, misalnya `LEARNER_QUEUE_SUPERVISOR_PROGRAM=laravel-worker-tts-artaswaha:*`. Jika nama dikosongkan atau Supervisor CLI tidak tersedia, deploy memeriksa proses `artisan queue:work learner-analysis` beserta working directory-nya, sehingga hanya proses dari folder proyek ini yang diterima. Pemeriksaan ini tidak bergantung pada nama program systemd/Supervisor. Worker yang tidak ditemukan tetap menggagalkan deploy sebelum maintenance. Jalankan deploy dengan user yang dapat membaca `/proc/<pid>/cwd`, seperti root yang digunakan pada server ini.

Untuk tuning CPU, set `TTS_CPU_THREADS=4` dan `TTS_PERSISTENT=true` dalam `.env`, jalankan `php artisan config:cache`, lalu restart hanya program TTS di Supervisor aaPanel. Nilai bawaan thread tetap 2; coba 4 kemudian 8 pada server banyak core dengan narasi yang sama. Runner membatasi jumlah thread berdasarkan CPU yang tersedia dan menyisakan setidaknya satu CPU jika memungkinkan. Satu tugas inferensi tetap dijalankan pada satu waktu. Lebih banyak thread belum tentu lebih cepat, dan jumlah thread bukan jaminan batas penggunaan CPU seluruh proses.

Dengan `TTS_PERSISTENT=true`, worker Laravel mempertahankan satu proses Python lokal melalui stdin/stdout. Nilai bawaan tetap `false` sampai administrator mengaktifkannya setelah memastikan Processes=1 pada program TTS. Model dimuat saat tugas pertama, kemudian dipakai ulang untuk tugas berikutnya. Referensi suara yang path/ukuran/waktu modifikasinya sama tidak diproses ulang; referensi yang berubah dimuat ulang. Mengosongkan referensi mengembalikan suara bawaan. Tidak ada HTTP API, port baru, paket tambahan, atau service Supervisor tambahan.

Model menggunakan RAM selama proses Python hidup. Proses dilepas setelah 15 menit tanpa permintaan atau setelah 50 tugas; tugas berikutnya memuat model kembali. Restart/keluarnya worker Laravel juga menghentikan proses Python; di Linux child meminta sinyal ketika induk mati agar tidak tertinggal. Setiap tugas dibatasi 1800 detik (30 menit), dan crash/timeout mereset proses sebelum tugas berikutnya. Jika perlu kembali ke perilaku satu proses per tugas, set `TTS_PERSISTENT=false`, cache konfigurasi, lalu restart program TTS.

Narasi mendukung hingga 16.000 karakter dan audio hasil generate hingga 64 MB; unggahan rekaman tetap maksimal 10 MB. Potongan Chatterbox tetap dibatasi 220 karakter, tetapi kalimat pendek digabung selama muat dalam satu potongan untuk mengurangi pemanggilan decoder berulang. Ukuran kamus token decoder tidak diubah. Batas ini bukan jaminan seluruh narasi panjang selesai dalam 30 menit pada CPU; bagi materi jika melebihi kapasitas mesin.

Untuk menguji pemakaian ulang, buat audio pendek melalui admin, lalu pilih **Simpan narasi** dan **Buat audio offline** lagi pada materi percobaan. Gunakan narasi/referensi sama agar perbandingan masuk akal. Log `Chatterbox TTS selesai` di `storage/logs/laravel.log` mencatat `model_reused`, `model_load_seconds`, `reference_seconds`, `generation_seconds`, `seconds`, dan `threads` tanpa isi narasi. Tugas kedua diharapkan memiliki `model_reused=true` saat ditangani proses yang sama. Waktu sintesis dan pemakaian RAM sebenarnya perlu diukur di server; biaya pemuatan yang berkurang tidak menjamin seluruh proses menjadi instan.

Jalankan migrasi setelah menarik pembaruan timer audio. Kolom `generation_started_at` dan `generation_finished_at` menyimpan waktu proses aktual dari worker, termasuk ketika tugas gagal; menyimpan narasi baru mengosongkan waktu versi lama. Durasi pada UI menghitung waktu pemrosesan, tanpa waktu menunggu antrean. Jam awal/akhir memakai zona waktu aplikasi.

Dalam mode persisten, Python melaporkan fase dan bagian yang sudah selesai melalui pipe yang sama. Metadata progres disimpan dalam cache per ID/versi audio selama satu jam, diperbarui per bagian (bukan per token). UI mengecek status setiap tiga detik saat tab terlihat; timer lokal berdetak tiap detik tanpa request tambahan. Bar mengikuti jumlah bagian yang benar-benar selesai. Estimasi sisa dihitung dari waktu generasi dan jumlah karakter yang sudah diproses pada tugas tersebut; sebelum bagian pertama selesai estimasi belum tersedia. Estimasi dapat berubah atau terlewati, dan tidak digunakan sebagai penanda tugas selesai. Untuk adapter selain mode persisten, waktu aktual tetap tercatat, tetapi progres per bagian/estimasi dapat belum tersedia.

Perintah CLI `synthesize` untuk diagnosis tetap memuat model baru setiap eksekusi. Gunakan perintah itu untuk membandingkan thread 4 dan 8 dengan teks/referensi/perangkat yang sama, bukan untuk mengukur pemakaian ulang model. Pengukuran mode persisten dilakukan lewat antrean admin seperti di atas. JSON CLI menampilkan `model_load_seconds` (termasuk startup/import), `reference_seconds`, `generation_seconds`, `model_reused`, serta jumlah `threads` efektif.

Contoh konfigurasi Supervisor mentah jika diperlukan (sesuaikan nama/path dengan program aaPanel yang sudah ada):

```ini
[program:artaswaha-learner]
process_name=%(program_name)s_%(process_num)02d
directory=/www/wwwroot/artaswaha
command=/www/server/php/84/bin/php /www/wwwroot/artaswaha/artisan queue:work learner-analysis --queue=learner-analysis --sleep=2 --tries=3 --timeout=1830 --max-time=3600
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

Jika program sudah ada, periksa dan sesuaikan; jangan membuat duplikat. `retry_after` koneksi learner adalah 1860 detik; timeout inferensi 1800, job 1830, lock 1845, dan stopwaitsecs 1860. Setelah perubahan ini, cache konfigurasi dan restart worker TTS agar reservasi antrean dan batas proses memakai nilai baru. Pertahankan cache lock bersama yang sudah digunakan aplikasi. Pastikan PHP CLI mengizinkan `proc_open`, `proc_get_status`, `proc_terminate`, dan `proc_close` yang digunakan untuk proses anak. Pengaturan CLI aaPanel dapat berbeda dari PHP-FPM.

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

Audio lama tidak berubah otomatis: setelah mengganti referensi, pilih **Simpan narasi**, lalu **Buat audio offline** untuk menghasilkan ulang. Tinjau hasil sebelum menyetujui audio untuk pengguna.

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

Tidak perlu seed materi demo secara massal di production. Audio sudah disetujui tetap diputar dari file tanpa memanggil model lagi. Untuk menghasilkan ulang pada engine/model yang sama, Simpan narasi lalu Buat audio offline; deduplikasi sengaja menggunakan kembali hasil yang masih berlaku.

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

1. **Admin → Modul → Buat/Ubah modul:** pilih kolom materi, pertanyaan latihan, pilihan, atau penjelasan, lalu buka **Keyboard aksara Bali**. Pastikan karakter masuk ke kolom yang dipilih dan tetap sama setelah disimpan/muat ulang. Opsi **Buka keyboard aksara otomatis untuk pelajar** berlaku pada modul tersebut. Perubahan modul tetap mengikuti alur simpan draf → preview → terbitkan.
2. **Buat/Edit kuis:** atur **Buka keyboard aksara otomatis saat soal ini tampil** secara terpisah pada setiap soal. Uji juga soal dan pilihan yang ditambah/dihapus melalui editor. Kuis yang sudah memiliki room tetap membuat versi baru ketika diedit; room lama mempertahankan pengaturannya.
3. **Pengguna:** uji satu soal dengan opsi otomatis aktif dan satu nonaktif. Yang aktif langsung membuka panel; yang nonaktif menyediakan tombol buka/tutup. Untuk modul aksara tanpa latihan, panel tersedia setelah materi. Tulisan pengguna adalah coretan latihan di browser, tidak dikirim/disimpan ke server atau dinilai sebagai jawaban; kuis tetap memakai pilihan ganda.
4. **Input dan HP:** uji aksara dasar, pangangge, gantungan, adeg-adeg manual tanpa duplikasi, sisip/ganti seleksi, hapus, spasi, enter, serta pindah kolom. Periksa lebar 320–390 px, orientasi, ketukan berulang, scroll panjang dan berhenti, serta pergantian keyboard perangkat. Memakai keyboard tidak menambahkan request per karakter.

Tes PHP modul/kuis, tes JavaScript keyboard, dan build frontend telah lolos secara lokal. Rendering gabungan aksara, gestur, dan perilaku keyboard perangkat pada browser/HP sebenarnya masih perlu diperiksa; pengujian otomatis tersebut bukan verifikasi visual atau jaminan kelancaran semua perangkat.

## Kelola kuis: penyimpanan soal, audio, dan arsip

Editor mengirim ID soal tersimpan agar perubahan kuis tanpa room mempertahankan soal dan audionya. Perubahan pertanyaan/pilihan menandai audio `stale`; perubahan urutan, kunci jawaban, penjelasan, atau keyboard tidak mewajibkan generate ulang. Soal yang dihapus dibersihkan bersama file audionya setelah transaksi berhasil.

Kuis yang sudah memiliki room tetap membuat versi baru ketika diedit. Audio `ready`/`review` dengan hash sumber yang cocok dan file yang tersedia disalin ke file privat baru untuk soal yang dipertahankan. Duplikasi kuis memakai aturan sama dan tidak menyalin room/jawaban. Audio kedaluwarsa atau masih diproses tidak disalin; generate tetap melalui alur audio admin. Pengarsipan manual menyembunyikan kuis dari daftar aktif dan melarang room baru; riwayat room tetap tersedia. Untuk memakai arsip lagi, buat salinan agar versi lama tidak diubah.

Pembaruan ini tidak menambah migration, dependency, atau environment. Gunakan alur deploy bagian 3 untuk memuat route dan frontend baru. Tidak perlu restart TTS/Reverb untuk perubahan pengelolaan kuis ini.

Uji setelah deploy: ubah judul/urutan dan pastikan audio lama tetap dapat diputar; ubah teks/pilihan dan pastikan audio lama tidak diputar peserta; edit kuis yang sudah punya room dan periksa riwayat/audio versi lama; duplikasikan kuis dan cek keyboard/audio salinan; arsipkan kuis dan cari pada filter arsip. Aksi dan ID soal harus ditolak jika berasal dari kuis milik pengguna lain.

Alur editor sekarang **Pengaturan → Kelola soal → Preview & simpan → Detail kuis → Buat room**. Menu **Kelola kuis** pada admin menuju koleksi kuis milik akun tersebut; akses tidak diperluas ke kuis milik akun lain. Daftar menyediakan pencarian judul, filter aktif/arsip, edit, duplikasi, dan arsip dengan konfirmasi. Detail kuis menampilkan pilihan/kunci jawaban khusus pemilik, pengaturan keyboard, status audio, serta akses preview peserta.

Pada **Kelola soal**, soal dapat dilipat, diduplikasikan, dipindah naik/turun, dan dihapus. Penomoran ulang mempertahankan jawaban benar. Duplikasi satu soal menyalin isi/kunci/keyboard sebagai soal baru tanpa ID atau audio lama; simpan dahulu untuk menyiapkan audio sendiri. Duplikasi seluruh kuis mengikuti aturan salinan audio di atas. Batas tetap 20 soal dan 2–5 pilihan per soal.

Keyboard editor mengisi kolom terakhir yang dipilih. Keyboard pada preview mempunyai area coretan terpisah dan mengikuti opsi otomatis tiap soal. Status dan tombol **Kelola audio soal** berada pada soal tersimpan; pengaturan/generate/unggah/persetujuan audio tetap khusus admin. Simpan perubahan sebelum membuka pengaturan audio. Preview hanya memutar audio `ready` yang disetujui dan cocok dengan pertanyaan/pilihan saat ini; teks yang berubah tidak memakai rekaman lama. Preview tidak menjalankan inferensi, timer, penilaian, atau pengiriman jawaban. Audio berhenti saat pindah tahap/soal atau meninggalkan/menyembunyikan halaman.

Editor menampilkan validasi di dekat kolom, membuka soal yang perlu dilengkapi, memperingatkan ketika meninggalkan perubahan belum tersimpan, dan mengunci tombol simpan selama pengiriman. Peringatan keluar memakai mekanisme browser dan memerlukan interaksi pengguna. Tidak ada autosave atau penyimpanan setiap karakter. Modul JavaScript editor dimuat hanya pada halaman editor; tidak ada dependency baru.

Sesudah deploy/build, muat ulang halaman editor lama sebelum mengedit. Uji ketiga tahap, validasi soal yang dilipat, duplikasi/pindah/hapus pilihan tanpa perubahan kunci yang tidak disengaja, preview keyboard manual/otomatis dan audio, batal/konfirmasi arsip, pencarian/filter/pagination, serta tombol kembali setelah menyimpan. Uji pada HP 320–390 px dan orientasi berbeda, termasuk scroll panjang, fokus input, dan posisi panel keyboard. Tes aplikasi dan JavaScript lokal tidak menggantikan pemeriksaan visual/gestur pada browser sebenarnya; browser pengujian tidak tersedia dalam sesi implementasi.

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

## React/Inertia, SSR, dan animasi

Seluruh halaman menggunakan React/Inertia, termasuk publik, autentikasi, kuis, dan admin. Tidak ada `REACT_UI_ENABLED` atau mode Blade aktif; hapus baris lama tersebut jika masih ada di `.env`. Template Blade lama tetap menjadi referensi. Tidak ada migration atau seeder baru untuk cutover UI. Gunakan `composer install`, `npm ci`, `npm run build`, dan pembaruan cache Laravel pada alur bagian 3. Build sekarang menghasilkan `public/build` dan `bootstrap/ssr`; keduanya harus berada pada release yang sama. Jangan menghapus aset hash versi sebelumnya ketika masih ada tab pengguna terbuka; gunakan release/deployment aset yang mempertahankannya selama transisi.

Gunakan PHP 8.4 dan Node 22.12+ yang cocok dengan lockfile (baseline lokal Node 22.22.3). SSR aktif secara default dan memerlukan proses Node yang dikelola Supervisor. Pastikan user program dapat menjalankan `node --version` melalui PATH yang sama dengan program Supervisor; PATH shell interaktif belum tentu sama.

Contoh program **baru**, sesuaikan path dan user sebelum dipakai:

```ini
[program:artaswaha-ssr]
directory=/www/wwwroot/artaswaha
command=/www/server/php/84/bin/php artisan inertia:start-ssr --no-interaction
user=www
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
stopwaitsecs=15
redirect_stderr=true
stdout_logfile=/www/wwwroot/artaswaha/storage/logs/inertia-ssr.log
```

Jangan menimpa konfigurasi Supervisor lain. Server SSR bind hanya ke `127.0.0.1:13714`; jangan membuka port tersebut ke publik. Jika PHP dan Node berada pada container berbeda, konfigurasi jaringan internal SSR perlu disesuaikan dahulu. `INERTIA_SSR_ENABLED=false` hanya opsi darurat untuk menonaktifkan SSR; UI tetap React, tetapi HTML konten publik awal tidak tersedia sampai JavaScript berjalan sehingga SEO menurun.

Setelah build/deploy, restart **hanya** program SSR ARTASWAHA agar memuat bundle terbaru. `deploy.sh` belum mengelola restart proses ini. Worker AI/TTS dan Reverb tetap program yang berbeda; restart hanya yang berubah sesuai panduan sebelumnya. Periksa dengan binary PHP yang sama:

```bash
"$PHP_BIN" artisan inertia:check-ssr --no-interaction
```

Jika health check gagal, periksa log program SSR, executable Node pada PATH program, akses file bundle, dan port loopback. Request SSR dibatasi 3 detik; fallback client menjaga aplikasi tetap dapat dibuka, tetapi bukan pengganti SSR publik yang sehat. Rollback memakai release source + build sebelumnya sebagai satu paket, bukan sakelar renderer atau rollback database.

QA setelah deploy: lihat source HTML awal beranda, katalog, pelajaran, dan privasi; pastikan heading/konten dan metadata sudah ada sebelum JavaScript. Periksa hydration/konsol, direct visit, refresh, Back/Forward, form validasi, login/logout, profil anonim, crop avatar, multi-select interview, editor kuis, feedback/timer, host reload/keluar, dan admin. PHP tetap memeriksa akses; props tidak boleh membawa jawaban masa depan, kunci tersembunyi, email peserta, atau path file privat.

Overlay muncul **setiap reload dokumen** pada semua halaman, menunggu bootstrap React/font dengan batas waktu, mencapai 100%, hold sekitar 450 ms, lalu fade. Persentase adalah kesiapan tampilan, bukan byte jaringan atau progres antrean AI. Visit Inertia menggunakan progress tipis/skeleton untuk GET lambat; submit jawaban, countdown, dan deadline tidak ditunda. Uji lewati, kegagalan chunk, reduced motion, reveal kartu di bawah layar, desktop/mobile, serta job queued/completed/failed. Skeleton analisis berhenti pada error/batas polling dan menampilkan status yang jelas.

Sebelum rilis: `php artisan test --compact`, `npm run test:react`, dan `npm run build`. Cakupan, hasil, serta batas pengujian yang masih perlu dilakukan tercatat di `docs/REACT.md`. UI Blade lama dan initializer DOM-nya sudah dihapus; entry build tetap React. Pertahankan root Blade, komponen `react-loader`, dan sitemap XML. Tes helper edit aksara tetap berjalan dengan `node --test tests/aksara-keyboard.test.js`; tes initializer Blade dan tes worker dengan target skrip yang sudah tidak tersedia telah dihapus.

Interview awal kini wajib bagi pengunjung tanpa akun login/profil anonim. Kunjungan beranda, materi, kuis, dan pendaftaran baru diarahkan oleh middleware ke halaman interview penuh setelah loader. Login, pemulihan, privasi, robots, dan sitemap tetap terbuka. Uji resume langkah, banyak pilihan materi, mode anonim/akun, cookie hilang, serta kembali ke URL tujuan semula. Akun yang sudah login tetap langsung masuk. Tidak ada migrasi atau konfigurasi tambahan; konten yang dilindungi ini tidak lagi tersedia langsung bagi pengunjung baru/crawler tanpa identitas.

Login email, Google, dan setelah registrasi kini selalu memakai cookie remember Laravel, tanpa checkbox. Sesi singkat tetap memakai konfigurasi yang ada; tidak perlu memperbesar `SESSION_LIFETIME`. Cookie remember bawaan sekitar 400 hari, tetap dapat berakhir karena logout/reset sandi/penghapusan akun atau pembersihan browser. Pengguna yang masuk sebelum perubahan perlu login ulang sekali untuk menerima cookie remember baru. Uji pemulihan akun setelah sesi hilang dan pastikan middleware interview mengenalinya; tombol Keluar harus tetap membatalkan token.

Urutan perkenalan: pilihan anonim/akun → materi (bisa banyak) → peran → nama/avatar untuk anonim. Mode akun diarahkan ke login/daftar setelah materi dan peran tersimpan. Mengganti mode mengosongkan jawaban langkah berikutnya. Profil yang sudah selesai tetap tidak mengulang interview.
