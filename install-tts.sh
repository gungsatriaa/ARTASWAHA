#!/usr/bin/env bash
set -Eeuo pipefail

# Install only the optional CLI TTS runtime. Never edit Laravel's .env or database.
APP_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
TTS_ROOT="$APP_DIR/storage/app/private/tools/chatterbox"
DEVICE=cpu

fail() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

if [[ "${1:-}" == "--help" ]]; then
    printf 'Usage: bash install-tts.sh [--device cpu|cuda]\n'
    printf 'Run as the Laravel worker user. Runtime/model/cache stay in storage/app/private/tools/chatterbox.\n'
    exit 0
fi
if [[ $# -gt 0 ]]; then
    [[ $# -eq 2 && "$1" == "--device" ]] || fail "Gunakan --device cpu atau --device cuda."
    DEVICE="$2"
fi
[[ "$DEVICE" == "cpu" || "$DEVICE" == "cuda" ]] || fail "Device harus cpu atau cuda."
[[ "$(uname -s)" == "Linux" ]] || fail "Installer ini untuk server Linux."
[[ "$(id -u)" -ne 0 ]] || fail "Jalankan sebagai user worker, contoh: sudo -u www bash install-tts.sh --device cpu"
for executable in curl timeout; do
    command -v "$executable" >/dev/null || fail "$executable belum tersedia."
done
if [[ "$DEVICE" == "cuda" ]]; then
    command -v nvidia-smi >/dev/null || fail "CUDA memerlukan driver NVIDIA; periksa nvidia-smi terlebih dahulu."
    nvidia-smi >/dev/null || fail "Driver NVIDIA tidak dapat diakses oleh user ini."
fi

umask 027
mkdir -p "$TTS_ROOT/samples"
export UV_CACHE_DIR="$TTS_ROOT/cache/uv"
export UV_PYTHON_INSTALL_DIR="$TTS_ROOT/python"
export PIP_DISABLE_PIP_VERSION_CHECK=1
export PYTHONUTF8=1
export OMP_NUM_THREADS=2
export MKL_NUM_THREADS=2
export NUMBA_NUM_THREADS=2

printf 'Runtime: %s\nDevice: %s\n' "$TTS_ROOT" "$DEVICE"
printf 'Pastikan RAM dan ruang disk tersedia; installer akan mengunduh paket dan bobot beberapa GB.\n'
if [[ ! -x "$TTS_ROOT/bin/uv" ]]; then
    curl --fail --location --silent --show-error https://astral.sh/uv/install.sh --output "$TTS_ROOT/install-uv.sh"
    UV_UNMANAGED_INSTALL="$TTS_ROOT/bin" sh "$TTS_ROOT/install-uv.sh"
fi
if [[ ! -x "$TTS_ROOT/venv/bin/python" ]]; then
    "$TTS_ROOT/bin/uv" venv --python 3.11 --seed "$TTS_ROOT/venv"
fi
PYTHON="$TTS_ROOT/venv/bin/python"
INDEX_URL=https://download.pytorch.org/whl/cpu
TORCH_VERSION=2.6.0+cpu
if [[ "$DEVICE" == "cuda" ]]; then
    INDEX_URL=https://download.pytorch.org/whl/cu124
    TORCH_VERSION=2.6.0+cu124
fi
"$TTS_ROOT/bin/uv" pip install --python "$PYTHON" "torch==$TORCH_VERSION" "torchaudio==$TORCH_VERSION" --index-url "$INDEX_URL"
"$TTS_ROOT/bin/uv" pip install --python "$PYTHON" --requirement "$APP_DIR/app/Services/tts_chatterbox_requirements.txt"
"$TTS_ROOT/bin/uv" pip check --python "$PYTHON"

RUNNER="$APP_DIR/app/Services/tts_chatterbox.py"
"$PYTHON" "$RUNNER" download --model-dir "$TTS_ROOT/models"
"$PYTHON" "$RUNNER" check --model-dir "$TTS_ROOT/models" --device "$DEVICE"
printf 'Selamat datang. Mari belajar teka-teki tradisional Bali.\n' |
    timeout --kill-after=10 240 "$PYTHON" "$RUNNER" synthesize --model-dir "$TTS_ROOT/models" --device "$DEVICE" -v id -s 150 -w "$TTS_ROOT/samples/indonesia.wav"
"$TTS_ROOT/bin/uv" pip freeze --python "$PYTHON" > "$TTS_ROOT/requirements-installed.txt"

printf '\nSmoke test selesai. Dengarkan samples/indonesia.wav sebelum menyetujui suara.\n'
printf 'Tambahkan ke .env setelah pengujian:\n'
printf 'TTS_ENABLED=true\nTTS_ENGINE=chatterbox\nTTS_DEVICE=%s\n' "$DEVICE"
printf 'TTS_PYTHON="%s"\nTTS_CHATTERBOX_MODEL_PATH="%s"\n' "$PYTHON" "$TTS_ROOT/models"
printf 'Lalu refresh config dan restart worker learner-analysis. Ollama tidak dipasang.\n'
