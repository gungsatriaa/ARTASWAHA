<?php

namespace Tests\Feature;

use App\Jobs\AnalyzeLearnerInterview;
use App\Jobs\GenerateAiReport;
use App\Jobs\GenerateContentAudio;
use App\Jobs\GenerateQuizDraft;
use App\Jobs\QueueLearnerAnalyses;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ApplicationQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_async_backends_reserve_jobs_longer_than_every_job_timeout(): void
    {
        foreach (['redis', 'database'] as $connection) {
            $this->assertTrue(config("queue.connections.{$connection}.after_commit"));
            foreach ($this->jobs() as $job) {
                $this->assertGreaterThan($job->timeout, config("queue.connections.{$connection}.retry_after"));
            }
        }
        $this->assertSame(1830, (new GenerateContentAudio(1, 'version'))->timeout);
    }

    public function test_all_jobs_follow_the_configured_connection_and_its_queue(): void
    {
        foreach (['redis', 'database', 'sync'] as $connection) {
            config(['queue.default' => $connection, "queue.connections.{$connection}.queue" => 'application']);
            foreach ($this->jobs() as $job) {
                $this->assertSame($connection, $job->connection);
                $this->assertSame('application', $job->queue);
            }
        }
    }

    public function test_builtin_worker_without_connection_reads_the_configured_database_queue(): void
    {
        config(['queue.default' => 'database']);
        foreach ($this->jobs() as $job) {
            Queue::connection($job->connection)->push($job, '', $job->queue);
        }
        $this->assertSame(5, DB::table('jobs')->where('queue', 'default')->count());
        DB::table('jobs')->delete();
        Queue::push(new AnalyzeLearnerInterview(99999, 'missing'));
        $this->artisan('queue:work', ['--sleep' => 3, '--tries' => 3, '--timeout' => 240, '--max-time' => 3600, '--once' => true])->assertSuccessful();
        $this->assertDatabaseCount('jobs', 0);
    }

    /** @return list<AnalyzeLearnerInterview|GenerateAiReport|GenerateContentAudio|GenerateQuizDraft|QueueLearnerAnalyses> */
    private function jobs(): array
    {
        return [new AnalyzeLearnerInterview(1, 'hash'), new GenerateAiReport(1), new GenerateContentAudio(1, 'version'), new GenerateQuizDraft(1), new QueueLearnerAnalyses(1)];
    }
}
