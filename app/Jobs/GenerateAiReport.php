<?php

namespace App\Jobs;

use App\Models\AiReport;
use App\Services\AdminAiService;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;
use UnexpectedValueException;

class GenerateAiReport implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $maxExceptions = 2;

    public int $uniqueFor = 86400;

    public bool $failOnTimeout = true;

    public int $deadline;

    public function __construct(public int $reportId)
    {
        $connection = config('queue.default');
        $this->onConnection($connection)->onQueue(config("queue.connections.{$connection}.queue", 'default'));
        $this->deadline = now()->addDay()->getTimestamp();
    }

    public function uniqueId(): string
    {
        return (string) $this->reportId;
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->setTimestamp($this->deadline);
    }

    public function backoff(): int
    {
        return 60;
    }

    /** @return list<WithoutOverlapping|RateLimited> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('learner-llm'))->shared()->releaseAfter(15)->expireAfter(135), new RateLimited('learner-analysis')];
    }

    public function handle(AdminAiService $service): void
    {
        $report = AiReport::find($this->reportId);
        if ($report === null || $report->status === 'completed') {
            return;
        }
        $report->update(['status' => 'processing']);
        try {
            $report->update(['result' => $service->interpret($report->snapshot), 'status' => 'completed', 'completed_at' => now()]);
        } catch (UnexpectedValueException $exception) {
            $this->failed($exception);
        } catch (Throwable $exception) {
            $report->update(['status' => 'queued']);
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        AiReport::whereKey($this->reportId)->where('status', '!=', 'completed')->update(['status' => 'failed']);
    }
}
