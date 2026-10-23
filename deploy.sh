#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
DEPLOY_REMOTE="${DEPLOY_REMOTE:-origin}"
DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"
QUEUE_PROGRAM="${QUEUE_SUPERVISOR_PROGRAM:-artaswaha-queue:*}"
REVERB_PROGRAM="${REVERB_SUPERVISOR_PROGRAM:-artaswaha-reverb}"

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

    output="$(supervisorctl status "$program")" || fail "Tidak dapat membaca status Supervisor untuk $program."
    printf '%s\n' "$output" | awk 'NF { seen = 1; if ($2 != "RUNNING") { print; failed = 1 } } END { if (!seen || failed) exit 1 }' || fail "$label belum seluruhnya RUNNING:\n$output"
    printf 'OK  %-18s RUNNING\n' "$label"
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

command -v git >/dev/null || fail "git tidak ditemukan."
command -v php >/dev/null || fail "PHP CLI tidak ditemukan."
command -v composer >/dev/null || fail "Composer tidak ditemukan."
command -v npm >/dev/null || fail "npm tidak ditemukan."
command -v curl >/dev/null || fail "curl tidak ditemukan."
command -v supervisorctl >/dev/null || fail "supervisorctl tidak ditemukan atau tidak dapat diakses."
command -v timeout >/dev/null || fail "timeout tidak ditemukan."
[[ -f .env ]] || fail ".env tidak ditemukan; deploy dibatalkan."
[[ -f vendor/autoload.php ]] || fail "vendor belum tersedia; lakukan instalasi awal Composer terlebih dahulu."
[[ "$(git branch --show-current)" == "$DEPLOY_BRANCH" ]] || fail "Branch aktif bukan '$DEPLOY_BRANCH'. Atur DEPLOY_BRANCH sesuai branch yang akan di-deploy."
[[ -z "$(git status --porcelain)" ]] || fail "Working tree tidak bersih. Commit/stash perubahan di server sebelum deploy agar tidak tertimpa."

printf '\n== Validasi environment production ==\n'
[[ "$(dotenv_value APP_ENV)" == "production" ]] || fail "APP_ENV harus production."
[[ "$(dotenv_value APP_DEBUG)" == "false" ]] || fail "APP_DEBUG harus false."

printf '\n== Update source dari GitHub ==\n'
git fetch --prune "$DEPLOY_REMOTE" "$DEPLOY_BRANCH"
git pull --ff-only "$DEPLOY_REMOTE" "$DEPLOY_BRANCH"

printf '\n== Install dependency PHP ==\n'
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

printf '\n== Build frontend ==\n'
npm ci --no-audit --no-fund
npm run build

printf '\n== Migrasi dan optimasi Laravel ==\n'
php artisan config:clear
php artisan migrate --force
php artisan optimize

printf '\n== Restart worker dan Reverb ==\n'
php artisan queue:restart
php artisan reverb:restart

printf '\n== Health checks ==\n'
APP_URL="$(dotenv_value APP_URL)"
OCR_API_URL="$(dotenv_value OCR_API_URL)"
OCR_API_KEY="$(dotenv_value OCR_API_KEY)"
AI_API_URL="$(dotenv_value AI_API_URL)"
AI_API_KEY="$(dotenv_value AI_API_KEY)"
REVERB_SERVER_HOST="$(dotenv_value REVERB_SERVER_HOST)"
REVERB_SERVER_PORT="$(dotenv_value REVERB_SERVER_PORT)"

[[ -n "$APP_URL" ]] || fail "APP_URL belum diisi di .env."
[[ -n "$OCR_API_URL" ]] || fail "OCR_API_URL belum diisi di .env."
[[ -n "$AI_API_URL" ]] || fail "AI_API_URL belum diisi di .env."
[[ -n "$REVERB_SERVER_PORT" ]] || REVERB_SERVER_PORT=8080

check_http "Laravel /up" "${APP_URL%/}/up"

OCR_HEADERS=()
if [[ -n "$OCR_API_KEY" ]]; then
    OCR_HEADERS+=(--header "X-API-Key: $OCR_API_KEY")
fi
check_http "OCR API" "${OCR_API_URL%/}/health" "${OCR_HEADERS[@]}"

AI_HEADERS=()
if [[ -n "$AI_API_KEY" ]]; then
    AI_HEADERS+=(--header "Authorization: Bearer $AI_API_KEY")
fi
check_http "AI API models" "${AI_API_URL%/}/models" "${AI_HEADERS[@]}"

wait_for_reverb "$REVERB_SERVER_HOST" "$REVERB_SERVER_PORT"
check_supervisor_program "Queue worker" "$QUEUE_PROGRAM"
check_supervisor_program "Reverb process" "$REVERB_PROGRAM"

printf '\nDeploy selesai.\n'
