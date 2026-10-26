<?php

namespace App\Jobs;

use App\Models\LearnerProfile;
use App\Services\LearnerInterviewAnalyzer;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;
use UnexpectedValueException;

class AnalyzeLearnerInterview implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $maxExceptions = 2;

    public int $uniqueFor = 86400;

    public bool $failOnTimeout = true;

    public int $deadline;

    public ?string $requestedAt = null;

    public function __construct(public int $profileId, public string $inputHash, ?string $requestedAt = null)
    {
        $this->requestedAt = $requestedAt;
        $this->onConnection('learner-analysis')->onQueue('learner-analysis');
        $this->deadline = now()->addDay()->getTimestamp();
    }

    public function uniqueId(): string
    {
        return $this->profileId.':'.$this->inputHash.($this->requestedAt === null ? '' : ':'.hash('sha256', $this->requestedAt));
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->setTimestamp($this->deadline);
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 180];
    }

    /** @return list<WithoutOverlapping|RateLimited> */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('learner-llm'))->shared()->releaseAfter(15)->expireAfter(135),
            new RateLimited('learner-analysis'),
        ];
    }

    public function handle(LearnerInterviewAnalyzer $analyzer): void
    {
        $profile = $this->profileQuery()->first();
        if ($profile === null || $profile->analysis_status === 'completed') {
            return;
        }
        if (! $analyzer->isCurrentInput($profile)) {
            $this->profileQuery()->update(['analysis_status' => 'outdated']);

            return;
        }
        if (! $analyzer->isConfigured()) {
            $this->profileQuery()->update(['analysis_status' => 'unavailable']);

            return;
        }
        if ($this->profileQuery()->update(['analysis_status' => 'processing']) === 0) {
            return;
        }
        $started = hrtime(true);
        try {
            $result = $analyzer->generate($profile->analysis_input);
            $currentProfile = $this->profileQuery()->first();
            if ($currentProfile !== null && ! $analyzer->isCurrentInput($currentProfile)) {
                $this->profileQuery()->update(['analysis_status' => 'outdated']);
            } elseif ($currentProfile !== null) {
                $saved = $this->profileQuery()
                    ->update(['analysis_status' => 'completed', 'analysis_result' => json_encode($result, JSON_THROW_ON_ERROR), 'analysis_completed_at' => now()]);
                if ($saved > 0) {
                    Log::info('Learner analysis processed.', ['profile_id' => $this->profileId, 'duration_ms' => (int) ((hrtime(true) - $started) / 1e6)]);
                }
            }
        } catch (UnexpectedValueException $exception) {
            $this->failed($exception);
        } catch (Throwable $exception) {
            $this->profileQuery()->where('analysis_status', '!=', 'completed')
                ->update(['analysis_status' => 'queued']);
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->profileQuery()->where('analysis_status', '!=', 'completed')
            ->update(['analysis_status' => 'failed']);
        Log::warning('Learner analysis failed.', ['profile_id' => $this->profileId, 'exception_type' => $exception === null ? null : $exception::class]);
    }

    /**
     * Keep jobs queued before requestedAt was introduced from changing a newer attempt.
     *
     * @return Builder<LearnerProfile>
     */
    private function profileQuery(): Builder
    {
        return LearnerProfile::query()->whereKey($this->profileId)->where('analysis_hash', $this->inputHash)
            ->when($this->requestedAt !== null,
                fn (Builder $query): Builder => $query->where('analysis_requested_at', $this->requestedAt),
                fn (Builder $query): Builder => $query->where('analysis_requested_at', '<=', now()->setTimestamp($this->deadline)->subDay()->toDateTimeString()));
    }
}
