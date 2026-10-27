<?php

namespace App\Jobs;

use App\Models\LearnerProfile;
use App\Services\LearnerInterviewAnalyzer;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class QueueLearnerAnalyses implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $uniqueFor = 3600;

    public function __construct(public int $throughId, public int $afterId = 0)
    {
        $this->onConnection('learner-analysis')->onQueue('learner-analysis');
    }

    public function uniqueId(): string
    {
        return $this->throughId.':'.$this->afterId;
    }

    public function handle(LearnerInterviewAnalyzer $analyzer): void
    {
        $profiles = LearnerProfile::forAnalysis()->where('id', '>', $this->afterId)
            ->where('id', '<=', $this->throughId)->orderBy('id')->limit(100)->get();
        foreach ($profiles as $profile) {
            $analyzer->enqueue($profile, force: true);
        }
        Log::info('Learner analysis bulk page queued.', ['profiles_checked' => $profiles->count(), 'after_id' => $this->afterId, 'through_id' => $this->throughId]);
        if ($profiles->count() === 100 && $profiles->last()->id < $this->throughId) {
            self::dispatch($this->throughId, $profiles->last()->id);
        }
    }
}
