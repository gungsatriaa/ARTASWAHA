<?php

namespace Tests\Feature;

use App\Jobs\AnalyzeLearnerInterview;
use App\Jobs\QueueLearnerAnalyses;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class LearnerAnalysisQueueEventsTest extends TestCase
{
    public function test_analysis_is_logged_before_middleware_and_when_released_without_exposing_payload(): void
    {
        Log::spy();
        $job = Mockery::mock(Job::class);
        $job->shouldReceive('payload')->andReturn([]);
        $job->shouldReceive('resolveName')->andReturn(AnalyzeLearnerInterview::class);
        $job->shouldReceive('getJobId')->andReturn('job-123');
        $job->shouldReceive('getQueue')->andReturn('default');
        $job->shouldReceive('attempts')->andReturn(2);
        $job->shouldReceive('isReleased')->andReturn(true);
        $context = ['job_id' => 'job-123', 'connection' => 'redis', 'queue' => 'default', 'attempt' => 2];

        Event::dispatch(new JobProcessing('redis', $job));
        Event::dispatch(new JobProcessed('redis', $job));

        Log::shouldHaveReceived('info')->with('Learner analysis job received by worker.', $context)->once();
        Log::shouldHaveReceived('warning')->with('Learner analysis job released back to queue.', $context)->once();
    }

    public function test_finished_analysis_and_unrelated_jobs_do_not_log_a_release_warning(): void
    {
        Log::spy();
        $job = Mockery::mock(Job::class);
        $job->shouldReceive('payload')->andReturn([]);
        $job->shouldReceive('resolveName')->andReturn(AnalyzeLearnerInterview::class);
        $job->shouldReceive('isReleased')->andReturn(false);
        Event::dispatch(new JobProcessed('database', $job));

        $bulkJob = Mockery::mock(Job::class);
        $bulkJob->shouldReceive('payload')->andReturn([]);
        $bulkJob->shouldReceive('resolveName')->andReturn(QueueLearnerAnalyses::class);
        Event::dispatch(new JobProcessing('redis', $bulkJob));
        Event::dispatch(new JobProcessed('redis', $bulkJob));

        Log::shouldNotHaveReceived('info');
        Log::shouldNotHaveReceived('warning');
    }
}
