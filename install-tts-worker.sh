#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
SERVICE=artaswaha-learner.service
fail() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

[[ "$(uname -s)" == Linux && "$(id -u)" -eq 0 ]] || fail 'Jalankan sebagai root di server Ubuntu.'
for executable in systemctl php runuser pgrep readlink; do
    command -v "$executable" >/dev/null || fail "$executable tidak tersedia."
done
id -u www >/dev/null || fail 'User www belum tersedia.'
PHP_BIN="$(readlink -f -- "$(command -v php)")"
[[ "$APP_DIR" =~ ^/[a-zA-Z0-9_./-]+$ && "$PHP_BIN" =~ ^/[a-zA-Z0-9_./-]+$ ]] || fail 'Path aplikasi/PHP mengandung karakter yang tidak didukung installer.'
[[ -f "$APP_DIR/artisan" && -f "$APP_DIR/.env" ]] || fail 'Artisan atau .env tidak ditemukan.'
systemctl show-environment >/dev/null || fail 'systemd tidak aktif.'

MAIN_PID="$(systemctl show "$SERVICE" --property=MainPID --value 2>/dev/null || true)"
while IFS= read -r worker_pid; do
    [[ -n "$worker_pid" && "$worker_pid" != "$MAIN_PID" ]] || continue
    worker_directory="$(readlink -f "/proc/$worker_pid/cwd" 2>/dev/null || true)"
    if [[ "$worker_directory" == "$APP_DIR" ]]; then
        fail "Worker learner-analysis PID $worker_pid masih aktif di luar service ini. Hentikan worker sementara dengan Ctrl+C; jika dikelola aaPanel, hentikan program tersebut sebelum instalasi."
    fi
done < <(pgrep -f '[a]rtisan queue:work.*learner-analysis' || true)

runuser -u www -- "$PHP_BIN" -r 'exit(PHP_VERSION_ID >= 80401 && extension_loaded("pcntl") && function_exists("proc_open") ? 0 : 1);' \
    || fail 'PHP CLI memerlukan PHP >= 8.4.1, pcntl, dan proc_open.'
runuser -u www -- test -r "$APP_DIR/.env" || fail 'User www tidak dapat membaca .env.'
runuser -u www -- test -w "$APP_DIR/storage/logs" || fail 'User www tidak dapat menulis storage/logs.'

UNIT_FILE="$(mktemp)"
trap 'rm -f -- "$UNIT_FILE"' EXIT
cat > "$UNIT_FILE" <<EOF
[Unit]
Description=ARTASWAHA learner analysis and offline TTS queue
Wants=network-online.target
After=network-online.target

[Service]
Type=exec
User=www
WorkingDirectory=$APP_DIR
ExecStart=$PHP_BIN $APP_DIR/artisan queue:work learner-analysis --queue=learner-analysis --sleep=2 --tries=3 --timeout=1830 --max-time=3600
Restart=always
RestartSec=5
KillSignal=SIGTERM
KillMode=mixed
TimeoutStopSec=1860
UMask=0027
Nice=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF
install -m 0644 "$UNIT_FILE" "/etc/systemd/system/$SERVICE"
systemctl daemon-reload
systemctl enable "$SERVICE"
systemctl restart "$SERVICE"
systemctl is-active --quiet "$SERVICE"
printf 'Worker aktif dan otomatis dimulai saat boot.\n'
printf 'Status: systemctl status %s --no-pager\nLog: journalctl -u %s -n 50 --no-pager\n' "$SERVICE" "$SERVICE"
