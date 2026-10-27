<?php

namespace Tests\Feature;

use App\Jobs\AnalyzeLearnerInterview;
use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\RoomAnswer;
use App\Models\RoomParticipant;
use App\Models\User;
use App\Services\LearnerInterviewAnalyzer;
use App\Services\LearningProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class LearnerAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.ai' => [
            'url' => 'http://localhost:20128/v1', 'model' => 'local-test', 'key' => '',
        ]]);
        Http::preventStrayRequests();
        $this->withCredentials();
    }

    public function test_finishing_interview_queues_once_without_contacting_llm(): void
    {
        Queue::fake([AnalyzeLearnerInterview::class]);
        $path = LearningPath::factory()->create(['is_published' => true]);
        $this->post(route('learning.interview.store'), ['step' => 1, 'mode' => 'anonymous']);
        $this->post(route('learning.interview.store'), ['step' => 2, 'learning_path_ids' => [$path->id]]);
        $this->post(route('learning.interview.store'), ['step' => 3, 'role' => 'murid']);

        $this->post(route('learning.interview.store'), ['step' => 4, 'name' => 'Private name'])
            ->assertRedirect(route('learning.paths.show', $path->slug));

        $profile = LearnerProfile::firstOrFail();
        $this->assertSame('murid', $profile->analysis_input['role']);
        $this->assertSame([$path->id], $profile->analysis_input['learning_path_ids']);
        $this->assertSame(0, $profile->analysis_input['activity']['active_days_7']);
        $this->assertSame('queued', $profile->analysis_status);
        $this->withCookie('artaswaha_device', $profile->visitor_id)->post(route('profile.analysis.store'))->assertRedirect();
        Queue::assertPushed(AnalyzeLearnerInterview::class, 1);
        Http::assertNothingSent();
    }

    public function test_unconfigured_shared_ai_keeps_learning_available_without_queuing(): void
    {
        Queue::fake([AnalyzeLearnerInterview::class]);
        config(['services.ai.model' => '']);
        $path = LearningPath::factory()->create(['is_published' => true]);
        $profile = $this->profile($path);

        app(LearnerInterviewAnalyzer::class)->enqueue($profile);

        $this->assertSame('unavailable', $profile->fresh()->analysis_status);
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('profile.show'))
            ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
        Queue::assertNotPushed(AnalyzeLearnerInterview::class);
        Http::assertNothingSent();
    }

    public function test_opening_profile_and_home_automatically_queues_analysis_once_without_a_button(): void
    {
        Queue::fake([AnalyzeLearnerInterview::class]);
        $path = LearningPath::factory()->create(['is_published' => true]);
        $profile = $this->profile($path);

        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('profile.show'))
            ->assertOk()
            ->assertInertiaNotContains('Coba analisis')
            ->assertInertiaNotContains('Periksa lagi');
        $this->get(route('home'))->assertOk();
        $this->get(route('profile.show'))->assertOk();

        $this->assertSame('queued', $profile->fresh()->analysis_status);
        Queue::assertPushed(AnalyzeLearnerInterview::class, 1);
        Http::assertNothingSent();
    }

    public function test_shared_ai_result_is_saved_and_shown_with_valid_graph_in_all_user_outputs(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true, 'title' => 'Aksara pilihan']);
        $profile = $this->profile($path);
        $analyzer = app(LearnerInterviewAnalyzer::class);
        $this->prepare($profile, $analyzer);
        Http::fake(['localhost:20128/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => json_encode($this->analysisResult($path))]]]])]);

        (new AnalyzeLearnerInterview($profile->id, $profile->analysis_hash))->handle($analyzer);

        $this->assertSame('completed', $profile->fresh()->analysis_status);
        $this->assertNotNull($profile->fresh()->analysis_completed_at);
        Http::assertSent(function (ClientRequest $request): bool {
            $input = json_decode($request['messages'][1]['content'], true);

            return $request->url() === 'http://localhost:20128/v1/chat/completions'
                && $request['model'] === 'local-test' && $request['stream'] === false
                && $request['response_format']['type'] === 'json_object'
                && $request['max_tokens'] === 1000
                && str_contains($request['messages'][2]['content'], 'learning_path_id')
                && ! str_contains(json_encode($input), 'Private name')
                && str_contains($request['messages'][0]['content'], 'Jangan menebak');
        });
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('profile.show'))
            ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc())->assertInertiaContains('Mulai dari materi pilihanmu.');
        $response = $this->getJson(route('profile.analysis.show'))->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('analysis.graph.nodes.1.id', 'path-'.$path->id)
            ->assertJsonPath('analysis.graph.edges.0', ['source' => 'role', 'target' => 'path-'.$path->id]);
        $this->assertCount(3, $response->json('analysis.graph.nodes'));
        $this->assertCount(2, $response->json('analysis.graph.edges'));
        $homepage = $this->get(route('home'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
        $homepage->assertInertiaValue('analysis.status', 'completed');
        $this->get(route('learning.paths.show', $path->slug))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
        $analyzer->enqueue($profile->fresh());
        (new AnalyzeLearnerInterview($profile->id, $profile->analysis_hash))->handle($analyzer);
        Http::assertSentCount(1);
    }

    public function test_openai_compatible_local_server_can_be_configured(): void
    {
        config(['services.ai.url' => 'http://localhost:8000/v1', 'services.ai.key' => 'test-key']);
        $path = LearningPath::factory()->create(['is_published' => true]);
        Http::fake(['localhost:8000/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => json_encode($this->analysisResult($path))]]]])]);

        $result = app(LearnerInterviewAnalyzer::class)->generate(['role' => 'guru', 'learning_path_ids' => [$path->id]]);

        $this->assertSame($path->id, $result['recommendations'][0]['learning_path_id']);
        Http::assertSent(fn (ClientRequest $request): bool => $request->hasHeader('Authorization', 'Bearer test-key') && $request['max_tokens'] === 1000);
    }

    #[DataProvider('invalidContents')]
    public function test_rejects_invalid_or_unsupported_ai_results(string $content): void
    {
        LearningPath::factory()->create(['id' => 101, 'is_published' => true]);
        Http::fake(['localhost:20128/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => $content]]]])]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invalid result');
        app(LearnerInterviewAnalyzer::class)->generate(['role' => 'murid', 'learning_path_ids' => [101]]);
    }

    /** @return array<string, array{string}> */
    public static function invalidContents(): array
    {
        return [
            'malformed json' => ['not JSON'],
            'unknown material' => ['{"summary":"Pilihanmu","recommendations":[{"learning_path_id":999,"reason":"Alasan"}]}'],
            'duplicate material' => ['{"summary":"Pilihanmu","recommendations":[{"learning_path_id":101,"reason":"A"},{"learning_path_id":101,"reason":"B"}]}'],
            'empty recommendations' => ['{"summary":"Pilihanmu","recommendations":[]}'],
            'extra property' => ['{"summary":"Pilihanmu","ability":"mahir","recommendations":[{"learning_path_id":101,"reason":"A"}]}'],
            'blank reason' => ['{"summary":"Pilihanmu","recommendations":[{"learning_path_id":101,"reason":" "}]}'],
            'overlong summary' => [json_encode(['summary' => str_repeat('x', 601), 'recommendations' => [['learning_path_id' => 101, 'reason' => 'A']]])],
        ];
    }

    public function test_account_cannot_read_or_retry_another_profiles_analysis_using_cookie_or_payload(): void
    {
        Queue::fake([AnalyzeLearnerInterview::class]);
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $path = LearningPath::factory()->create(['is_published' => true]);
        $profile = $this->profile($path, $owner);

        $this->actingAs($other)->withCookie('artaswaha_device', $profile->visitor_id)
            ->getJson(route('profile.analysis.show', ['profile_id' => $profile->id]))->assertNotFound();
        $this->post(route('profile.analysis.store'), ['profile_id' => $profile->id])->assertNotFound();
        Queue::assertNotPushed(AnalyzeLearnerInterview::class);
    }

    public function test_unknown_device_cannot_read_analysis_and_an_account_is_visible_on_another_device(): void
    {
        $user = User::factory()->create();
        $path = LearningPath::factory()->create(['is_published' => true]);
        $profile = $this->profile($path, $user);
        $this->complete($profile, $path);

        $this->getJson(route('profile.analysis.show'))->assertNotFound();
        $this->actingAs($user)->get(route('profile.show'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
        $this->getJson(route('profile.analysis.show'))->assertOk()->assertJsonPath('analysis.summary', 'Mulai dari materi pilihanmu.');
        Http::assertNothingSent();
    }

    public function test_linking_anonymous_profile_preserves_saved_analysis(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $profile = $this->profile($path);
        $this->complete($profile, $path);
        $user = User::factory()->create();
        $request = Request::create('/', cookies: ['artaswaha_device' => $profile->visitor_id]);

        app(LearningProgressService::class)->claimDeviceProgress($request, $user);

        $this->assertSame($user->id, $profile->fresh()->user_id);
        $this->assertSame('completed', $profile->fresh()->analysis_status);
        $this->actingAs($user)->get(route('profile.show'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
        Http::assertNothingSent();
    }

    public function test_archived_material_is_removed_from_graph_and_links(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $profile = $this->profile($path);
        $this->complete($profile, $path);
        $path->update(['is_published' => false]);

        $this->withCookie('artaswaha_device', $profile->visitor_id)->getJson(route('profile.analysis.show'))
            ->assertOk()->assertJsonPath('analysis.recommendations', [])->assertJsonPath('analysis.graph.edges', [])->assertJsonPath('analysis.summary', null);
    }

    public function test_ai_text_is_escaped_in_profile_and_polled_html(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true, 'title' => '<script>material()</script>']);
        $profile = $this->profile($path);
        $this->complete($profile, $path, '<script>summary()</script>');
        $result = $profile->analysis_result;
        $result['recommendations'][0]['reason'] = '<img src=x onerror=alert(1)>';
        $profile->forceFill(['analysis_result' => $result])->save();

        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('profile.show'))->assertOk()
            ->assertInertiaContains('<script>summary()')->assertDontSee('<script>summary()', false)
            ->assertInertiaContains('<img')->assertDontSee('<img src=x onerror=', false);
        $response = $this->getJson(route('profile.analysis.show'))->assertOk();
        $response->assertJsonPath('analysis.summary', '<script>summary()</script>')->assertJsonMissingPath('html');
        $this->assertIsArray($response->json('analysis.recommendations'));
    }

    public function test_old_job_does_not_overwrite_new_answers(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $profile = $this->profile($path);
        $analyzer = app(LearnerInterviewAnalyzer::class);
        $this->prepare($profile, $analyzer);
        $job = new AnalyzeLearnerInterview($profile->id, $profile->analysis_hash);
        Http::fake(function () use ($profile, $path) {
            $profile->update(['role' => 'guru']);

            return Http::response(['choices' => [['message' => ['content' => json_encode($this->analysisResult($path))]]]]);
        });

        $job->handle($analyzer);

        $this->assertNull($profile->fresh()->analysis_result);
        $this->assertSame('outdated', $profile->fresh()->analysis_status);
    }

    public function test_stale_job_skips_llm_when_input_has_already_changed(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $profile = $this->profile($path);
        $analyzer = app(LearnerInterviewAnalyzer::class);
        $this->prepare($profile, $analyzer);
        $job = new AnalyzeLearnerInterview($profile->id, $profile->analysis_hash);
        $profile->update(['role' => 'guru']);

        $job->handle($analyzer);

        Http::assertNothingSent();
    }

    public function test_failed_provider_preserves_answers_and_terminal_failure_has_fallback(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $profile = $this->profile($path);
        $analyzer = app(LearnerInterviewAnalyzer::class);
        $this->prepare($profile, $analyzer);
        Http::fake(['localhost:20128/v1/chat/completions' => Http::response([], 503)]);
        $job = new AnalyzeLearnerInterview($profile->id, $profile->analysis_hash);
        try {
            $job->handle($analyzer);
            $this->fail('Provider failure must not produce a completed analysis.');
        } catch (RuntimeException $exception) {
            $job->failed($exception);
        }

        $this->assertSame('failed', $profile->fresh()->analysis_status);
        $this->assertSame([$path->id], $profile->fresh()->learning_path_ids);
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('profile.show'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
    }

    public function test_pending_status_has_no_rendered_html_and_retry_is_rate_limited(): void
    {
        Queue::fake([AnalyzeLearnerInterview::class]);
        $path = LearningPath::factory()->create(['is_published' => true]);
        $profile = $this->profile($path);
        $this->prepare($profile, app(LearnerInterviewAnalyzer::class));

        $this->withCookie('artaswaha_device', $profile->visitor_id)->getJson(route('profile.analysis.show'))
            ->assertOk()->assertJsonPath('analysis.status', 'queued')->assertJsonPath('html', null);
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post(route('profile.analysis.store'))->assertRedirect();
        }
        $this->post(route('profile.analysis.store'))->assertTooManyRequests();
        Queue::assertNotPushed(AnalyzeLearnerInterview::class);
    }

    public function test_global_llm_lock_defers_a_second_user_without_inference(): void
    {
        $lock = Cache::lock('laravel-queue-overlap:learner-llm', 135);
        $this->assertTrue($lock->get());
        $job = \Mockery::mock(AnalyzeLearnerInterview::class.'[release]', [1, 'test']);
        $job->shouldReceive('release')->once()->with(15);

        $job->middleware()[0]->handle($job, function (): void {
            $this->fail('Only one inference may run at a time.');
        });

        $lock->release();
        Http::assertNothingSent();
    }

    public function test_configured_database_queue_is_durable_and_deduplicated(): void
    {
        config(['queue.default' => 'database']);
        $path = LearningPath::factory()->create(['is_published' => true]);
        $profile = $this->profile($path);
        $analyzer = app(LearnerInterviewAnalyzer::class);

        $analyzer->enqueue($profile);
        $analyzer->enqueue($profile->fresh());

        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseHas('jobs', ['queue' => 'default']);
        $this->assertSame('queued', $profile->fresh()->analysis_status);
        Http::assertNothingSent();

        Http::fake(['localhost:20128/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => json_encode($this->analysisResult($path))]]]])]);
        $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--sleep' => 0])
            ->assertExitCode(0);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame('completed', $profile->fresh()->analysis_status);
        Http::assertSentCount(1);
    }

    public function test_failed_analysis_can_retry_after_two_minutes_instead_of_six_hours(): void
    {
        Queue::fake([AnalyzeLearnerInterview::class]);
        $this->travelTo(now()->setTime(12, 0));
        $profile = $this->profile(LearningPath::factory()->create(['is_published' => true]));
        $analyzer = app(LearnerInterviewAnalyzer::class);
        $this->prepare($profile, $analyzer);
        $profile->forceFill(['analysis_status' => 'failed'])->save();
        $analyzer->enqueue($profile);
        Queue::assertNotPushed(AnalyzeLearnerInterview::class);

        $this->travel(3)->minutes();
        $analyzer->enqueue($profile->fresh());
        $this->assertSame('queued', $profile->fresh()->analysis_status);
        Queue::assertPushed(AnalyzeLearnerInterview::class, 1);
    }

    public function test_stalled_queue_can_recover_without_waiting_for_the_old_unique_lock(): void
    {
        $this->travelTo(now()->setTime(12, 0));
        $path = LearningPath::factory()->create(['is_published' => true]);
        $profile = $this->profile($path);
        $analyzer = app(LearnerInterviewAnalyzer::class);
        $analyzer->enqueue($profile);
        $oldJob = new AnalyzeLearnerInterview($profile->id, $profile->fresh()->analysis_hash, $profile->fresh()->analysis_requested_at->toDateTimeString());
        $legacyJob = new AnalyzeLearnerInterview($profile->id, $profile->fresh()->analysis_hash);
        $this->assertDatabaseCount('jobs', 1);
        $this->travel(31)->minutes();
        $analyzer->enqueue($profile->fresh());
        $this->assertDatabaseCount('jobs', 2);

        Http::fake(['localhost:20128/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => json_encode($this->analysisResult($path))]]]])]);
        $oldJob->handle($analyzer);
        $oldJob->failed(new RuntimeException('Old attempt failed'));
        $legacyJob->handle($analyzer);
        $legacyJob->failed(new RuntimeException('Legacy attempt failed'));
        Http::assertNothingSent();
        $this->assertSame('queued', $profile->fresh()->analysis_status);
        $this->artisan('queue:work', ['connection' => 'database', '--stop-when-empty' => true, '--sleep' => 0])
            ->assertExitCode(0);
        $this->assertSame('completed', $profile->fresh()->analysis_status);
        Http::assertSentCount(1);
    }

    public function test_processing_status_has_a_skeleton_and_stale_worker_config_stops_waiting(): void
    {
        $profile = $this->profile(LearningPath::factory()->create(['is_published' => true]));
        $analyzer = app(LearnerInterviewAnalyzer::class);
        $this->prepare($profile, $analyzer);
        $profile->forceFill(['analysis_status' => 'processing'])->save();
        $this->withCookie('artaswaha_device', $profile->visitor_id)->getJson(route('profile.analysis.show'))
            ->assertOk()->assertJsonPath('analysis.status', 'processing')->assertJsonPath('html', null);
        $this->get(route('profile.show'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());

        config(['services.ai.model' => 'changed-model']);
        (new AnalyzeLearnerInterview($profile->id, $profile->analysis_hash))->handle($analyzer);
        $this->getJson(route('profile.analysis.show'))->assertOk()->assertJsonPath('analysis.status', 'outdated');
        $this->assertSame('outdated', $profile->fresh()->analysis_status);
        Http::assertNothingSent();
    }

    public function test_pending_analysis_is_not_duplicated_when_a_six_hour_period_changes(): void
    {
        Queue::fake([AnalyzeLearnerInterview::class]);
        $this->travelTo(now()->setTime(11, 59));
        $profile = $this->profile(LearningPath::factory()->create(['is_published' => true]));
        $analyzer = app(LearnerInterviewAnalyzer::class);
        $this->prepare($profile, $analyzer);
        $this->travel(3)->minutes();
        $analyzer->enqueue($profile->fresh());
        Queue::assertNotPushed(AnalyzeLearnerInterview::class);
    }

    public function test_running_old_attempt_does_not_save_over_a_new_request_with_the_same_input(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $profile = $this->profile($path);
        $analyzer = app(LearnerInterviewAnalyzer::class);
        $this->prepare($profile, $analyzer);
        $job = new AnalyzeLearnerInterview($profile->id, $profile->analysis_hash, $profile->analysis_requested_at->toDateTimeString());
        Http::fake(function () use ($profile, $path) {
            $profile->fresh()->forceFill(['analysis_status' => 'queued', 'analysis_requested_at' => now()->addMinute()])->save();

            return Http::response(['choices' => [['message' => ['content' => json_encode($this->analysisResult($path))]]]]);
        });
        $job->handle($analyzer);
        $this->assertSame('queued', $profile->fresh()->analysis_status);
        $this->assertNull($profile->fresh()->analysis_result);
    }

    public function test_connection_failure_keeps_analysis_retryable_without_losing_answers(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $profile = $this->profile($path);
        $analyzer = app(LearnerInterviewAnalyzer::class);
        $this->prepare($profile, $analyzer);
        Http::fake(['localhost:20128/v1/chat/completions' => Http::failedConnection()]);

        try {
            (new AnalyzeLearnerInterview($profile->id, $profile->analysis_hash))->handle($analyzer);
            $this->fail('A disconnected provider must not complete analysis.');
        } catch (ConnectionException) {
            $this->assertSame('queued', $profile->fresh()->analysis_status);
            $this->assertSame([$path->id], $profile->fresh()->learning_path_ids);
        }
    }

    public function test_invalid_result_fails_without_retrying_permanent_validation_errors(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $profile = $this->profile($path);
        $analyzer = app(LearnerInterviewAnalyzer::class);
        $this->prepare($profile, $analyzer);
        Http::fake(['localhost:20128/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => 'invalid']]]])]);

        (new AnalyzeLearnerInterview($profile->id, $profile->analysis_hash))->handle($analyzer);

        $this->assertSame('failed', $profile->fresh()->analysis_status);
        $this->assertNull($profile->fresh()->analysis_result);
        Http::assertSentCount(1);
    }

    public function test_model_change_hides_previous_graph_until_new_analysis_is_requested(): void
    {
        Queue::fake([AnalyzeLearnerInterview::class]);
        $path = LearningPath::factory()->create(['is_published' => true]);
        $profile = $this->profile($path);
        $this->complete($profile, $path);
        config(['services.ai.model' => 'new-local-model']);

        $this->withCookie('artaswaha_device', $profile->visitor_id)->getJson(route('profile.analysis.show'))
            ->assertOk()->assertJsonPath('analysis.status', 'outdated')->assertJsonPath('analysis.recommendations', []);
        $this->post(route('profile.analysis.store'))->assertRedirect();
        Queue::assertPushed(AnalyzeLearnerInterview::class, 1);
    }

    public function test_scheduled_analysis_refreshes_after_six_hours_without_duplicate_requests(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 26)->setTime(12, 0));
        Queue::fake([AnalyzeLearnerInterview::class]);
        $path = LearningPath::factory()->create();
        $profile = $this->profile($path);
        $this->complete($profile, $path);

        $this->artisan('learning:analyze')->assertExitCode(0);
        Queue::assertNotPushed(AnalyzeLearnerInterview::class);
        LearningProgress::factory()->create(['visitor_id' => $profile->visitor_id]);
        $this->artisan('learning:analyze')->assertExitCode(0);
        Queue::assertNotPushed(AnalyzeLearnerInterview::class);
        $this->travel(6)->hours();
        $this->artisan('learning:analyze')->assertExitCode(0);
        $this->artisan('learning:analyze')->assertExitCode(0);

        Queue::assertPushed(AnalyzeLearnerInterview::class, 1);
        $this->assertSame('queued', $profile->fresh()->analysis_status);
        $this->assertSame(1, $profile->fresh()->analysis_input['activity']['lessons_completed_7']);
        Http::assertNothingSent();
    }

    public function test_scheduler_uses_only_latest_account_profile_and_skips_unconfigured_ai(): void
    {
        Queue::fake([AnalyzeLearnerInterview::class]);
        $user = User::factory()->create();
        $path = LearningPath::factory()->create();
        $olderProfile = $this->profile($path, $user);
        $latestProfile = $this->profile($path, $user);
        $anonymousProfile = $this->profile($path);
        config(['services.ai.model' => '']);
        $this->artisan('learning:analyze')->assertExitCode(0);
        Queue::assertNotPushed(AnalyzeLearnerInterview::class);

        config(['services.ai.model' => 'local-test']);
        $this->artisan('learning:analyze')->assertExitCode(0);

        $this->assertNull($olderProfile->fresh()->analysis_requested_at);
        $this->assertSame('queued', $latestProfile->fresh()->analysis_status);
        $this->assertSame('queued', $anonymousProfile->fresh()->analysis_status);
        Queue::assertPushed(AnalyzeLearnerInterview::class, 2);
        Http::assertNothingSent();
    }

    public function test_activity_snapshot_compares_weeks_and_excludes_other_accounts(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 26)->setTime(12, 0));
        $user = User::factory()->create();
        $profile = $this->profile(LearningPath::factory()->create(), $user);
        LearningProgress::factory()->for($user)->create(['completed_at' => now()->subDay()]);
        LearningProgress::factory()->for($user)->create(['completed_at' => now()->subDays(8)]);
        LearningProgress::factory()->for($user)->create(['completed_at' => now()->subDays(15)]);
        LearningProgress::factory()->create(['visitor_id' => $profile->visitor_id]);
        $participant = RoomParticipant::factory()->for($user)->create();
        RoomAnswer::factory()->for($participant, 'participant')->create(['answered_at' => now()->subDays(2)]);
        RoomAnswer::factory()->for($participant, 'participant')->create(['is_correct' => false, 'answered_at' => now()->subDays(2)]);
        RoomAnswer::factory()->create();

        $activity = app(LearnerInterviewAnalyzer::class)->activity($profile);

        $this->assertSame(2, $activity['active_days_7']);
        $this->assertSame(1, $activity['active_days_previous_7']);
        $this->assertSame(1, $activity['lessons_completed_7']);
        $this->assertSame(1, $activity['lessons_completed_previous_7']);
        $this->assertSame(2, $activity['quiz_answers_14']);
        $this->assertSame(1, $activity['quiz_correct_14']);
        $this->assertCount(14, $activity['daily']);
    }

    public function test_anonymous_activity_only_includes_its_unclaimed_progress_and_quiz_answers(): void
    {
        $this->freezeTime();
        $profile = $this->profile(LearningPath::factory()->create());
        LearningProgress::factory()->create(['visitor_id' => $profile->visitor_id]);
        LearningProgress::factory()->for(User::factory())->create(['visitor_id' => $profile->visitor_id]);
        LearningProgress::factory()->create();
        $participant = RoomParticipant::factory()->create(['learner_profile_id' => $profile->id, 'user_id' => null]);
        RoomAnswer::factory()->for($participant, 'participant')->create();
        RoomAnswer::factory()->create();

        $activity = app(LearnerInterviewAnalyzer::class)->activity($profile);

        $this->assertSame(1, $activity['active_days_7']);
        $this->assertSame(1, $activity['lessons_completed_7']);
        $this->assertSame(1, $activity['quiz_answers_14']);
        $this->assertSame(1, $activity['quiz_correct_14']);
    }

    public function test_learning_during_ai_generation_does_not_discard_the_snapshot_result(): void
    {
        $this->freezeTime();
        $path = LearningPath::factory()->create();
        $profile = $this->profile($path);
        $analyzer = app(LearnerInterviewAnalyzer::class);
        $this->prepare($profile, $analyzer);
        Http::fake(function (ClientRequest $request) use ($profile, $path) {
            $input = json_decode($request['messages'][1]['content'], true);
            $this->assertSame(0, $input['learning_activity']['lessons_completed_7']);
            $this->assertArrayNotHasKey('visitor_id', $input);
            LearningProgress::factory()->create(['visitor_id' => $profile->visitor_id]);

            return Http::response(['choices' => [['message' => ['content' => json_encode($this->analysisResult($path))]]]]);
        });

        (new AnalyzeLearnerInterview($profile->id, $profile->analysis_hash))->handle($analyzer);

        $this->assertSame('completed', $profile->fresh()->analysis_status);
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('profile.show'))
            ->assertOk()->assertInertiaContains('Mulai dari materi pilihanmu.')->assertInertia(fn (AssertableInertia $page) => $page->etc());
        Http::assertSentCount(1);
    }

    public function test_authenticated_lesson_completion_is_attributed_to_the_account_for_analysis(): void
    {
        $user = User::factory()->create();
        $path = LearningPath::factory()->create();
        $lesson = Lesson::factory()->for($path, 'learningPath')->create(['exercise_prompt' => null]);
        $this->actingAs($user)->post(route('learning.progress.store', $lesson->slug))->assertRedirect();

        $this->assertDatabaseHas('learning_progress', ['user_id' => $user->id, 'lesson_id' => $lesson->id]);
        $profile = $this->profile($path, $user);
        $this->assertSame(1, app(LearnerInterviewAnalyzer::class)->activity($profile)['lessons_completed_7']);
    }

    public function test_a_stale_queue_is_presented_as_delayed_and_logged_once_without_marking_the_job_failed(): void
    {
        Log::spy();
        $path = LearningPath::factory()->create();
        $profile = $this->profile($path);
        $analyzer = app(LearnerInterviewAnalyzer::class);
        $this->prepare($profile, $analyzer);
        $profile->forceFill(['analysis_requested_at' => now()->subMinutes(11)])->save();
        foreach (range(1, 2) as $request) {
            $this->withCookie('artaswaha_device', $profile->visitor_id)->getJson(route('profile.analysis.show'))
                ->assertOk()->assertJsonPath('analysis.status', 'delayed');
        }
        $this->assertSame('queued', $profile->fresh()->analysis_status);
        Log::shouldHaveReceived('warning')->with('Learner analysis is taking longer than expected.', \Mockery::on(fn (array $context): bool => $context['profile_id'] === $profile->id && $context['queue'] === 'default' && $context['connection'] === 'database'))->once();
        Http::assertNothingSent();
    }

    public function test_invalid_provider_response_is_logged_without_its_private_contents(): void
    {
        Log::spy();
        $path = LearningPath::factory()->create();
        $profile = $this->profile($path);
        $analyzer = app(LearnerInterviewAnalyzer::class);
        $this->prepare($profile, $analyzer);
        Http::fake(['localhost:20128/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => 'private raw response']]]])]);
        (new AnalyzeLearnerInterview($profile->id, $profile->analysis_hash))->handle($analyzer);
        $this->assertSame('failed', $profile->fresh()->analysis_status);
        Log::shouldHaveReceived('warning')->with('Learner analysis provider returned invalid JSON.', \Mockery::on(fn (array $context): bool => $context['content_bytes'] === 20 && ! str_contains(json_encode($context), 'private raw response')))->once();
        Log::shouldHaveReceived('warning')->with('Learner analysis failed.', \Mockery::type('array'))->once();
    }

    public function test_provider_http_errors_log_status_and_model_without_response_body(): void
    {
        Log::spy();
        Http::fake(['localhost:20128/v1/chat/completions' => Http::response(['error' => ['message' => 'private provider content']], 401)]);
        try {
            app(LearnerInterviewAnalyzer::class)->requestJson([], []);
            $this->fail('Expected a provider error.');
        } catch (\UnexpectedValueException) {
            Log::shouldHaveReceived('warning')->with('Learner analysis provider returned an error.', \Mockery::on(fn (array $context): bool => $context['http_status'] === 401 && isset($context['model']) && ! str_contains(json_encode($context), 'private provider content')))->once();
        }
    }

    private function profile(LearningPath $path, ?User $user = null): LearnerProfile
    {
        return LearnerProfile::create([
            'visitor_id' => (string) Str::uuid(), 'user_id' => $user?->id,
            'learning_path_id' => $path->id, 'learning_path_ids' => [$path->id],
            'role' => 'murid', 'name' => 'Private name', 'mode' => $user === null ? 'anonymous' : 'account',
        ]);
    }

    private function prepare(LearnerProfile $profile, LearnerInterviewAnalyzer $analyzer): void
    {
        $input = $analyzer->input($profile);
        $profile->forceFill(['analysis_input' => $input, 'analysis_hash' => $analyzer->hash($input), 'analysis_status' => 'queued', 'analysis_requested_at' => now()])->save();
    }

    private function complete(LearnerProfile $profile, LearningPath $path, string $summary = 'Mulai dari materi pilihanmu.'): void
    {
        $this->prepare($profile, app(LearnerInterviewAnalyzer::class));
        $result = $this->analysisResult($path);
        $result['summary'] = $summary;
        $profile->forceFill(['analysis_status' => 'completed', 'analysis_result' => $result, 'analysis_completed_at' => now()])->save();
    }

    /** @return array{summary: string, recommendations: list<array{learning_path_id: int, reason: string}>} */
    private function analysisResult(LearningPath $path): array
    {
        return ['summary' => 'Mulai dari materi pilihanmu.', 'recommendations' => [['learning_path_id' => $path->id, 'reason' => 'Materi ini termasuk pilihanmu sebagai murid.']]];
    }
}
