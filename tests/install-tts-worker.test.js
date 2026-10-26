import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';

const bash = process.platform === 'win32' ? 'C:/Program Files/Git/bin/bash.exe' : 'bash';
const available = spawnSync(bash, ['--version']).status === 0;

function runInstaller(duplicate = false, invalidPhp = false) {
    const folder = fs.mkdtempSync(path.join(os.tmpdir(), 'artaswaha-worker-test-'));
    try {
        const bin = path.join(folder, 'bin');
        fs.mkdirSync(bin);
        fs.writeFileSync(path.join(folder, 'artisan'), '');
        fs.writeFileSync(path.join(folder, '.env'), 'TTS_ENABLED=true\n');
        fs.mkdirSync(path.join(folder, 'storage/logs'), { recursive: true });
        fs.copyFileSync(new URL('../install-tts-worker.sh', import.meta.url), path.join(folder, 'install-tts-worker.sh'));
        const executable = (name, source) => {
            const file = path.join(bin, name);
            fs.writeFileSync(file, '#!/usr/bin/env bash\nset -eu\n' + source);
            fs.chmodSync(file, 0o755);
        };
        executable('uname', 'echo Linux\n');
        executable('id', 'echo 0\n');
        executable('php', 'exit 0\n');
        executable('systemctl', 'printf "%s\\n" "$*" >> "$TEST_LOG"\nif [[ "${1:-}" == show ]]; then echo 0; fi\n');
        executable('pgrep', duplicate ? 'echo 1234\n' : 'exit 1\n');
        executable('readlink', 'if [[ "${*: -1}" == /proc/1234/cwd ]]; then echo "$TEST_APP"; else printf "%s\\n" "${*: -1}"; fi\n');
        executable('runuser', invalidPhp ? 'exit 1\n' : 'printf "USER %s\\n" "$*" >> "$TEST_LOG"\n');
        executable('install', 'cat "$3" > "$TEST_UNIT"\nprintf "INSTALL %s\\n" "$4" >> "$TEST_LOG"\n');
        const toPosix = value => value.replaceAll('\\', '/').replace(/^([A-Z]):/i, (_, drive) => `/${drive.toLowerCase()}`);
        const result = spawnSync(bash, ['-c', 'export PATH="$TEST_BIN:$PATH"; exec bash "$1"', '_', toPosix(path.join(folder, 'install-tts-worker.sh'))], {
            encoding: 'utf8',
            timeout: 15000,
            env: {
                ...process.env,
                PATH: `${bin}${path.delimiter}${process.env.PATH}`,
                TEST_BIN: toPosix(bin),
                TEST_APP: toPosix(folder),
                TEST_LOG: toPosix(path.join(folder, 'calls.log')),
                TEST_UNIT: toPosix(path.join(folder, 'unit.service')),
            },
        });
        return {
            ...result,
            unit: fs.existsSync(path.join(folder, 'unit.service')) ? fs.readFileSync(path.join(folder, 'unit.service'), 'utf8') : '',
            log: fs.existsSync(path.join(folder, 'calls.log')) ? fs.readFileSync(path.join(folder, 'calls.log'), 'utf8') : '',
        };
    } finally {
        assert.ok(path.resolve(folder).startsWith(path.resolve(os.tmpdir()) + path.sep));
        fs.rmSync(folder, { recursive: true, force: true });
    }
}

test('installs and enables one dedicated worker with graceful shutdown and restart', { skip: !available }, () => {
    const result = runInstaller();
    assert.equal(result.status, 0, result.stderr);
    assert.match(result.unit, /User=www/);
    assert.match(result.unit, /queue:work learner-analysis --queue=learner-analysis/);
    assert.match(result.unit, /Restart=always/);
    assert.match(result.unit, /KillMode=mixed/);
    assert.match(result.unit, /TimeoutStopSec=300/);
    assert.match(result.log, /enable artaswaha-learner.service/);
    assert.match(result.log, /restart artaswaha-learner.service/);
    assert.doesNotMatch(result.log, /queue:restart|reverb|redis/);
});

test('refuses duplicate learner worker before writing a service', { skip: !available }, () => {
    const result = runInstaller(true);
    assert.notEqual(result.status, 0);
    assert.match(result.stderr, /PID 1234/);
    assert.equal(result.unit, '');
    assert.doesNotMatch(result.log, /restart|INSTALL/);
});

test('refuses incompatible worker PHP before writing a service', { skip: !available }, () => {
    const result = runInstaller(false, true);
    assert.notEqual(result.status, 0);
    assert.match(result.stderr, /PHP CLI/);
    assert.equal(result.unit, '');
    assert.doesNotMatch(result.log, /restart|INSTALL/);
});
