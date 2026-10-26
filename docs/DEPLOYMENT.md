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
LEARNER_ANALYSIS_ENABLED=false
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
"$PHP_BIN" artisan queue:restart
"$PHP_BIN" artisan up
```

Gunakan Node yang memenuhi package/lock proyek; Vite saat persiapan memerlukan Node 20.19+ atau 22.12+. Jangan menjalankan `composer update`, `key:generate`, `migrate:fresh`, atau seeder demo pada database production yang sudah ada.

Aplikasi dapat tetap berjalan dengan TTS nonaktif selama runtime diunduh. Skrip deploy memeriksa worker learner sebelum menyalakan maintenance jika TTS atau analisis diaktifkan. Jika keduanya nonaktif, worker tersebut dilewati. Jika worker hilang sesudah restart, kegagalan dilaporkan setelah aplikasi dibuka kembali; pemeriksaan ini tidak meninggalkan situs dalam maintenance.

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

Untuk server Ubuntu yang belum mempunyai worker ini di Supervisor aaPanel, gunakan systemd bawaan:

```bash
php artisan config:cache
bash install-tts-worker.sh
```

Jalankan sebagai root dari folder proyek. Installer mengambil path PHP CLI aktif, memeriksa dukungan `pcntl`/`proc_open` serta akses user `www`, lalu memasang satu service `artaswaha-learner.service` yang aktif saat boot dan otomatis hidup kembali. Jika sebelumnya ada worker sementara di terminal, hentikan dengan Ctrl+C sebelum instalasi; installer menolak proses ganda di folder proyek ini. Jika worker sudah dikelola Supervisor, gunakan satu pengelola saja. Installer tidak memasang paket, mengunduh model, mengubah `.env`, atau me-restart worker Redis/Reverb. Berhenti/restart diberi waktu sampai 300 detik untuk menyelesaikan tugas aktif. Log dan restart khusus worker ini:

```bash
systemctl status artaswaha-learner --no-pager
journalctl -u artaswaha-learner -n 50 --no-pager
systemctl restart artaswaha-learner
```

Untuk tuning CPU, set `TTS_CPU_THREADS=4` dalam `.env`, jalankan `php artisan config:cache`, lalu restart service ini. Nilai bawaan tetap 2; coba 4 kemudian 8 pada server banyak core dengan narasi yang sama. Runner membatasi jumlah thread berdasarkan CPU yang tersedia dan menyisakan setidaknya satu CPU jika memungkinkan. Satu tugas inferensi tetap dijalankan pada satu waktu. Lebih banyak thread belum tentu lebih cepat, dan jumlah thread bukan jaminan batas penggunaan CPU seluruh proses. Model masih dimuat ulang setiap tugas; worker Laravel permanen tidak menyimpan bobot Python dalam RAM.

Untuk membandingkan waktu secara langsung, jalankan `synthesize` dengan environment `TTS_CPU_THREADS=4` lalu `8`, teks/referensi/perangkat yang sama, dan output WAV berbeda. JSON CLI sekarang menampilkan `model_load_seconds` (termasuk startup/import), `reference_seconds`, `generation_seconds`, serta jumlah `threads` efektif. Penilaian kecepatan perlu dilakukan di server dengan bobot sebenarnya.

Alternatif jika menggunakan Supervisor aaPanel (sesuaikan binary/folder/user; jangan dipasang bersamaan dengan service di atas):

```ini
[program:artaswaha-learner]
process_name=%(program_name)s_%(process_num)02d
directory=/www/wwwroot/artaswaha
command=/www/server/php/84/bin/php /www/wwwroot/artaswaha/artisan queue:work learner-analysis --queue=learner-analysis --sleep=2 --tries=3 --timeout=270 --max-time=3600
user=www
numprocs=1
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
stopwaitsecs=360
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

Jika program sudah ada, periksa dan sesuaikan; jangan membuat duplikat. `retry_after` koneksi learner sudah 300 detik di kode; timeout inferensi 240, job 270, dan stopwaitsecs 360. Pertahankan cache lock bersama yang sudah digunakan aplikasi. Pastikan PHP CLI mengizinkan `proc_open`, `proc_get_status`, `proc_terminate`, dan `proc_close` yang digunakan untuk proses anak. Pengaturan CLI aaPanel dapat berbeda dari PHP-FPM.

## 7. Aktifkan setelah smoke test sukses

Installer mencetak path environment yang benar. Isi pada `.env` production:

```dotenv
LEARNER_ANALYSIS_ENABLED=false
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

Jalankan `php artisan config:cache` lalu `php artisan queue:restart`. Model tidak perlu diunduh ulang. Referensi diproses sekali per tugas dan digunakan untuk semua potongan narasi; sintesis tetap offline. Untuk pengecekan CLI, tambahkan `--reference-audio /path/ke/indonesia.wav` pada perintah `check` maupun `synthesize`. Path salah atau WAV di luar durasi tersebut akan ditolak; konfigurasi yang kosong tetap memakai suara bawaan. Perbaikan aksen harus dinilai dari hasil audio, bukan hanya status tugas.

Audio lama tidak berubah otomatis: setelah mengganti referensi, pilih **Simpan narasi**, lalu **Buat audio offline** untuk menghasilkan ulang. Tinjau hasil sebelum menyetujui audio untuk pengguna.

Lalu:

```bash
cd "$APP_DIR"
"$PHP_BIN" artisan config:clear
"$PHP_BIN" artisan optimize
"$PHP_BIN" artisan queue:restart
supervisorctl status
```

Tunggu worker lama benar-benar restart. Default cache/store production harus dapat dipakai oleh user web dan worker yang sama. Tidak perlu mengubah Reverb/domain atau membuka port baru untuk TTS.

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
