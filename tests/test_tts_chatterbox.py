import contextlib
import importlib.util
import io
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
        os.environ['TTS_CPU_THREADS'] = '2'
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

    def test_cpu_threads_are_configurable_capped_and_reject_invalid_values(self):
        with patch.object(os, 'sched_getaffinity', create=True, return_value=set(range(24))):
            os.environ['TTS_CPU_THREADS'] = '8'
            torch, api, modules = self.modules()
            with patch.dict(sys.modules, modules), patch.object(runner, 'version', return_value='0.1.1'):
                runner.check_runtime(self.root, 'cpu')
            torch.set_num_threads.assert_called_once_with(8)
            for key in ['OMP_NUM_THREADS', 'MKL_NUM_THREADS', 'NUMBA_NUM_THREADS']:
                self.assertEqual('8', os.environ[key])
        with patch.object(os, 'sched_getaffinity', create=True, return_value={0, 1}):
            self.assertEqual(1, runner.cpu_threads())
        for value in ['0', '33', 'bad']:
            os.environ['TTS_CPU_THREADS'] = value
            with self.subTest(value=value), self.assertRaises(ValueError):
                runner.cpu_threads()

    def test_cli_rejects_unsupported_language_before_inference(self):
        result = subprocess.run([sys.executable, str(SERVICES / 'tts_chatterbox.py'), 'synthesize',
                                 '--model-dir', str(self.root), '-v', 'ban', '-w', str(self.root / 'bad.wav')],
                                capture_output=True, timeout=10)
        self.assertEqual(2, result.returncode)
        self.assertFalse((self.root / 'bad.wav').exists())

    def write_reference(self, seconds=8):
        reference = self.root / 'suara indonesia.wav'
        with wave.open(str(reference), 'wb') as audio:
            audio.setnchannels(1)
            audio.setsampwidth(2)
            audio.setframerate(8000)
            audio.writeframes(b'\0\0' * 8000 * seconds)
        return reference

    def test_missing_or_wrong_duration_reference_fails_before_model_load(self):
        with patch.object(runner, 'check_runtime') as check:
            for seconds in [None, 5, 11]:
                reference = self.root / 'missing.wav' if seconds is None else self.write_reference(seconds)
                with self.assertRaises(ValueError):
                    runner.synthesize(self.root, 'cpu', 'Narasi.', 150, self.root / 'output.wav', reference)
            check.assert_not_called()
        for seconds in [6, 10]:
            runner.validate_reference(self.write_reference(seconds))

    @unittest.skipIf(np is None, 'Optional TTS runtime provides numpy')
    def test_native_voice_reference_is_prepared_once_for_multiple_chunks(self):
        reference = self.write_reference()
        model = MagicMock(sr=8000)
        model.generate.return_value.detach.return_value.cpu.return_value.numpy.return_value = np.zeros(40, dtype=np.float32)
        torch, api, modules = self.modules()
        with patch.dict(sys.modules, modules), patch.object(runner, 'check_runtime'), patch.object(runner, 'load_model', return_value=model):
            timings = runner.synthesize(self.root, 'cpu', 'Kalimat satu.\nKalimat dua.', 150, self.root / 'output.wav', reference)
        model.prepare_conditionals.assert_called_once_with(str(reference))
        self.assertEqual(2, model.generate.call_count)
        self.assertTrue((self.root / 'output.wav').exists())
        self.assertEqual(2, timings['threads'])
        self.assertGreaterEqual(timings['generation_seconds'], 0)

    @unittest.skipIf(np is None, 'Optional TTS runtime provides numpy')
    def test_session_reuses_model_and_reference_but_reloads_changed_voice(self):
        reference = self.write_reference()
        model = MagicMock(sr=8000)
        base_conditionals = model.conds
        model.prepare_conditionals.side_effect = lambda path: setattr(model, 'conds', 'reference-voice')
        model.generate.return_value.detach.return_value.cpu.return_value.numpy.return_value = np.zeros(40, dtype=np.float32)
        torch, api, modules = self.modules()
        with patch.dict(sys.modules, modules), patch.object(runner, 'check_runtime') as check, patch.object(runner, 'load_model', return_value=model) as loader:
            session = runner.ChatterboxSession(self.root, 'cpu')
            cold = session.synthesize('Kalimat pertama.', 150, self.root / 'first.wav', reference)
            warm = session.synthesize('Kalimat berikutnya.', 150, self.root / 'second.wav', reference)
            self.assertFalse(cold['model_reused'])
            self.assertTrue(warm['model_reused'])
            check.assert_called_once()
            loader.assert_called_once()
            model.prepare_conditionals.assert_called_once()
            modified = reference.stat().st_mtime_ns + 1000000000
            os.utime(reference, ns=(modified, modified))
            session.synthesize('Referensi berubah.', 150, self.root / 'third.wav', reference)
            self.assertEqual(2, model.prepare_conditionals.call_count)
            session.synthesize('Kembali ke bawaan.', 150, self.root / 'fourth.wav')
            self.assertIs(model.conds, base_conditionals)

    @unittest.skipIf(np is None, 'Optional TTS runtime provides numpy')
    def test_repeated_native_alignment_hooks_are_removed_even_when_generation_fails(self):
        runner.configure_environment(self.root)
        import torch
        from chatterbox.models.t3.inference.alignment_stream_analyzer import AlignmentStreamAnalyzer

        class Attention(torch.nn.Module):
            def forward(self, **kwargs):
                return torch.zeros(1), torch.ones(1, 1, 1, 1), None

        attention = Attention()
        original_forward = attention.forward
        existing = attention.register_forward_hook(lambda module, inputs, output: None)
        tfmr = types.SimpleNamespace(layers=[types.SimpleNamespace(self_attn=attention)] * 10)
        model = types.SimpleNamespace(t3=types.SimpleNamespace(tfmr=tfmr))
        def generate(text):
            analyzer = AlignmentStreamAnalyzer(tfmr, None, (0, 1), alignment_layer_idx=9)
            model.t3.patched_model = analyzer
            attention()
            if text == 'fail':
                raise RuntimeError('generation failed')
            return 'samples'

        model.generate = generate
        for text in ['first', 'second', 'third', 'fail']:
            if text == 'fail':
                with self.assertRaises(RuntimeError):
                    runner.generate_part(model, text)
            else:
                self.assertEqual('samples', runner.generate_part(model, text))
            self.assertEqual(original_forward, attention.forward)
            self.assertEqual({existing.id}, set(attention._forward_hooks))
            self.assertFalse(hasattr(model.t3, 'patched_model'))
        existing.remove()

    def test_pipe_reader_preserves_buffered_requests_and_trailing_eof(self):
        read_fd, write_fd = os.pipe()
        try:
            os.write(write_fd, b'first\nsecond\nthird')
            os.close(write_fd)
            write_fd = None
            with patch.object(sys, 'stdin', types.SimpleNamespace(fileno=lambda: read_fd)):
                self.assertEqual([b'first\n', b'second\n', b'third'], list(runner.request_lines()))
        finally:
            os.close(read_fd)
            if write_fd is not None:
                os.close(write_fd)

    def test_pipe_reader_releases_idle_session_and_rejects_oversized_input(self):
        with patch.object(os, 'name', 'posix'), patch.object(runner.select, 'select', return_value=([], [], [])):
            self.assertEqual([], list(runner.request_lines()))
        with patch.object(runner.select, 'select', return_value=([sys.stdin], [], [])), patch.object(os, 'read', return_value=b'x' * 8192):
            with self.assertRaisesRegex(ValueError, 'terlalu besar'):
                list(runner.request_lines())

    def test_server_returns_protocol_errors_and_recycles_after_fifty_requests(self):
        destination = self.root / 'server.wav'
        destination.write_bytes(b'output')
        request = {'id': 'request-id', 'text': 'Narasi Indonesia.', 'speed': 150, 'destination': str(destination), 'reference': None}
        output = io.StringIO()
        with patch.object(runner, 'watch_parent'), patch.object(runner, 'request_lines', return_value=iter([json.dumps(request).encode()] * 51)), patch.object(runner, 'ChatterboxSession') as session, contextlib.redirect_stdout(output):
            session.return_value.synthesize.return_value = {'model_reused': True}
            runner.serve(self.root, 'cpu')
        replies = [json.loads(line.removeprefix(runner.PROTOCOL_PREFIX)) for line in output.getvalue().splitlines()]
        self.assertEqual(50, len(replies))
        self.assertEqual(50, session.return_value.synthesize.call_count)
        self.assertTrue(all(reply['status'] == 'generated' and reply['id'] == 'request-id' for reply in replies))
        self.assertFalse(replies[0]['recycle'])
        self.assertTrue(replies[-1]['recycle'])
        output = io.StringIO()
        with patch.object(runner, 'watch_parent'), patch.object(runner, 'request_lines', return_value=iter([json.dumps(request).encode()])), patch.object(runner, 'ChatterboxSession') as session, contextlib.redirect_stdout(output):
            session.return_value.synthesize.side_effect = ValueError('invalid reference')
            runner.serve(self.root, 'cpu')
        reply = json.loads(output.getvalue().removeprefix(runner.PROTOCOL_PREFIX))
        self.assertEqual('error', reply['status'])
        self.assertIn('invalid reference', reply['error'])

    def test_linux_child_exits_when_parent_dies_and_rejects_failed_registration(self):
        with patch.object(sys, 'platform', 'linux'), patch.object(os, 'getppid', side_effect=[123, 123]), patch('ctypes.CDLL') as library:
            library.return_value.prctl.return_value = 0
            runner.watch_parent()
            library.return_value.prctl.assert_called_once_with(1, runner.signal.SIGTERM, 0, 0, 0)
        with patch.object(sys, 'platform', 'linux'), patch.object(os, 'getppid', return_value=123), patch('ctypes.CDLL') as library:
            library.return_value.prctl.return_value = -1
            with self.assertRaises(OSError):
                runner.watch_parent()
        with patch.object(sys, 'platform', 'linux'), patch.object(os, 'getppid', side_effect=[123, 1]), patch('ctypes.CDLL') as library:
            library.return_value.prctl.return_value = 0
            with self.assertRaises(SystemExit) as error:
                runner.watch_parent()
            self.assertEqual(143, error.exception.code)

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
        model.prepare_conditionals.assert_not_called()

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
