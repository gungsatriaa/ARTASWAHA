<?php

namespace Tests\Feature;

use App\Jobs\AnalyzeLearnerInterview;
use App\Jobs\QueueLearnerAnalyses;
use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\User;
use App\Services\LearnerInterviewAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class LearnerAnalysisMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.ai' => ['url' => 'http://localhost:20128/v1', 'model' => 'test-model', 'key' => 'secret-key']]);
        Http::preventStrayRequests();
    }

    public function test_first_result_is_factual_and_refresh_keeps_last_ai_result_even_when_provider_fails(): void
    {
        Queue::fake();
        $path = LearningPath::factory()->create(['is_published' => true]);
        $profile = $this->profile($path);
        $analyzer = app(LearnerInterviewAnalyzer::class);
        $analyzer->enqueue($profile);
        $initial = $analyzer->presentation($profile->fresh(), collect([$path]));
        $this->assertSame('initial', $initial['source']);
        $this->assertStringContainsString('0 hari aktif', $initial['summary']);
        $this->assertSame('queued', $initial['status']);
        $result = ['summary' => 'Hasil AI pertama.', 'recommendations' => [['learning_path_id' => $path->id, 'reason' => 'Materi pilihanmu.']]];
        Http::fake(['*' => Http::sequence()->push(['choices' => [['message' => ['content' => json_encode($result)]]]])->push(['error' => 'private provider response secret-key'], 401)]);
        $queued = $profile->fresh();
        (new AnalyzeLearnerInterview($profile->id, $queued->analysis_hash, $queued->analysis_requested_at->toDateTimeString()))->handle($analyzer);
        $updatedAt = $profile->fresh()->analysis_completed_at->toIso8601String();
        $this->travel(7)->hours();
        $analyzer->enqueue($profile->fresh());
        $queued = $profile->fresh();
        $presentation = $analyzer->presentation($queued, collect([$path]));
        $this->assertSame('queued', $presentation['status']);
        $this->assertSame('ai', $presentation['source']);
        $this->assertSame('Hasil AI pertama.', $presentation['summary']);
        $this->assertSame($updatedAt, $presentation['updated_at']);
        (new AnalyzeLearnerInterview($profile->id, $queued->analysis_hash, $queued->analysis_requested_at->toDateTimeString()))->handle($analyzer);
        $profile->refresh();
        $this->assertSame('failed', $profile->analysis_status);
        $this->assertSame('Hasil AI pertama.', $analyzer->presentation($profile, collect([$path]))['summary']);
        $event = collect($profile->analysis_events)->last();
        $this->assertSame(401, $event['http_status']);
        $this->assertStringNotContainsString('secret-key', json_encode($profile->analysis_events));
        $this->assertTrue(collect($profile->analysis_events)->contains(fn (array $event): bool => ($event['result']['summary'] ?? null) === 'Hasil AI pertama.'));
    }

    public function test_admin_bulk_test_is_background_only_and_logs_are_private_and_bounded(): void
    {
        Queue::fake();
        $path = LearningPath::factory()->create();
        $user = User::factory()->create();
        $old = $this->profile($path, $user);
        $latest = $this->profile($path, $user);
        $anonymous = $this->profile($path);
        $this->actingAs($user)->post(route('admin.ai.profiles.test-all'))->assertForbidden();
        Queue::assertNothingPushed();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->post(route('admin.ai.profiles.test-all'))->assertRedirect();
        Queue::assertPushed(QueueLearnerAnalyses::class, 1);
        Http::assertNothingSent();
        (new QueueLearnerAnalyses($anonymous->id))->handle(app(LearnerInterviewAnalyzer::class));
        $this->assertNull($old->fresh()->analysis_status);
        $this->assertSame('queued', $latest->fresh()->analysis_status);
        $this->assertSame('queued', $anonymous->fresh()->analysis_status);
        Queue::assertPushed(AnalyzeLearnerInterview::class, 2);
        (new QueueLearnerAnalyses($anonymous->id))->handle(app(LearnerInterviewAnalyzer::class));
        Queue::assertPushed(AnalyzeLearnerInterview::class, 2);
        for ($index = 0; $index < 25; $index++) {
            $anonymous->recordAnalysisEvent('failed', 'Kegagalan tercatat.');
        }
        $this->assertCount(20, $anonymous->fresh()->analysis_events);
        $this->get(route('admin.ai.profiles.show', $anonymous))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('record.events', 20));
        $this->actingAs($user)->get(route('admin.ai.profiles.show', $anonymous))->assertForbidden();
        $this->getJson(route('profile.analysis.show'))->assertOk()->assertJsonMissingPath('analysis.events');
        $this->assertArrayNotHasKey('analysis_events', $anonymous->fresh()->toArray());
    }

    public function test_bulk_job_continues_in_pages_and_forces_recent_success_without_replacing_active_work(): void
    {
        Queue::fake();
        $path = LearningPath::factory()->create();
        $analyzer = app(LearnerInterviewAnalyzer::class);
        $profile = $this->profile($path);
        $analyzer->enqueue($profile);
        $profile->refresh()->forceFill(['analysis_status' => 'completed', 'analysis_completed_at' => now()])->save();
        $this->travel(3)->minutes();
        for ($index = 0; $index < 100; $index++) {
            $this->profile($path);
        }
        $max = LearnerProfile::max('id');
        (new QueueLearnerAnalyses($max))->handle($analyzer);
        $this->assertSame('queued', $profile->fresh()->analysis_status);
        Queue::assertPushed(QueueLearnerAnalyses::class, fn ($job): bool => $job->afterId === $max - 1 && $job->throughId === $max);
    }

    public function test_scheduler_check_and_old_profiles_are_visible_and_unconfigured_bulk_is_rejected(): void
    {
        Queue::fake();
        $path = LearningPath::factory()->create();
        $profile = $this->profile($path);
        $profile->forceFill(['created_at' => now()->subYear()])->save();
        $this->artisan('learning:analyze')->assertSuccessful();
        $this->assertNotNull(Cache::get('learner-analysis-last-check'));
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('admin.ai.index', ['tab' => 'interviews']))
            ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->has('profiles.data', 1)->where('aiConfigured', true));
        config(['services.ai.model' => '']);
        $this->post(route('admin.ai.profiles.test-all'))->assertSessionHasErrors('analysis');
        Queue::assertNotPushed(QueueLearnerAnalyses::class);
    }

    public function test_permanent_provider_error_marks_the_real_queue_job_failed_instead_of_done(): void
    {
        Queue::fake();
        $profile = $this->profile(LearningPath::factory()->create(['is_published' => true]));
        $analyzer = app(LearnerInterviewAnalyzer::class);
        $analyzer->enqueue($profile);
        $profile->refresh();
        Http::fake(['*' => Http::response(['error' => 'private body'], 403)]);
        $job = (new AnalyzeLearnerInterview($profile->id, $profile->analysis_hash, $profile->analysis_requested_at->toDateTimeString()))->withFakeQueueInteractions();
        $job->handle($analyzer);
        $job->assertFailed();
    }

    private function profile(LearningPath $path, ?User $user = null): LearnerProfile
    {
        return LearnerProfile::create(['visitor_id' => (string) Str::uuid(), 'user_id' => $user?->id, 'learning_path_id' => $path->id,
            'learning_path_ids' => [$path->id], 'role' => 'murid', 'name' => 'Private name', 'mode' => $user === null ? 'anonymous' : 'account']);
    }
}
