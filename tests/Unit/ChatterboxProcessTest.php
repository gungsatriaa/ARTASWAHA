<?php

namespace Tests\Unit;

use App\Services\ChatterboxProcess;
use PHPUnit\Framework\TestCase;
use Throwable;

class ChatterboxProcessTest extends TestCase
{
    private array $clients = [];

    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->clients as $client) {
            $client->stop();
        }
        foreach ($this->files as $file) {
            unlink($file);
        }
        parent::tearDown();
    }

    public function test_multiple_requests_reuse_one_process_and_handle_split_responses(): void
    {
        [$client, $command] = $this->fixture();
        $first = $client->generate($command, ['TEST_TTS_MODE' => 'split'], $this->request(), 3);
        $second = $client->generate($command, ['TEST_TTS_MODE' => 'split'], $this->request(), 3);

        $this->assertSame($first['pid'], $second['pid']);
        $this->assertSame(1, $first['request_number']);
        $this->assertSame(2, $second['request_number']);
        $this->assertNotSame($first['id'], $second['id']);
    }

    public function test_configuration_changes_and_explicit_shutdown_start_a_fresh_process(): void
    {
        [$client, $command] = $this->fixture();
        $first = $client->generate($command, ['TEST_TTS_MODE' => 'normal'], $this->request(), 3);
        $changed = $client->generate($command, ['TEST_TTS_MODE' => 'split'], $this->request(), 3);
        $client->stop();
        $restarted = $client->generate($command, ['TEST_TTS_MODE' => 'split'], $this->request(), 3);

        $this->assertNotSame($first['pid'], $changed['pid']);
        $this->assertNotSame($changed['pid'], $restarted['pid']);
        $this->assertSame(1, $restarted['request_number']);
    }

    public function test_crashes_bad_protocol_and_timeouts_fail_then_allow_a_clean_restart(): void
    {
        [$client, $command] = $this->fixture();
        foreach (['exit' => 'ImportError', 'error' => 'model.py:42 in generate', 'mismatch' => 'tidak cocok', 'broken' => 'Syntax error', 'large' => 'melebihi batas', 'timeout' => 'batas waktu'] as $mode => $message) {
            try {
                $client->generate($command, ['TEST_TTS_MODE' => $mode], $this->request(), $mode === 'timeout' ? 0.15 : 3);
                $this->fail('Worker failure must not be reported as generated: '.$mode);
            } catch (Throwable $exception) {
                $this->assertStringContainsString($message, $exception->getMessage(), $mode);
            }
            $result = $client->generate($command, ['TEST_TTS_MODE' => 'normal'], $this->request(), 3);
            $this->assertSame(1, $result['request_number']);
        }
    }

    public function test_recycling_response_releases_process_before_the_next_request(): void
    {
        [$client, $command] = $this->fixture();
        $first = $client->generate($command, ['TEST_TTS_MODE' => 'recycle'], $this->request(), 3);
        $second = $client->generate($command, ['TEST_TTS_MODE' => 'recycle'], $this->request(), 3);

        $this->assertNotSame($first['pid'], $second['pid']);
        $this->assertSame(1, $second['request_number']);
    }

    private function request(): array
    {
        return ['text' => 'Narasi Indonesia.\nBaris kedua; $(bukan perintah)', 'speed' => 150, 'destination' => 'example.wav', 'reference' => null];
    }

    private function fixture(): array
    {
        $file = tempnam(sys_get_temp_dir(), 'tts-pipe-');
        $this->files[] = $file;
        file_put_contents($file, <<<'PHP'
<?php
$number = 0;
echo "library startup noise\n";
while (($line = fgets(STDIN)) !== false) {
    $request = json_decode($line, true);
    $mode = getenv('TEST_TTS_MODE');
    if ($mode === 'timeout') { usleep(900000); }
    if ($mode === 'exit') { fwrite(STDERR, 'ImportError: module missing'); exit(7); }
    if ($mode === 'large') { echo str_repeat('x', 70000); fflush(STDOUT); continue; }
    if ($mode === 'broken') { echo "ARTASWAHA_TTS {broken\n"; fflush(STDOUT); continue; }
    $result = ['id' => $mode === 'mismatch' ? 'other-task' : $request['id'],
        'status' => $mode === 'error' ? 'error' : 'generated', 'error' => 'referensi tidak valid', 'traceback' => 'model.py:42 in generate',
        'pid' => getmypid(), 'request_number' => ++$number, 'recycle' => $mode === 'recycle'];
    $message = 'ARTASWAHA_TTS '.json_encode($result)."\n";
    if ($mode === 'split') { echo substr($message, 0, 15); fflush(STDOUT); usleep(20000); $message = substr($message, 15); }
    echo $message;
    fflush(STDOUT);
}
PHP);
        $client = new ChatterboxProcess;
        $this->clients[] = $client;

        return [$client, [PHP_BINARY, $file]];
    }
}
