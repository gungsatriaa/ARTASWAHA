#!/usr/bin/env bash
set -Eeuo pipefail

# ARTASWAHA production deploy script for aaPanel.
APP_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
DEPLOY_REMOTE="${DEPLOY_REMOTE:-origin}"
DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"
DEPLOY_SSH_KEY="${DEPLOY_SSH_KEY:-/root/.ssh/aapanel/artaswaha_78175}"
QUEUE_PROGRAM="${QUEUE_SUPERVISOR_PROGRAM:-artaswaha-queue:*}"
LEARNER_QUEUE_PROGRAM="${LEARNER_QUEUE_SUPERVISOR_PROGRAM:-}"
REVERB_PROGRAM="${REVERB_SUPERVISOR_PROGRAM:-artaswaha-reverb}"
SUPERVISORCTL_BIN="${SUPERVISORCTL_BIN:-$(command -v supervisorctl || true)}"

fail() {
    printf 'ERROR: %s\n' "$*" >&2
    exit 1
}

dotenv_value() {
    php -r 'require "vendor/autoload.php"; $env = Dotenv\Dotenv::parse(file_get_contents(".env")); echo $env[$argv[1]] ?? "";' "$1"
}

check_http() {
    local label="$1"
    local url="$2"
    shift 2

    local status
    status="$(curl --silent --show-error --location --max-time 8 --output /dev/null --write-out '%{http_code}' "$@" "$url")" || fail "$label tidak dapat dihubungi ($url)."
    [[ "$status" == 2* ]] || fail "$label merespons HTTP $status ($url)."
    printf 'OK  %-18s HTTP %s\n' "$label" "$status"
}

check_supervisor_program() {
    local label="$1"
    local program="$2"
    local output

    output="$("$SUPERVISORCTL_BIN" status "$program")" || fail "Tidak dapat membaca status Supervisor untuk $program."
    printf '%s\n' "$output" | awk 'NF { seen = 1; if ($2 != "RUNNING") { print; failed = 1 } } END { if (!seen || failed) exit 1 }' || fail "$label belum seluruhnya RUNNING:\n$output"
    printf 'OK  %-18s RUNNING\n' "$label"
}

check_process() {
    local label="$1"
    local pattern="$2"
    local process_pid
    local process_directory

    while IFS= read -r process_pid; do
        [[ "$process_pid" =~ ^[0-9]+$ ]] || continue
        process_directory="$(readlink -f "/proc/$process_pid/cwd" 2>/dev/null || true)"
        if [[ "$process_directory" == "$APP_DIR" ]]; then
            printf 'OK  %-18s proses aktif di folder aplikasi\n' "$label"
            return
        fi
    done < <(pgrep -f "$pattern" || true)
    fail "$label tidak ditemukan di $APP_DIR. Periksa proses di Supervisor/aaPanel."
}

check_learner_worker() {
    if [[ -n "$SUPERVISORCTL_BIN" && -n "$LEARNER_QUEUE_PROGRAM" ]]; then
        check_supervisor_program "AI/TTS worker" "$LEARNER_QUEUE_PROGRAM"
    else
        check_process "AI/TTS worker" '[a]rtisan (queue([[:space:]]|$)|queue:work[[:space:]]+learner-analysis([[:space:]]|$))'
    fi
}

check_reverb_port() {
    local host="$1"
    local port="$2"

    if [[ "$host" == "0.0.0.0" || "$host" == "::" || -z "$host" ]]; then
        host="127.0.0.1"
    fi

    timeout 3 bash -c 'exec 3<>/dev/tcp/$1/$2' _ "$host" "$port" 2>/dev/null || fail "Port Reverb tidak menerima koneksi di $host:$port."
    printf 'OK  %-18s TCP %s:%s\n' "Reverb WebSocket" "$host" "$port"
}

wait_for_reverb() {
    local host="$1"
    local port="$2"
    local attempt

    if [[ "$host" == "0.0.0.0" || "$host" == "::" || -z "$host" ]]; then
        host="127.0.0.1"
    fi

    for attempt in {1..15}; do
        if timeout 2 bash -c 'exec 3<>/dev/tcp/$1/$2' _ "$host" "$port" 2>/dev/null; then
            check_reverb_port "$host" "$port"
            return
        fi
        sleep 1
    done

    fail "Reverb belum aktif setelah 15 detik."
}

cd "$APP_DIR"

printf '=================================================\n'
printf '  Memulai deployment ARTASWAHA...\n'
printf '=================================================\n'

printf '\n[0/13] Memvalidasi prasyarat dan environment production...\n'
command -v git >/dev/null || fail "git tidak ditemukan."
command -v php >/dev/null || fail "PHP CLI tidak ditemukan."
command -v composer >/dev/null || fail "Composer tidak ditemukan."
command -v npm >/dev/null || fail "npm tidak ditemukan."
command -v curl >/dev/null || fail "curl tidak ditemukan."
command -v timeout >/dev/null || fail "timeout tidak ditemukan."
[[ -f .env ]] || fail ".env tidak ditemukan; deploy dibatalkan."
[[ -f vendor/autoload.php ]] || fail "vendor belum tersedia; lakukan instalasi awal Composer terlebih dahulu."
if [[ -z "$LEARNER_QUEUE_PROGRAM" ]]; then
    LEARNER_QUEUE_PROGRAM="$(dotenv_value LEARNER_QUEUE_SUPERVISOR_PROGRAM)"
fi
if [[ -n "$SUPERVISORCTL_BIN" ]]; then
    [[ -x "$SUPERVISORCTL_BIN" ]] || fail "SUPERVISORCTL_BIN tidak dapat dijalankan: $SUPERVISORCTL_BIN"
fi
if [[ -z "$SUPERVISORCTL_BIN" || -z "$LEARNER_QUEUE_PROGRAM" ]]; then
    command -v pgrep >/dev/null || fail "pgrep tidak ditemukan; tidak dapat memeriksa proses worker."
    command -v readlink >/dev/null || fail "readlink tidak ditemukan; tidak dapat memverifikasi folder worker."
fi
[[ "$(git branch --show-current)" == "$DEPLOY_BRANCH" ]] || fail "Branch aktif bukan '$DEPLOY_BRANCH'. Atur DEPLOY_BRANCH sesuai branch yang akan di-deploy."
git diff --quiet && git diff --cached --quiet || fail "Ada perubahan file tracked. Commit/stash perubahan di server sebelum deploy."
[[ "$(dotenv_value APP_ENV)" == "production" ]] || fail "APP_ENV harus production."
[[ "$(dotenv_value APP_DEBUG)" == "false" ]] || fail "APP_DEBUG harus false."

LEARNER_WORKER_REQUIRED=false
if [[ "$(dotenv_value TTS_ENABLED)" == "true" || ( -n "$(dotenv_value AI_API_URL)" && -n "$(dotenv_value AI_MODEL)" ) ]]; then
    LEARNER_WORKER_REQUIRED=true
    check_learner_worker
else
    printf 'SKIP AI/TTS worker: kedua fitur belum dikonfigurasi.\n'
fi

printf '\n[1/13] Menyalakan mode maintenance...\n'
php artisan down --render="errors::503" --retry=60

printf '\n[2/13] Menarik pembaruan dari GitHub...\n'
if [[ -f "$DEPLOY_SSH_KEY" ]]; then
    printf -v DEPLOY_GIT_SSH_COMMAND 'ssh -i %q -o IdentitiesOnly=yes' "$DEPLOY_SSH_KEY"
    GIT_SSH_COMMAND="$DEPLOY_GIT_SSH_COMMAND" git fetch --prune "$DEPLOY_REMOTE" "$DEPLOY_BRANCH"
    GIT_SSH_COMMAND="$DEPLOY_GIT_SSH_COMMAND" git pull --ff-only "$DEPLOY_REMOTE" "$DEPLOY_BRANCH"
else
    git fetch --prune "$DEPLOY_REMOTE" "$DEPLOY_BRANCH"
    git pull --ff-only "$DEPLOY_REMOTE" "$DEPLOY_BRANCH"
fi

printf '\n[3/13] Memperbarui dependensi Composer production...\n'
COMPOSER_IPRESOLVE=4 COMPOSER_PROCESS_TIMEOUT=2000 composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

printf '\n[4/13] Menginstal dependensi NPM...\n'
npm ci --no-audit --no-fund

printf '\n[5/13] Mem-build frontend...\n'
npm run build

printf '\n[6/13] Menjalankan migrasi database...\n'
php artisan config:clear
php artisan migrate --force

printf '\n[7/13] Membuat symbolic link storage...\n'
php artisan storage:link

printf '\n[8/13] Membersihkan cache konfigurasi lama...\n'
php artisan config:clear

printf '\n[9/13] Mengoptimalkan cache Laravel...\n'
php artisan optimize

printf '\n[10/13] Memeriksa status queue worker dan Reverb...\n'
if [[ -n "$SUPERVISORCTL_BIN" ]]; then
    check_supervisor_program "Queue worker" "$QUEUE_PROGRAM"
    check_supervisor_program "Reverb process" "$REVERB_PROGRAM"
else
    printf 'Supervisor CLI tidak tersedia; memeriksa proses aktif langsung.\n'
    check_process "Queue worker" '[a]rtisan queue(:work)?([[:space:]]|$)'
    check_process "Reverb process" '[a]rtisan reverb:start'
fi

printf '\n[11/13] Mematikan mode maintenance...\n'
php artisan up

if [[ "$LEARNER_WORKER_REQUIRED" == "true" ]]; then
    check_learner_worker
fi

printf '\n[12/13] Menjalankan health check Laravel, OCR, AI, dan WebSocket...\n'
APP_URL="$(dotenv_value APP_URL)"
OCR_API_URL="$(dotenv_value OCR_API_URL)"
OCR_API_KEY="$(dotenv_value OCR_API_KEY)"
AI_API_URL="$(dotenv_value AI_API_URL)"
AI_API_KEY="$(dotenv_value AI_API_KEY)"
REVERB_SERVER_HOST="$(dotenv_value REVERB_SERVER_HOST)"
REVERB_SERVER_PORT="$(dotenv_value REVERB_SERVER_PORT)"

[[ -n "$APP_URL" ]] || fail "APP_URL belum diisi di .env."
[[ -n "$REVERB_SERVER_PORT" ]] || REVERB_SERVER_PORT=8080

check_http "Laravel /up" "${APP_URL%/}/up"

OCR_HEADERS=()
if [[ -n "$OCR_API_KEY" ]]; then
    OCR_HEADERS+=(--header "X-API-Key: $OCR_API_KEY")
fi
if [[ -n "$OCR_API_URL" ]]; then
    check_http "OCR API" "${OCR_API_URL%/}/health" "${OCR_HEADERS[@]}"
else
    printf 'SKIP OCR belum dikonfigurasi; pembuatan soal dari dokumen belum tersedia.\n'
fi

AI_HEADERS=()
if [[ -n "$AI_API_KEY" ]]; then
    AI_HEADERS+=(--header "Authorization: Bearer $AI_API_KEY")
fi
if [[ -n "$AI_API_URL" ]]; then
    check_http "AI API models" "${AI_API_URL%/}/models" "${AI_HEADERS[@]}"
else
    printf 'SKIP AI kuis belum dikonfigurasi; pembuatan soal dari dokumen belum tersedia.\n'
fi

if [[ "$(dotenv_value TTS_ENABLED)" == "true" ]]; then
    php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); exit(app(App\Services\ContentAudioService::class)->isConfigured() ? 0 : 1);' || fail "Konfigurasi TTS belum lengkap."
    printf 'OK  TTS terkonfigurasi; lakukan uji Generate di admin untuk memastikan inferensi.\n'
fi

wait_for_reverb "$REVERB_SERVER_HOST" "$REVERB_SERVER_PORT"

printf '\n[13/13] Deployment ARTASWAHA berhasil.\n'
printf '=================================================\n'
printf '  Aplikasi web sudah aktif.\n'
printf '=================================================\n'
printf '\nVerifikasi cepat:\n'
printf '  php artisan about\n'
printf '  php artisan queue:failed\n'
printf '\nWorker dan Reverb tidak direstart otomatis.\n'
printf 'Jika kode/konfigurasinya berubah, restart hanya program ARTASWAHA terkait melalui Supervisor aaPanel.\n'
printf 'Untuk perubahan TTS: restart program laravel-worker-tts-artaswaha agar model/konfigurasi baru dimuat.\n'
