"""Pinned Indonesian Chatterbox CLI. Only the download command uses the network."""

import argparse
import gc
import json
import os
import signal
import sys
import time
import wave
from importlib.metadata import version
from pathlib import Path

BASE_REPO = 'ResembleAI/chatterbox'
BASE_REVISION = '5bb1f6ee58e50c3b8d408bc82a6d3740c2db6e18'
INDONESIAN_REPO = 'grandhigh/Chatterbox-TTS-Indonesian'
INDONESIAN_REVISION = '15027c10d34ac7bbb2796ec5cd727a17a3bf862b'
BASE_FILES = ('ve.pt', 's3gen.pt', 'tokenizer.json', 'conds.pt')
MAX_BYTES = 10485760


def configure_environment(directory: Path, offline: bool = True) -> None:
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
        'OMP_NUM_THREADS': '2',
        'MKL_NUM_THREADS': '2',
        'NUMBA_NUM_THREADS': '2',
    }.items():
        os.environ[name] = value


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
    configure_environment(directory)
    validate_assets(directory)
    if version('chatterbox-tts') != '0.1.1':
        raise ValueError('Gunakan paket chatterbox-tts==0.1.1 dari requirements proyek.')
    import torch
    from chatterbox.tts import ChatterboxTTS, Conditionals, EnTokenizer, S3Gen, T3, VoiceEncoder

    if device == 'cuda' and not torch.cuda.is_available():
        raise ValueError('CUDA diminta tetapi tidak tersedia. Periksa build PyTorch dan driver.')
    torch.set_num_threads(2)
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


def synthesize(directory: Path, device: str, text: str, speed: int, destination: Path) -> None:
    if not text.strip() or len(text) > 8000 or not 100 <= speed <= 220:
        raise ValueError('Narasi atau kecepatan tidak valid.')
    check_runtime(directory, device)
    import numpy as np
    import torch
    from tts_mms import chunks

    torch.manual_seed(42)
    model = load_model(directory, device)
    total_bytes = 0
    try:
        with wave.open(str(destination), 'wb') as output:
            output.setnchannels(1)
            output.setsampwidth(2)
            output.setframerate(model.sr)
            with torch.inference_mode():
                for part in chunks(text):
                    samples = model.generate(part).detach().cpu().numpy().reshape(-1)
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


def main() -> None:
    signal.signal(signal.SIGTERM, lambda signum, frame: sys.exit(143))
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('command', choices=['download', 'check', 'synthesize'])
    parser.add_argument('--model-dir', type=Path, required=True)
    parser.add_argument('--device', choices=['cpu', 'cuda'], default='cpu')
    parser.add_argument('-v', choices=['id'], default='id')
    parser.add_argument('-s', type=int, default=150)
    parser.add_argument('-w', type=Path)
    args = parser.parse_args()
    directory = args.model_dir.resolve()
    started = time.perf_counter()
    if args.command == 'download':
        download(directory)
    elif args.command == 'check':
        check_runtime(directory, args.device)
        print('Runtime dan aset tersedia. Uji synthesize untuk memverifikasi bobot/inferensi.')
    else:
        if args.w is None:
            parser.error('synthesize memerlukan -w untuk lokasi output.')
        text = sys.stdin.buffer.read(32001).decode('utf-8').strip()
        synthesize(directory, args.device, text, args.s, args.w)
        report = {'status': 'generated', 'seconds': round(time.perf_counter() - started, 2),
                  'device': args.device, 'wav_bytes': args.w.stat().st_size}
        if sys.platform.startswith('linux'):
            import resource
            report['peak_rss_mib'] = round(resource.getrusage(resource.RUSAGE_SELF).ru_maxrss / 1024, 2)
        if args.device == 'cuda':
            import torch
            report['peak_vram_reserved_mib'] = round(torch.cuda.max_memory_reserved() / 1048576, 2)
        print(json.dumps(report))


if __name__ == '__main__':
    main()
