<?php

namespace App\Jobs;

use App\Models\ContentAudio;
use App\Services\ContentAudioService;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateContentAudio implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = ContentAudioService::GENERATION_TIMEOUT + 30;

    public int $uniqueFor = 86400;

    public int $maxExceptions = 2;

    public bool $failOnTimeout = true;

    public int $deadline;

    public function __construct(public int $audioId, public string $version)
    {
        $connection = config('queue.default');
        $this->onConnection($connection)->onQueue(config("queue.connections.{$connection}.queue", 'default'));
        $this->deadline = now()->addDay()->getTimestamp();
    }

    public function uniqueId(): string
    {
        return $this->audioId.':'.$this->version;
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->setTimestamp($this->deadline);
    }

    public function backoff(): int
    {
        return 60;
    }

    /** @return list<WithoutOverlapping> */
    public function middleware(): array
    {
        /** ponytail: One local inference at a time; separate resource pools after measuring contention. */
        return [(new WithoutOverlapping('learner-llm'))->shared()->releaseAfter(15)->expireAfter(1845)];
    }

    public function handle(ContentAudioService $service): void
    {
        $audio = ContentAudio::with(['lesson', 'question'])->find($this->audioId);
        if ($audio === null || $audio->version !== $this->version || ! in_array($audio->status, ['queued', 'processing'], true) || ! $service->isCurrent($audio)) {
            return;
        }
        if (! $service->isConfigured() || $audio->engine !== config('services.tts.engine', 'espeak')) {
            $audio->update(['status' => 'unavailable']);

            return;
        }
        $service->resetGenerationProgress($audio);
        $audio->update(['status' => 'processing', 'generation_started_at' => now(), 'generation_finished_at' => null]);
        $path = null;
        try {
            $path = $service->synthesize($audio);
            $current = $audio->fresh(['lesson', 'question']);
            if ($current !== null && $current->version === $this->version && $service->isCurrent($current)) {
                $saved = ContentAudio::whereKey($audio->id)->where('version', $this->version)->update(['path' => $path, 'status' => 'review', 'generation_finished_at' => now()]);
                if ($saved) {
                    return;
                }
            }
            Storage::disk('local')->delete($path);
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk('local')->delete($path);
            }
            ContentAudio::whereKey($audio->id)->where('version', $this->version)->update(['status' => 'queued', 'generation_finished_at' => now()]);
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        ContentAudio::whereKey($this->audioId)->where('version', $this->version)->whereNotIn('status', ['ready', 'review'])->update(['status' => 'failed', 'generation_finished_at' => now()]);
    }
}
