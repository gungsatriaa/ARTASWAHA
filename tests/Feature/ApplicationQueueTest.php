<?php

namespace Tests\Feature;

use App\Console\Commands\WorkApplicationQueue;
use App\Jobs\AnalyzeLearnerInterview;
use App\Jobs\GenerateAiReport;
use App\Jobs\GenerateContentAudio;
use App\Jobs\GenerateQuizDraft;
use App\Jobs\QueueLearnerAnalyses;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class ApplicationQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_command_delegates_to_the_shared_durable_connection_with_safe_worker_limits(): void
    {
        $command = Mockery::mock(WorkApplicationQueue::class)->makePartial();
        $command->shouldReceive('option')->with('once')->andReturn(true);
        $command->shouldReceive('option')->with('stop-when-empty')->andReturn(false);
        $command->shouldReceive('call')->once()->with('queue:work', [
            'connection' => 'learner-analysis', '--queue' => 'learner-analysis,default', '--sleep' => 2,
            '--tries' => 3, '--timeout' => 1830, '--max-time' => 3600, '--once' => true, '--stop-when-empty' => false,
        ])->andReturn(0);
        $this->assertSame(0, $command->handle());
        $this->assertGreaterThan(1830, config('queue.connections.learner-analysis.retry_after'));
    }

    public function test_all_application_jobs_use_the_same_backend_even_if_the_default_is_sync(): void
    {
        config(['queue.default' => 'sync']);
        $jobs = [new AnalyzeLearnerInterview(1, 'hash'), new GenerateAiReport(1), new GenerateContentAudio(1, 'version'), new GenerateQuizDraft(1), new QueueLearnerAnalyses(1)];
        foreach ($jobs as $job) {
            $this->assertSame('learner-analysis', $job->connection);
            $this->assertContains($job->queue, ['learner-analysis', 'default']);
            Queue::connection($job->connection)->push($job, '', $job->queue);
        }
        $this->assertSame(5, DB::table('jobs')->count());
        $this->assertSame(1, DB::table('jobs')->where('queue', 'default')->count());
    }

    public function test_queue_command_is_registered_and_can_exit_cleanly_when_empty(): void
    {
        $this->artisan('queue', ['--stop-when-empty' => true])->assertSuccessful();
    }
}
