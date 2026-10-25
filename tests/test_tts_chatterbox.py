import contextlib
import importlib.util
import json
import os
import subprocess
import sys
import tempfile
import types
import unittest
import wave
from pathlib import Path
from unittest.mock import MagicMock, patch

SERVICES = Path(__file__).resolve().parents[1] / 'app' / 'Services'
sys.path.insert(0, str(SERVICES))
spec = importlib.util.spec_from_file_location('tts_chatterbox', SERVICES / 'tts_chatterbox.py')
runner = importlib.util.module_from_spec(spec)
spec.loader.exec_module(runner)
try:
    import numpy as np
except ImportError:
    np = None


class ChatterboxRunnerTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name) / 'models'
        self.root.mkdir()
        self.environment = patch.dict(os.environ)
        self.environment.start()
        self.addCleanup(self.environment.stop)
        self.write_assets()

    def write_assets(self):
        manifest = {'chatterbox_version': '0.1.1', 'base_revision': runner.BASE_REVISION,
                    'indonesian_revision': runner.INDONESIAN_REVISION}
        (self.root / 'manifest.json').write_text(json.dumps(manifest))
        for name in [*(f'base/{name}' for name in runner.BASE_FILES), 'indonesian/t3_cfg.safetensors']:
            file = self.root / name
            file.parent.mkdir(exist_ok=True)
            file.write_bytes(b'asset')

    def modules(self):
        torch = MagicMock()
        torch.inference_mode.side_effect = contextlib.nullcontext
        api = types.ModuleType('chatterbox.tts')
        for name in ['ChatterboxTTS', 'Conditionals', 'EnTokenizer', 'S3Gen', 'T3', 'VoiceEncoder']:
            setattr(api, name, MagicMock())
        return torch, api, {'torch': torch, 'chatterbox': types.ModuleType('chatterbox'),
                            'chatterbox.tts': api}

    def test_missing_empty_or_wrong_revision_assets_are_rejected(self):
        runner.validate_assets(self.root)
        (self.root / 'base/conds.pt').write_bytes(b'')
        with self.assertRaisesRegex(ValueError, 'belum lengkap'):
            runner.validate_assets(self.root)
        self.write_assets()
        (self.root / 'manifest.json').write_text('{}')
        with self.assertRaisesRegex(ValueError, 'Revision'):
            runner.validate_assets(self.root)

    def test_download_only_fetches_pinned_required_assets_and_writes_manifest(self):
        def fetch(repo, filename, **options):
            self.assertFalse(options['token'])
            destination = Path(options['local_dir']) / filename
            destination.parent.mkdir(parents=True, exist_ok=True)
            destination.write_bytes(b'asset')

        fetch_mock = MagicMock(side_effect=fetch)
        with patch.dict(sys.modules, {'huggingface_hub': types.SimpleNamespace(hf_hub_download=fetch_mock)}):
            runner.download(self.root)
        self.assertEqual(5, fetch_mock.call_count)
        for call in fetch_mock.call_args_list[:4]:
            self.assertEqual(runner.BASE_REVISION, call.kwargs['revision'])
        self.assertEqual('t3_cfg.safetensors', fetch_mock.call_args.args[1])
        self.assertEqual(runner.INDONESIAN_REVISION, fetch_mock.call_args.kwargs['revision'])
        self.assertFalse(any('t3_cfg.pt' in call.args for call in fetch_mock.call_args_list))
        runner.validate_assets(self.root)

    def test_loader_uses_indonesian_weights_and_cpu_safe_deserialization(self):
        torch, api, modules = self.modules()
        state = {'indonesian': 'weights'}
        safetensors = types.ModuleType('safetensors')
        safe_torch = types.SimpleNamespace(load_file=MagicMock(return_value=state))
        modules.update({'safetensors': safetensors, 'safetensors.torch': safe_torch})
        with patch.dict(sys.modules, modules):
            runner.load_model(self.root, 'cpu')
        api.T3.return_value.load_state_dict.assert_called_once_with(state)
        safe_torch.load_file.assert_called_once_with(str(self.root / 'indonesian/t3_cfg.safetensors'), device='cpu')
        self.assertEqual(2, torch.load.call_count)
        for call in torch.load.call_args_list:
            self.assertEqual('cpu', call.kwargs['map_location'])
            self.assertTrue(call.kwargs['weights_only'])
        api.Conditionals.load.assert_called_once_with(self.root / 'base/conds.pt', map_location='cpu')

    def test_requested_cuda_is_not_silently_changed_to_cpu(self):
        torch, api, modules = self.modules()
        torch.cuda.is_available.return_value = False
        with patch.dict(sys.modules, modules), patch.object(runner, 'version', return_value='0.1.1'):
            with self.assertRaisesRegex(ValueError, 'CUDA'):
                runner.check_runtime(self.root, 'cuda')
        api.ChatterboxTTS.assert_not_called()
        self.assertEqual('1', os.environ['HF_HUB_OFFLINE'])
        self.assertEqual('1', os.environ['TRANSFORMERS_OFFLINE'])
        self.assertEqual('2', os.environ['OMP_NUM_THREADS'])

    def test_invalid_text_and_speed_fail_before_loading_model(self):
        with patch.object(runner, 'load_model') as loader:
            for text, speed in [(' ', 150), ('a' * 8001, 150), ('Narasi', 50), ('Narasi', 221)]:
                with self.subTest(speed=speed, length=len(text)), self.assertRaises(ValueError):
                    runner.synthesize(self.root, 'cpu', text, speed, self.root / 'test.wav')
            loader.assert_not_called()

    def test_cli_rejects_unsupported_language_before_inference(self):
        result = subprocess.run([sys.executable, str(SERVICES / 'tts_chatterbox.py'), 'synthesize',
                                 '--model-dir', str(self.root), '-v', 'ban', '-w', str(self.root / 'bad.wav')],
                                capture_output=True, timeout=10)
        self.assertEqual(2, result.returncode)
        self.assertFalse((self.root / 'bad.wav').exists())

    @unittest.skipIf(np is None, 'Optional TTS runtime provides numpy')
    def test_synthesis_writes_pcm_wav_and_applies_requested_tempo(self):
        model = MagicMock(sr=8000)
        model.generate.return_value.detach.return_value.cpu.return_value.numpy.return_value = np.zeros(40, dtype=np.float32)
        torch, api, modules = self.modules()
        stretch = MagicMock(side_effect=lambda samples, rate: samples)
        modules['librosa'] = types.SimpleNamespace(effects=types.SimpleNamespace(time_stretch=stretch))
        destination = self.root / 'valid.wav'
        with patch.dict(sys.modules, modules), patch.object(runner, 'check_runtime'), patch.object(runner, 'load_model', return_value=model):
            runner.synthesize(self.root, 'cpu', 'Kalimat satu.\nKalimat dua.', 180, destination)
        with wave.open(str(destination), 'rb') as audio:
            self.assertEqual(8000, audio.getframerate())
            self.assertEqual(1, audio.getnchannels())
            self.assertEqual(2, audio.getsampwidth())
            self.assertEqual(3280, audio.getnframes())
        self.assertEqual(2, stretch.call_count)
        self.assertEqual(1.2, stretch.call_args.kwargs['rate'])

    @unittest.skipIf(np is None, 'Optional TTS runtime provides numpy')
    def test_invalid_audio_exception_cancellation_and_size_limit_remove_output(self):
        torch, api, modules = self.modules()
        for failure in ['nan', 'exception', 'cancel', 'size']:
            with self.subTest(failure=failure):
                model = MagicMock(sr=8000)
                tensor = model.generate.return_value
                tensor.detach.return_value.cpu.return_value.numpy.return_value = np.array([np.nan] if failure == 'nan' else [0.0], dtype=np.float32)
                if failure == 'exception':
                    model.generate.side_effect = RuntimeError('inference failed')
                elif failure == 'cancel':
                    model.generate.side_effect = KeyboardInterrupt()
                destination = self.root / f'{failure}.wav'
                limit = 64 if failure == 'size' else runner.MAX_BYTES
                with patch.dict(sys.modules, modules), patch.object(runner, 'check_runtime'), patch.object(runner, 'load_model', return_value=model), patch.object(runner, 'MAX_BYTES', limit):
                    with self.assertRaises((ValueError, RuntimeError, KeyboardInterrupt)):
                        runner.synthesize(self.root, 'cpu', 'Narasi.', 150, destination)
                self.assertFalse(destination.exists())


if __name__ == '__main__':
    unittest.main()
