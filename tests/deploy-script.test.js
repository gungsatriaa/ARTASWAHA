import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';

const bash = process.platform === 'win32' ? 'C:/Program Files/Git/bin/bash.exe' : 'bash';
const available = spawnSync(bash, ['--version']).status === 0;

function runDeploy({ tts = false, analysis = false, missing = false, failAfterDeploy = false, workerProgram = 'artaswaha-learner:*', wrongDirectory = false, mixedDirectories = false, queueConnection = '' } = {}) {
    const folder = fs.mkdtempSync(path.join(os.tmpdir(), 'artaswaha-deploy-test-'));
    try {
        const bin = path.join(folder, 'fake-bin');
        fs.mkdirSync(bin);
        fs.mkdirSync(path.join(folder, 'vendor'));
        fs.writeFileSync(path.join(folder, 'vendor/autoload.php'), '');
        fs.writeFileSync(path.join(folder, '.env'), 'APP_ENV=production\n');
        fs.copyFileSync(new URL('../deploy.sh', import.meta.url), path.join(folder, 'deploy.sh'));
        const executable = (name, source) => {
            const file = path.join(bin, name);
            fs.writeFileSync(file, '#!/usr/bin/env bash\nset -eu\n' + source);
            fs.chmodSync(file, 0o755);
            return file.replaceAll('\\', '/');
        };
        executable('git', 'if [[ "$1" == branch ]]; then printf "main\\n"; fi\n');
        executable('composer', 'exit 0\n');
        executable('npm', 'exit 0\n');
        executable('sleep', 'exit 0\n');
        executable('timeout', 'exit 0\n');
        executable('curl', 'printf "200"\n');
        executable('php', `
if [[ "$1" == -r ]]; then
    [[ $# -ge 3 ]] || exit 0
    case "$3" in
        QUEUE_CONNECTION) printf '%s' "$TEST_QUEUE_CONNECTION";;
        APP_ENV) printf production;;
        APP_DEBUG) printf false;;
        TTS_ENABLED) printf '%s' "$TEST_TTS";;
        APP_URL) printf 'https://site.invalid';;
        REVERB_SERVER_HOST) printf '127.0.0.1';;
        REVERB_SERVER_PORT) printf 8080;;
        AI_API_URL) if [[ "$TEST_ANALYSIS" == true ]]; then printf 'http://model.invalid/v1'; fi;;
        AI_MODEL) if [[ "$TEST_ANALYSIS" == true ]]; then printf test-model; fi;;
        LEARNER_QUEUE_SUPERVISOR_PROGRAM) printf '%s' "$TEST_WORKER_PROGRAM";;
    esac
elif [[ "$1" == artisan ]]; then
    printf '%s\\n' "$2" >> "$TEST_LOG"
    if [[ "$2" == down ]]; then touch "$TEST_MAINTENANCE"; fi
    if [[ "$2" == up ]]; then rm -f "$TEST_MAINTENANCE"; fi
    if [[ "$2" == optimize ]]; then touch "$TEST_UPDATED"; fi
fi
`);
        const supervisor = executable('supervisorctl', `
printf 'SUPERVISOR %s\\n' "$*" >> "$TEST_LOG"
if [[ "$2" == "$TEST_WORKER_PROGRAM" && -n "$TEST_WORKER_PROGRAM" ]]; then
    if [[ "$TEST_MISSING" == 1 ]]; then exit 3; fi
    if [[ "$TEST_LATE_FAILURE" == 1 && -f "$TEST_UPDATED" ]]; then exit 3; fi
fi
printf '%s RUNNING pid 123, uptime 1:00:00\\n' "$2"
`);
        executable('pgrep', `
printf 'PROCESS %s\\n' "$*" >> "$TEST_LOG"
[[ "$TEST_MISSING" != 1 ]] || exit 1
[[ "$TEST_LATE_FAILURE" != 1 || ! -f "$TEST_UPDATED" ]] || exit 1
printf '1234\\n'
if [[ "$TEST_MIXED_DIRECTORIES" == 1 ]]; then printf '5678\\n'; fi
`);
        executable('readlink', 'if [[ "$TEST_WRONG_DIRECTORY" == 1 || ( "$TEST_MIXED_DIRECTORIES" == 1 && "$2" == /proc/1234/cwd ) ]]; then printf "/other-app\\n"; else pwd; fi\n');
        const log = path.join(folder, 'actions.log');
        const maintenance = path.join(folder, 'maintenance');
        const unixBin = bin.replaceAll('\\', '/').replace(/^([A-Za-z]):/, (_, drive) => '/' + drive.toLowerCase());
        const result = spawnSync(bash, ['-c', 'export PATH="$TEST_BIN:$PATH"; exec bash "$1"', '_', path.join(folder, 'deploy.sh').replaceAll('\\', '/')], {
            env: { ...process.env, TEST_BIN: unixBin, SUPERVISORCTL_BIN: supervisor, TEST_QUEUE_CONNECTION: queueConnection,
                LEARNER_QUEUE_SUPERVISOR_PROGRAM: '', TEST_WORKER_PROGRAM: workerProgram,
                TEST_WRONG_DIRECTORY: wrongDirectory ? '1' : '0', TEST_MIXED_DIRECTORIES: mixedDirectories ? '1' : '0',
                DEPLOY_SSH_KEY: path.join(folder, 'no-key').replaceAll('\\', '/'),
                DEPLOY_BRANCH: 'main', TEST_LOG: log.replaceAll('\\', '/'),
                TEST_MAINTENANCE: maintenance.replaceAll('\\', '/'),
                TEST_UPDATED: path.join(folder, 'updated').replaceAll('\\', '/'),
                TEST_TTS: String(tts), TEST_ANALYSIS: String(analysis),
                TEST_MISSING: missing ? '1' : '0', TEST_LATE_FAILURE: failAfterDeploy ? '1' : '0' },
            encoding: 'utf8', timeout: 15000,
        });
        return { ...result, maintenance: fs.existsSync(maintenance),
            actions: fs.existsSync(log) ? fs.readFileSync(log, 'utf8').trim().split('\n') : [] };
    } finally {
        const relative = path.relative(os.tmpdir(), folder);
        assert(relative && !relative.startsWith('..') && !path.isAbsolute(relative));
        fs.rmSync(folder, { recursive: true, force: true });
    }
}

test('disabled AI/TTS does not require an inference worker', { skip: !available }, () => {
    const result = runDeploy({ missing: true });
    assert.equal(result.status, 0, result.stdout + result.stderr);
    assert.equal(result.maintenance, false);
    assert(result.actions.includes('down'));
    assert(result.actions.includes('up'));
    assert(!result.actions.includes('queue:restart'));
    assert(!result.actions.includes('reverb:restart'));
});

for (const feature of ['tts', 'analysis']) {
    test(`missing ${feature} worker fails before enabling maintenance`, { skip: !available }, () => {
        const result = runDeploy({ [feature]: true, missing: true });
        assert.equal(result.status, 1, result.stdout + result.stderr);
        assert.equal(result.maintenance, false);
        assert(!result.actions.includes('down'));
    });
}

test('running inference worker permits a complete deployment', { skip: !available }, () => {
    const result = runDeploy({ tts: true });
    assert.equal(result.status, 0, result.stdout + result.stderr);
    assert.equal(result.maintenance, false);
    assert(!result.actions.includes('queue:restart'));
    assert(!result.actions.includes('reverb:restart'));
    assert(!result.actions.some(action => action.startsWith('SUPERVISOR restart')));
    assert.match(result.stdout, /Worker dan Reverb tidak direstart otomatis/);
    assert.match(result.stdout, /laravel-worker-tts-artaswaha/);
});

test('deployment accepts the existing aaPanel TTS program name from environment configuration', { skip: !available }, () => {
    const result = runDeploy({ tts: true, workerProgram: 'laravel-worker-tts-artaswaha:*' });
    assert.equal(result.status, 0, result.stdout + result.stderr);
    assert(result.actions.includes('SUPERVISOR status laravel-worker-tts-artaswaha:*'));
    assert.equal(result.maintenance, false);
});

test('without a configured program name deployment checks the configured shared worker command', { skip: !available }, () => {
    const result = runDeploy({ tts: true, workerProgram: '' });
    assert.equal(result.status, 0, result.stdout + result.stderr);
    assert(result.actions.some(action => action.startsWith('PROCESS -f [a]rtisan queue:work([[:space:]]+database)?')));
});

test('a learner worker from a different app does not permit deployment', { skip: !available }, () => {
    const result = runDeploy({ tts: true, workerProgram: '', wrongDirectory: true });
    assert.equal(result.status, 1, result.stdout + result.stderr);
    assert(!result.actions.includes('down'));
    assert.equal(result.maintenance, false);
});

test('deployment finds this app worker among processes from multiple apps', { skip: !available }, () => {
    const result = runDeploy({ tts: true, workerProgram: '', mixedDirectories: true });
    assert.equal(result.status, 0, result.stdout + result.stderr);
    assert.equal(result.maintenance, false);
});

test('missing learner worker with no configured program still fails before maintenance', { skip: !available }, () => {
    const result = runDeploy({ tts: true, workerProgram: '', missing: true });
    assert.equal(result.status, 1, result.stdout + result.stderr);
    assert(!result.actions.includes('down'));
});

test('worker failure during deployment is reported after the site is live again', { skip: !available }, () => {
    const result = runDeploy({ tts: true, failAfterDeploy: true });
    assert.equal(result.status, 1, result.stdout + result.stderr);
    assert.equal(result.maintenance, false);
    assert(result.actions.includes('up'));
});


test('worker detection follows Redis configured in environment', { skip: !available }, () => {
    const result = runDeploy({ analysis: true, workerProgram: '', queueConnection: 'redis' });
    assert.equal(result.status, 0, result.stdout + result.stderr);
    assert(result.actions.some(action => action.startsWith('PROCESS -f [a]rtisan queue:work([[:space:]]+redis)?')));
});
