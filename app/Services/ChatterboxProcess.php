<?php

namespace App\Services;

use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Throwable;

class ChatterboxProcess
{
    private ?Process $process = null;

    private ?InputStream $input = null;

    private string $signature = '';

    /**
     * @param  list<string>  $command
     * @param  array<string, string>  $environment
     * @param  array{text: string, speed: int, destination: string, reference: ?string}  $request
     * @return array<string, mixed>
     */
    public function generate(array $command, array $environment, array $request, float $timeout = 240): array
    {
        $signature = hash('sha256', json_encode([$command, $environment], JSON_THROW_ON_ERROR));
        try {
            if ($this->process !== null && ($signature !== $this->signature || ! $this->process->isRunning())) {
                $this->stop();
            }
            if ($this->process === null) {
                $this->input = new InputStream;
                $this->process = new Process($command, null, $environment, $this->input, null);
                $this->process->start();
                $this->signature = $signature;
            }
            $id = (string) Str::uuid();
            $this->input->write(json_encode([...$request, 'id' => $id], JSON_THROW_ON_ERROR)."\n");
            $deadline = hrtime(true) + (int) ($timeout * 1_000_000_000);
            $output = '';
            $errors = '';
            while (hrtime(true) < $deadline) {
                $running = $this->process->isRunning();
                $output .= $this->process->getIncrementalOutput();
                $errors = substr($errors.$this->process->getIncrementalErrorOutput(), -6000);
                $this->process->clearOutput()->clearErrorOutput();
                while (($newline = strpos($output, "\n")) !== false) {
                    $line = substr($output, 0, $newline);
                    $output = substr($output, $newline + 1);
                    if (! str_starts_with($line, 'ARTASWAHA_TTS ')) {
                        continue;
                    }
                    $result = json_decode(substr($line, strlen('ARTASWAHA_TTS ')), true, flags: JSON_THROW_ON_ERROR);
                    if (! is_array($result) || ($result['id'] ?? null) !== $id) {
                        throw new RuntimeException('Respons worker TTS tidak cocok dengan tugas aktif.');
                    }
                    if (($result['status'] ?? null) !== 'generated') {
                        $details = (string) ($result['error'] ?? 'Respons tidak valid.');
                        if (is_string($result['traceback'] ?? null)) {
                            $details .= "\n".$result['traceback'];
                        }
                        throw new RuntimeException('Worker TTS gagal: '.Str::limit($details, 6000));
                    }
                    if ($result['recycle'] ?? false) {
                        $this->stop();
                    }

                    return $result;
                }
                if (strlen($output) > 65536) {
                    throw new RuntimeException('Respons worker TTS melebihi batas.');
                }
                if (! $running) {
                    throw new RuntimeException('Worker TTS berhenti (exit '.$this->process->getExitCode().'): '.$errors);
                }
                usleep(50000);
            }
            throw new RuntimeException('Worker TTS melewati batas waktu '.$timeout.' detik. '.$errors);
        } catch (Throwable $exception) {
            $this->stop();
            throw $exception;
        }
    }

    public function stop(): void
    {
        $this->input?->close();
        $this->process?->stop(1);
        $this->input = null;
        $this->process = null;
    }

    public function __destruct()
    {
        $this->stop();
    }
}
