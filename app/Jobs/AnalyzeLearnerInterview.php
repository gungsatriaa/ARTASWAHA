<?php

namespace App\Jobs;

use App\Models\LearnerProfile;
use App\Services\LearnerInterviewAnalyzer;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
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

    public function __construct(public int $profileId, public string $inputHash)
    {
        $this->onConnection('learner-analysis')->onQueue('learner-analysis');
        $this->deadline = now()->addDay()->getTimestamp();
    }

    public function uniqueId(): string
    {
        return $this->profileId.':'.$this->inputHash;
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
        $profile = LearnerProfile::find($this->profileId);
        if ($profile === null || $profile->analysis_hash !== $this->inputHash || $profile->analysis_status === 'completed'
            || ! $analyzer->isCurrentInput($profile)) {
            return;
        }
        if (! $analyzer->isConfigured()) {
            $profile->forceFill(['analysis_status' => 'unavailable'])->save();

            return;
        }
        $profile->forceFill(['analysis_status' => 'processing'])->save();
        $started = hrtime(true);
        try {
            $result = $analyzer->generate($profile->analysis_input);
            $currentProfile = $profile->fresh();
            if ($currentProfile !== null && $currentProfile->analysis_hash === $this->inputHash && $analyzer->isCurrentInput($currentProfile)) {
                LearnerProfile::query()->whereKey($this->profileId)->where('analysis_hash', $this->inputHash)
                    ->update(['analysis_status' => 'completed', 'analysis_result' => json_encode($result, JSON_THROW_ON_ERROR), 'analysis_completed_at' => now()]);
            }
            Log::info('Learner analysis processed.', ['profile_id' => $this->profileId, 'duration_ms' => (int) ((hrtime(true) - $started) / 1e6)]);
        } catch (UnexpectedValueException $exception) {
            $this->failed($exception);
        } catch (Throwable $exception) {
            LearnerProfile::query()->whereKey($this->profileId)->where('analysis_hash', $this->inputHash)
                ->update(['analysis_status' => 'queued']);
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        LearnerProfile::query()->whereKey($this->profileId)->where('analysis_hash', $this->inputHash)->where('analysis_status', '!=', 'completed')
            ->update(['analysis_status' => 'failed']);
        Log::warning('Learner analysis failed.', ['profile_id' => $this->profileId, 'exception_type' => $exception === null ? null : $exception::class]);
    }
}
