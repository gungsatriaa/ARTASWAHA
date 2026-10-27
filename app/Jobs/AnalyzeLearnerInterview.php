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
use Illuminate\Support\Facades\Cache;
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
        $connection = config('queue.default');
        $this->onConnection($connection)->onQueue(config("queue.connections.{$connection}.queue", 'default'));
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
        Cache::forever('learner-analysis-last-worker', now()->toIso8601String());
        $profile = $this->profileQuery()->first();
        if ($profile === null || $profile->analysis_status === 'completed') {
            Log::info('Learner analysis skipped.', ['profile_id' => $this->profileId, 'reason' => $profile === null ? 'request_replaced_or_profile_deleted' : 'already_completed']);

            return;
        }
        if (! $analyzer->isCurrentInput($profile)) {
            $this->profileQuery()->update(['analysis_status' => 'outdated']);
            $profile->recordAnalysisEvent('outdated', 'Jawaban interview atau konfigurasi berubah. Permintaan lama dilewati.');

            return;
        }
        if (! $analyzer->isConfigured()) {
            Log::warning('Learner analysis worker has no AI configuration.', ['profile_id' => $this->profileId]);
            $this->profileQuery()->update(['analysis_status' => 'unavailable']);
            $profile->recordAnalysisEvent('unavailable', 'Worker belum membaca AI_API_URL atau AI_MODEL. Perbarui cache konfigurasi dan restart worker.');

            return;
        }
        if ($this->profileQuery()->update(['analysis_status' => 'processing']) === 0) {
            return;
        }
        Log::info('Learner analysis started.', ['profile_id' => $this->profileId, 'connection' => $this->connection, 'queue' => $this->queue]);
        $profile->recordAnalysisEvent('processing', 'Worker mulai meminta analisis ke penyedia AI.');
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
                    $profile->recordAnalysisEvent('completed', 'Hasil AI berhasil divalidasi dan disimpan.', ['duration_ms' => (int) ((hrtime(true) - $started) / 1e6), 'result' => $result]);
                    Log::info('Learner analysis processed.', ['profile_id' => $this->profileId, 'duration_ms' => (int) ((hrtime(true) - $started) / 1e6)]);
                }
            }
        } catch (UnexpectedValueException $exception) {
            if ($this->job !== null) {
                $this->fail($exception);
            } else {
                $this->failed($exception);
            }
        } catch (Throwable $exception) {
            Log::warning('Learner analysis attempt will be retried.', ['profile_id' => $this->profileId, 'exception_type' => $exception::class, 'duration_ms' => (int) ((hrtime(true) - $started) / 1e6)]);
            $changed = $this->profileQuery()->where('analysis_status', '!=', 'completed')
                ->update(['analysis_status' => 'queued']);
            if ($changed > 0) {
                $profile->recordAnalysisEvent('queued', 'Permintaan AI gagal sementara; worker akan mencoba lagi.', ['exception_type' => $exception::class, 'http_status' => $exception->getCode() >= 400 && $exception->getCode() <= 599 ? $exception->getCode() : null]);
            }
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $profile = $this->profileQuery()->first();
        $changed = $this->profileQuery()->where('analysis_status', '!=', 'completed')->update(['analysis_status' => 'failed']);
        if ($changed > 0 && $profile !== null) {
            $message = match ($exception?->getMessage()) {
                'No published learning material is available.' => 'Tidak ada materi pilihan yang sudah diterbitkan.',
                'Learner analysis provider rejected the request.' => 'Penyedia AI menolak permintaan. Periksa HTTP status, model, dan autentikasi.',
                'AI provider returned an invalid result.' => 'Respons AI bukan JSON yang valid atau melebihi batas ukuran.',
                'Learner analysis provider returned an invalid result.' => 'JSON AI tidak sesuai skema ringkasan/rekomendasi atau ID materi tidak diizinkan.',
                'Learner analysis is unavailable for this input.', 'AI service is not configured.' => 'Konfigurasi AI atau data interview belum valid.',
                default => $exception instanceof UnexpectedValueException ? 'Respons AI tidak sesuai format yang diwajibkan.' : 'Analisis gagal setelah percobaan worker atau melewati batas waktu.',
            };
            $profile->recordAnalysisEvent('failed', $message, ['exception_type' => $exception === null ? null : $exception::class, 'http_status' => $exception?->getCode() >= 400 && $exception?->getCode() <= 599 ? $exception->getCode() : null]);
        }
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
