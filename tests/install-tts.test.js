import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';

const bash = process.platform === 'win32' ? 'C:/Program Files/Git/bin/bash.exe' : 'bash';
const available = spawnSync(bash, ['--version']).status === 0;

function runInstaller(device, failSmoke = false) {
    const folder = fs.mkdtempSync(path.join(os.tmpdir(), 'artaswaha-tts-test-'));
    try {
        const bin = path.join(folder, 'fake-bin');
        const runtime = path.join(folder, 'storage/app/private/tools/chatterbox');
        fs.mkdirSync(bin, { recursive: true });
        fs.mkdirSync(path.join(runtime, 'bin'), { recursive: true });
        fs.copyFileSync(new URL('../install-tts.sh', import.meta.url), path.join(folder, 'install-tts.sh'));
        fs.writeFileSync(path.join(folder, '.env'), 'APP_KEY=unchanged-test-value\nTTS_ENABLED=false\n');
        const executable = (file, source) => {
            fs.writeFileSync(file, '#!/usr/bin/env bash\nset -eu\n' + source);
            fs.chmodSync(file, 0o755);
        };
        executable(path.join(bin, 'uname'), 'printf "Linux\\n"\n');
        executable(path.join(bin, 'id'), 'printf "1001\\n"\n');
        executable(path.join(bin, 'curl'), 'echo "Unexpected network request" >&2; exit 99\n');
        executable(path.join(bin, 'nvidia-smi'), 'exit 0\n');
        executable(path.join(bin, 'timeout'), 'shift 2\nexec "$@"\n');
        const python = path.join(bin, 'fake-python');
        executable(python, `
printf 'PYTHON %s\\n' "$*" >> "$TEST_LOG"
if [[ "$2" == synthesize ]]; then
    cat > "$TEST_INPUT"
    [[ "$TEST_FAIL_SMOKE" == 0 ]] || exit 9
    while [[ $# -gt 0 ]]; do
        if [[ "$1" == -w ]]; then shift; printf 'WAV' > "$1"; break; fi
        shift
    done
fi
`);
        executable(path.join(runtime, 'bin/uv'), `
printf 'UV %s\\n' "$*" >> "$TEST_LOG"
if [[ "$1" == venv ]]; then
    for target in "$@"; do :; done
    mkdir -p "$target/bin"
    cp "$TEST_PYTHON" "$target/bin/python"
    chmod +x "$target/bin/python"
elif [[ "$1" == pip && "$2" == freeze ]]; then
    printf 'chatterbox-tts==0.1.1\\n'
fi
`);
        const log = path.join(folder, 'commands.log');
        const input = path.join(folder, 'input.txt');
        const unixBin = bin.replaceAll('\\', '/').replace(/^([A-Za-z]):/, (_, drive) => '/' + drive.toLowerCase());
        const result = spawnSync(bash, ['-c', 'export PATH="$TEST_BIN:$PATH"; exec bash "$1" --device "$2"', '_', path.join(folder, 'install-tts.sh').replaceAll('\\', '/'), device], {
            env: { ...process.env, PATH: bin + path.delimiter + process.env.PATH,
                TEST_BIN: unixBin, TEST_LOG: log.replaceAll('\\', '/'), TEST_INPUT: input.replaceAll('\\', '/'),
                TEST_PYTHON: python.replaceAll('\\', '/'), TEST_FAIL_SMOKE: failSmoke ? '1' : '0' },
            encoding: 'utf8', timeout: 15000,
        });
        return {
            ...result,
            commands: fs.existsSync(log) ? fs.readFileSync(log, 'utf8') : '',
            narration: fs.existsSync(input) ? fs.readFileSync(input, 'utf8') : '',
            environment: fs.readFileSync(path.join(folder, '.env'), 'utf8'),
            frozen: fs.existsSync(path.join(runtime, 'requirements-installed.txt')),
            sample: fs.existsSync(path.join(runtime, 'samples/indonesia.wav')),
        };
    } finally {
        const relative = path.relative(os.tmpdir(), folder);
        assert(relative && !relative.startsWith('..') && !path.isAbsolute(relative));
        fs.rmSync(folder, { recursive: true, force: true });
    }
}

test('CPU installer uses isolated runtime, completes smoke test, and leaves Laravel environment intact', { skip: !available }, () => {
    const result = runInstaller('cpu');
    assert.equal(result.status, 0, result.stdout + result.stderr);
    assert.match(result.commands, /torch==2\.6\.0\+cpu.*torchaudio==2\.6\.0\+cpu/);
    assert.match(result.commands, /download --model-dir/);
    assert.match(result.commands, /check --model-dir.*--device cpu/);
    assert.match(result.narration, /Selamat datang/);
    assert.equal(result.sample, true);
    assert.equal(result.frozen, true);
    assert.equal(result.environment, 'APP_KEY=unchanged-test-value\nTTS_ENABLED=false\n');
});

test('failed native smoke test stops installation before reporting readiness', { skip: !available }, () => {
    const result = runInstaller('cpu', true);
    assert.equal(result.status, 9, result.stdout + result.stderr);
    assert.equal(result.frozen, false);
    assert.equal(result.sample, false);
    assert.doesNotMatch(result.stdout, /Smoke test selesai/);
    assert.equal(result.environment, 'APP_KEY=unchanged-test-value\nTTS_ENABLED=false\n');
});

test('CUDA installer selects CUDA wheels and passes the same device to the runner', { skip: !available }, () => {
    const result = runInstaller('cuda');
    assert.equal(result.status, 0, result.stdout + result.stderr);
    assert.match(result.commands, /torch==2\.6\.0\+cu124.*torchaudio==2\.6\.0\+cu124/);
    assert.match(result.commands, /synthesize --model-dir.*--device cuda/);
});
