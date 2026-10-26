"""Local MMS Indonesian inference. Reads narration from stdin; never downloads models."""

import argparse
import os
import re
import sys
import textwrap
import wave
from pathlib import Path

os.environ['HF_HUB_OFFLINE'] = '1'
os.environ['TRANSFORMERS_OFFLINE'] = '1'


def chunks(text: str) -> list[str]:
    sentences = re.split(r'(?<=[.!?])\s+|\n+', text.strip())
    return [part for sentence in sentences for part in textwrap.wrap(sentence, width=220)]


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument('--model-dir', required=True)
    parser.add_argument('-v', choices=['id'], required=True)
    parser.add_argument('-s', type=int, required=True)
    parser.add_argument('-w', required=True)
    args = parser.parse_args()
    text = sys.stdin.buffer.read(64001).decode('utf-8').strip()
    if not text or len(text) > 16000 or not 100 <= args.s <= 220:
        parser.error('Narasi atau kecepatan tidak valid.')

    import numpy as np
    import torch
    from transformers import AutoTokenizer, VitsModel

    torch.set_num_threads(2)
    torch.set_num_interop_threads(1)
    torch.manual_seed(42)
    directory = str(Path(args.model_dir).resolve(strict=True))
    tokenizer = AutoTokenizer.from_pretrained(directory, local_files_only=True)
    model = VitsModel.from_pretrained(directory, local_files_only=True, use_safetensors=True).eval()
    model.speaking_rate = args.s / 150
    sample_rate = model.config.sampling_rate
    total_bytes = 0
    with wave.open(args.w, 'wb') as output:
        output.setnchannels(1)
        output.setsampwidth(2)
        output.setframerate(sample_rate)
        with torch.inference_mode():
            for part in chunks(text):
                inputs = tokenizer(part, return_tensors='pt')
                samples = model(**inputs).waveform[0].cpu().numpy()
                if not np.isfinite(samples).all():
                    raise ValueError('Model menghasilkan audio tidak valid.')
                pcm = (np.clip(samples, -1, 1) * 32767).astype('<i2').tobytes()
                pause = b'\0\0' * (sample_rate // 5)
                total_bytes += len(pcm) + len(pause)
                if total_bytes + 44 > 67108864:
                    raise ValueError('Audio melebihi 64 MB. Ringkas narasi.')
                output.writeframesraw(pcm + pause)


if __name__ == '__main__':
    main()
