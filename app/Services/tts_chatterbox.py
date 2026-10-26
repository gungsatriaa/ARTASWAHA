"""Pinned Indonesian Chatterbox CLI. Only the download command uses the network."""

import argparse
import contextlib
import gc
import json
import os
import signal
import select
import sys
import time
import traceback
import wave
from importlib.metadata import version
from pathlib import Path

BASE_REPO = 'ResembleAI/chatterbox'
BASE_REVISION = '5bb1f6ee58e50c3b8d408bc82a6d3740c2db6e18'
INDONESIAN_REPO = 'grandhigh/Chatterbox-TTS-Indonesian'
INDONESIAN_REVISION = '15027c10d34ac7bbb2796ec5cd727a17a3bf862b'
BASE_FILES = ('ve.pt', 's3gen.pt', 'tokenizer.json', 'conds.pt')
MAX_BYTES = 10485760
PROTOCOL_PREFIX = 'ARTASWAHA_TTS '


def cpu_threads() -> int:
    requested = int(os.environ.get('TTS_CPU_THREADS', '2'))
    if not 1 <= requested <= 32:
        raise ValueError('TTS_CPU_THREADS harus antara 1 sampai 32.')
    available = len(os.sched_getaffinity(0)) if hasattr(os, 'sched_getaffinity') else (os.cpu_count() or 1)
    return min(requested, max(1, available - 1))


def configure_environment(directory: Path, offline: bool = True) -> int:
    threads = cpu_threads()
    cache = directory.parent / 'cache'
    cache.mkdir(parents=True, exist_ok=True)
    for name, value in {
        'HF_HOME': str(cache / 'huggingface'),
        'TORCH_HOME': str(cache / 'torch'),
        'NUMBA_CACHE_DIR': str(cache / 'numba'),
        'HF_HUB_OFFLINE': '1' if offline else '0',
        'TRANSFORMERS_OFFLINE': '1' if offline else '0',
        'HF_HUB_DISABLE_TELEMETRY': '1',
        'HF_HUB_DISABLE_XET': '1',
        'TOKENIZERS_PARALLELISM': 'false',
        'OMP_NUM_THREADS': str(threads),
        'MKL_NUM_THREADS': str(threads),
        'NUMBA_NUM_THREADS': str(threads),
    }.items():
        os.environ[name] = value
    return threads


def download(directory: Path) -> None:
    configure_environment(directory, offline=False)
    from huggingface_hub import hf_hub_download

    directory.mkdir(parents=True, exist_ok=True)
    for filename in BASE_FILES:
        hf_hub_download(BASE_REPO, filename, revision=BASE_REVISION,
                        local_dir=directory / 'base', token=False)
    hf_hub_download(INDONESIAN_REPO, 't3_cfg.safetensors', revision=INDONESIAN_REVISION,
                    local_dir=directory / 'indonesian', token=False)
    manifest = {'chatterbox_version': '0.1.1', 'base_revision': BASE_REVISION,
                'indonesian_revision': INDONESIAN_REVISION}
    temporary = directory / 'manifest.json.tmp'
    temporary.write_text(json.dumps(manifest, indent=2), encoding='utf-8')
    temporary.replace(directory / 'manifest.json')
    print('Aset Chatterbox Indonesia tersimpan lokal. Jalankan check dan smoke test.')


def validate_assets(directory: Path) -> None:
    manifest = json.loads((directory / 'manifest.json').read_text(encoding='utf-8'))
    if (manifest.get('chatterbox_version') != '0.1.1'
            or manifest.get('base_revision') != BASE_REVISION
            or manifest.get('indonesian_revision') != INDONESIAN_REVISION):
        raise ValueError('Revision model tidak cocok. Jalankan installer dari versi kode ini.')
    required = [directory / 'base' / name for name in BASE_FILES]
    required.append(directory / 'indonesian' / 't3_cfg.safetensors')
    if any(not path.is_file() or path.stat().st_size == 0 for path in required):
        raise ValueError('Aset model belum lengkap. Jalankan download terlebih dahulu.')


def check_runtime(directory: Path, device: str) -> None:
    threads = configure_environment(directory)
    validate_assets(directory)
    if version('chatterbox-tts') != '0.1.1':
        raise ValueError('Gunakan paket chatterbox-tts==0.1.1 dari requirements proyek.')
    import torch
    from chatterbox.tts import ChatterboxTTS, Conditionals, EnTokenizer, S3Gen, T3, VoiceEncoder

    if device == 'cuda' and not torch.cuda.is_available():
        raise ValueError('CUDA diminta tetapi tidak tersedia. Periksa build PyTorch dan driver.')
    torch.set_num_threads(threads)
    torch.set_num_interop_threads(1)


def load_model(directory: Path, device: str):
    import torch
    from safetensors.torch import load_file
    from chatterbox.tts import ChatterboxTTS, Conditionals, EnTokenizer, S3Gen, T3, VoiceEncoder

    voice_encoder = VoiceEncoder()
    voice_encoder.load_state_dict(torch.load(directory / 'base/ve.pt', map_location='cpu', weights_only=True))
    voice_encoder.to(device).eval()
    text_model = T3()
    state = load_file(str(directory / 'indonesian/t3_cfg.safetensors'), device='cpu')
    text_model.load_state_dict(state)
    del state
    gc.collect()
    text_model.to(device).eval()
    speech_model = S3Gen()
    speech_model.load_state_dict(torch.load(directory / 'base/s3gen.pt', map_location='cpu', weights_only=True))
    speech_model.to(device).eval()
    tokenizer = EnTokenizer(str(directory / 'base/tokenizer.json'))
    conditionals = Conditionals.load(directory / 'base/conds.pt', map_location='cpu').to(device)
    return ChatterboxTTS(t3=text_model, s3gen=speech_model, ve=voice_encoder,
                        tokenizer=tokenizer, device=device, conds=conditionals)


def validate_reference(reference: Path | None) -> None:
    if reference is None:
        return
    if not reference.is_file() or not os.access(reference, os.R_OK):
        raise ValueError('Rekaman referensi tidak ditemukan atau tidak dapat dibaca.')
    with wave.open(str(reference), 'rb') as audio:
        duration = audio.getnframes() / audio.getframerate()
        if audio.getcomptype() != 'NONE' or not 6 <= duration <= 10:
            raise ValueError('Gunakan referensi WAV PCM sepanjang 6 sampai 10 detik.')


def generate_part(model, text: str):
    # Chatterbox 0.1.1 installs a new hook and wraps forward on every inference.
    attention = model.t3.tfmr.layers[9].self_attn
    original_forward = attention.forward
    original_hooks = set(attention._forward_hooks)
    try:
        return model.generate(text)
    finally:
        attention.forward = original_forward
        for key in set(attention._forward_hooks) - original_hooks:
            del attention._forward_hooks[key]
        if hasattr(model.t3, 'patched_model'):
            del model.t3.patched_model


def render_audio(model, text: str, speed: int, destination: Path) -> None:
    import numpy as np
    import torch
    from tts_mms import chunks

    total_bytes = 0
    try:
        with wave.open(str(destination), 'wb') as output:
            output.setnchannels(1)
            output.setsampwidth(2)
            output.setframerate(model.sr)
            with torch.inference_mode():
                for part in chunks(text):
                    samples = generate_part(model, part).detach().cpu().numpy().reshape(-1)
                    if not np.isfinite(samples).all() or samples.size == 0:
                        raise ValueError('Model menghasilkan audio tidak valid.')
                    if speed != 150:
                        import librosa
                        samples = librosa.effects.time_stretch(samples, rate=speed / 150)
                    pcm = (np.clip(samples, -1, 1) * 32767).astype('<i2').tobytes()
                    pause = b'\0\0' * (model.sr // 5)
                    total_bytes += len(pcm) + len(pause)
                    if total_bytes + 44 > MAX_BYTES:
                        raise ValueError('Audio melebihi 10 MB. Ringkas narasi.')
                    output.writeframesraw(pcm + pause)
    except BaseException:
        destination.unlink(missing_ok=True)
        raise


class ChatterboxSession:
    def __init__(self, directory: Path, device: str):
        self.directory = directory
        self.device = device
        self.model = None
        self.base_conditionals = None
        self.reference_key = None

    def synthesize(self, text: str, speed: int, destination: Path,
                   reference: Path | None = None) -> dict:
        if not isinstance(text, str) or not text.strip() or len(text) > 8000 or type(speed) is not int or not 100 <= speed <= 220:
            raise ValueError('Narasi atau kecepatan tidak valid.')
        validate_reference(reference)
        started = time.perf_counter()
        reused = self.model is not None
        if not reused:
            check_runtime(self.directory, self.device)
            self.model = load_model(self.directory, self.device)
            self.base_conditionals = self.model.conds
        loaded = time.perf_counter()
        import torch
        torch.manual_seed(42)
        reference_key = None
        if reference is not None:
            attributes = reference.stat()
            reference_key = (str(reference.resolve()), attributes.st_mtime_ns, attributes.st_size)
        if reference_key != self.reference_key:
            if reference is None:
                self.model.conds = self.base_conditionals
            else:
                with torch.inference_mode():
                    self.model.prepare_conditionals(str(reference))
            self.reference_key = reference_key
        prepared = time.perf_counter()
        render_audio(self.model, text, speed, destination)
        return {'model_load_seconds': round(loaded - started, 2),
                'reference_seconds': round(prepared - loaded, 2),
                'generation_seconds': round(time.perf_counter() - prepared, 2),
                'model_reused': reused, 'threads': cpu_threads()}


def synthesize(directory: Path, device: str, text: str, speed: int, destination: Path,
               reference: Path | None = None) -> dict:
    return ChatterboxSession(directory, device).synthesize(text, speed, destination, reference)


def request_lines():
    pending = b''
    while True:
        while b'\n' not in pending:
            if os.name == 'posix' and not select.select([sys.stdin], [], [], 900)[0]:
                return
            chunk = os.read(sys.stdin.fileno(), 8192)
            if not chunk:
                if pending:
                    yield pending
                return
            pending += chunk
            if len(pending) > 131072:
                raise ValueError('Permintaan TTS terlalu besar.')
        newline = pending.index(b'\n') + 1
        line, pending = pending[:newline], pending[newline:]
        yield line


def watch_parent() -> None:
    if sys.platform.startswith('linux'):
        import ctypes
        parent_pid = os.getppid()
        if ctypes.CDLL(None, use_errno=True).prctl(1, signal.SIGTERM, 0, 0, 0) != 0:
            raise OSError('Tidak dapat mengaktifkan penghentian worker bersama proses induk.')
        if os.getppid() != parent_pid:
            sys.exit(143)


def serve(directory: Path, device: str) -> None:
    watch_parent()
    session = ChatterboxSession(directory, device)
    # One request at a time over private stdin/stdout; no listener or network API.
    for number, line in zip(range(50), request_lines()):
        request_id = None
        started = time.perf_counter()
        try:
            if len(line) > 131072:
                raise ValueError('Permintaan TTS terlalu besar.')
            request = json.loads(line)
            request_id = request['id']
            if not isinstance(request_id, str) or len(request_id) > 64:
                raise ValueError('ID permintaan tidak valid.')
            destination = Path(request['destination'])
            reference = Path(request['reference']) if request.get('reference') else None
            with contextlib.redirect_stdout(sys.stderr):
                timings = session.synthesize(request['text'], request['speed'], destination, reference)
            result = {'id': request_id, 'status': 'generated', 'seconds': round(time.perf_counter() - started, 2),
                      'wav_bytes': destination.stat().st_size, 'recycle': number == 49, **timings}
        except Exception as error:
            frames = traceback.extract_tb(error.__traceback__)[-20:]
            result = {'id': request_id, 'status': 'error', 'error': f'{type(error).__name__}: {error}',
                      'traceback': '\n'.join(f'{frame.filename}:{frame.lineno} in {frame.name}' for frame in frames)}
            print(PROTOCOL_PREFIX + json.dumps(result), flush=True)
            return
        print(PROTOCOL_PREFIX + json.dumps(result), flush=True)


def main() -> None:
    signal.signal(signal.SIGTERM, lambda signum, frame: sys.exit(143))
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('command', choices=['download', 'check', 'synthesize', 'serve'])
    parser.add_argument('--model-dir', type=Path, required=True)
    parser.add_argument('--device', choices=['cpu', 'cuda'], default='cpu')
    parser.add_argument('--reference-audio', type=Path)
    parser.add_argument('-v', choices=['id'], default='id')
    parser.add_argument('-s', type=int, default=150)
    parser.add_argument('-w', type=Path)
    args = parser.parse_args()
    directory = args.model_dir.resolve()
    started = time.perf_counter()
    if args.command == 'download':
        download(directory)
    elif args.command == 'serve':
        serve(directory, args.device)
    elif args.command == 'check':
        validate_reference(args.reference_audio)
        check_runtime(directory, args.device)
        print('Runtime dan aset tersedia. Uji synthesize untuk memverifikasi bobot/inferensi.')
    else:
        if args.w is None:
            parser.error('synthesize memerlukan -w untuk lokasi output.')
        text = sys.stdin.buffer.read(32001).decode('utf-8').strip()
        timings = synthesize(directory, args.device, text, args.s, args.w, args.reference_audio)
        report = {'status': 'generated', 'seconds': round(time.perf_counter() - started, 2),
                  'device': args.device, 'wav_bytes': args.w.stat().st_size, **timings}
        if sys.platform.startswith('linux'):
            import resource
            report['peak_rss_mib'] = round(resource.getrusage(resource.RUSAGE_SELF).ru_maxrss / 1024, 2)
        if args.device == 'cuda':
            import torch
            report['peak_vram_reserved_mib'] = round(torch.cuda.max_memory_reserved() / 1048576, 2)
        print(json.dumps(report))


if __name__ == '__main__':
    main()
