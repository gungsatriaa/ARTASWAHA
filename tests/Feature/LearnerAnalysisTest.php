<?php

namespace Tests\Feature;

use App\Jobs\AnalyzeLearnerInterview;
use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\User;
use App\Services\LearnerInterviewAnalyzer;
use App\Services\LearningProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class LearnerAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.learner_analysis' => [
            'enabled' => true, 'provider' => 'ollama', 'url' => 'http://localhost:11434', 'model' => 'local-test', 'key' => '',
        ]]);
        Http::preventStrayRequests();
        $this->withCredentials();
    }

    public function test_finishing_interview_queues_once_without_contacting_llm(): void
    {
        Queue::fake([AnalyzeLearnerInterview::class]);
        $path = LearningPath::factory()->create(['is_published' => true]);
        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_ids' => [$path->id]]);
        $this->post(route('learning.interview.store'), ['step' => 2, 'role' => 'murid']);
        $this->post(route('learning.interview.store'), ['step' => 3, 'mode' => 'anonymous']);

        $this->post(route('learning.interview.store'), ['step' => 4, 'name' => 'Private name'])
            ->assertRedirect(route('learning.paths.show', $path->slug));

        $profile = LearnerProfile::firstOrFail();
        $this->assertSame(['role' => 'murid', 'learning_path_ids' => [$path->id]], $profile->analysis_input);
        $this->assertSame('queued', $profile->analysis_status);
        $this->withCookie('artaswaha_device', $profile->visitor_id)->post(route('profile.analysis.store'))->assertRedirect();
        Queue::assertPushed(AnalyzeLearnerInterview::class, 1);
        Http::assertNothingSent();
    }

    public function test_unconfigured_ollama_keeps_learning_available_without_queuing(): void
    {
        Queue::fake([AnalyzeLearnerInterview::class]);
        config(['services.learner_analysis.model' => '']);
        $path = LearningPath::factory()->create(['is_published' => true]);
        $profile = $this->profile($path);

        app(LearnerInterviewAnalyzer::class)->enqueue($profile);

        $this->assertSame('unavailable', $profile->fresh()->analysis_status);
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('profile.show'))
            ->assertOk()->assertSee('Rekomendasi AI belum tersedia')->assertSee('Progres belajar');
        Queue::assertNotPushed(AnalyzeLearnerInterview::class);
        Http::assertNothingSent();
    }

    public function test_ollama_result_is_saved_and_shown_with_valid_graph_in_all_user_outputs(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true, 'title' => 'Aksara pilihan']);
        $profile = $this->profile($path);
        $analyzer = app(LearnerInterviewAnalyzer::class);
        $this->prepare($profile, $analyzer);
        Http::fake(['localhost:11434/api/chat' => Http::response(['message' => ['content' => json_encode($this->analysisResult($path))]])]);

        (new AnalyzeLearnerInterview($profile->id, $profile->analysis_hash))->handle($analyzer);

        $this->assertSame('completed', $profile->fresh()->analysis_status);
        $this->assertNotNull($profile->fresh()->analysis_completed_at);
        Http::assertSent(function (ClientRequest $request) use ($path): bool {
            $input = json_decode($request['messages'][1]['content'], true);

            return $request->url() === 'http://localhost:11434/api/chat'
                && $request['model'] === 'local-test' && $request['stream'] === false
                && $request['format']['properties']['recommendations']['items']['properties']['learning_path_id']['enum'] === [$path->id]
                && $request['options']['num_predict'] === 1000
                && ! str_contains(json_encode($input), 'Private name')
                && str_contains($request['messages'][0]['content'], 'Jangan menebak');
        });
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('profile.show'))
            ->assertOk()->assertSee('Analisis belajarmu')->assertSee('Peta belajarmu')->assertSee('Mulai dari materi pilihanmu.');
        $response = $this->getJson(route('profile.analysis.show'))->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('analysis.graph.nodes.1.id', 'path-'.$path->id)
            ->assertJsonPath('analysis.graph.edges.0', ['source' => 'role', 'target' => 'path-'.$path->id]);
        $this->assertCount(3, $response->json('analysis.graph.nodes'));
        $this->assertCount(2, $response->json('analysis.graph.edges'));
        $this->get(route('home'))->assertOk()->assertSee('Lihat analisis dan peta belajarmu');
        $this->get(route('learning.paths.show', $path->slug))->assertOk()->assertSee('Direkomendasikan untuk pilihan belajarmu');
        $analyzer->enqueue($profile->fresh());
        (new AnalyzeLearnerInterview($profile->id, $profile->analysis_hash))->handle($analyzer);
        Http::assertSentCount(1);
    }

    public function test_openai_compatible_local_server_can_be_configured(): void
    {
        config(['services.learner_analysis.provider' => 'openai', 'services.learner_analysis.url' => 'http://localhost:8000/v1', 'services.learner_analysis.key' => 'test-key']);
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
        Http::fake(['localhost:11434/api/chat' => Http::response(['message' => ['content' => $content]])]);

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
        $this->actingAs($user)->get(route('profile.show'))->assertOk()->assertSee('Peta belajarmu')->assertDontSee('Belajar sebagai Private name');
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
        $this->actingAs($user)->get(route('profile.show'))->assertOk()->assertSee('Peta belajarmu');
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
            ->assertSee('&lt;script&gt;summary()', false)->assertDontSee('<script>summary()', false)
            ->assertSee('&lt;img', false)->assertDontSee('<img src=x onerror=', false);
        $response = $this->getJson(route('profile.analysis.show'))->assertOk();
        $this->assertStringNotContainsString('<script>summary()', $response->json('html'));
        $this->assertStringNotContainsString('<script>material()', $response->json('html'));
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

            return Http::response(['message' => ['content' => json_encode($this->analysisResult($path))]]);
        });

        $job->handle($analyzer);

        $this->assertNull($profile->fresh()->analysis_result);
        $this->assertNotSame('completed', $profile->fresh()->analysis_status);
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
        Http::fake(['localhost:11434/api/chat' => Http::response([], 503)]);
        $job = new AnalyzeLearnerInterview($profile->id, $profile->analysis_hash);
        try {
            $job->handle($analyzer);
            $this->fail('Provider failure must not produce a completed analysis.');
        } catch (RuntimeException $exception) {
            $job->failed($exception);
        }

        $this->assertSame('failed', $profile->fresh()->analysis_status);
        $this->assertSame([$path->id], $profile->fresh()->learning_path_ids);
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('profile.show'))->assertOk()->assertSee('Analisis belum berhasil');
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

    public function test_real_queue_is_durable_and_deduplicated_even_when_default_connection_is_sync(): void
    {
        config(['queue.default' => 'sync']);
        $path = LearningPath::factory()->create(['is_published' => true]);
        $profile = $this->profile($path);
        $analyzer = app(LearnerInterviewAnalyzer::class);

        $analyzer->enqueue($profile);
        $analyzer->enqueue($profile->fresh());

        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseHas('jobs', ['queue' => 'learner-analysis']);
        $this->assertSame('queued', $profile->fresh()->analysis_status);
        Http::assertNothingSent();

        Http::fake(['localhost:11434/api/chat' => Http::response(['message' => ['content' => json_encode($this->analysisResult($path))]])]);
        $this->artisan('queue:work', ['connection' => 'learner-analysis', '--queue' => 'learner-analysis', '--once' => true, '--sleep' => 0])
            ->assertExitCode(0);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame('completed', $profile->fresh()->analysis_status);
        Http::assertSentCount(1);
    }

    public function test_connection_failure_keeps_analysis_retryable_without_losing_answers(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $profile = $this->profile($path);
        $analyzer = app(LearnerInterviewAnalyzer::class);
        $this->prepare($profile, $analyzer);
        Http::fake(['localhost:11434/api/chat' => Http::failedConnection()]);

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
        Http::fake(['localhost:11434/api/chat' => Http::response(['message' => ['content' => 'invalid']])]);

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
        config(['services.learner_analysis.model' => 'new-local-model']);

        $this->withCookie('artaswaha_device', $profile->visitor_id)->getJson(route('profile.analysis.show'))
            ->assertOk()->assertJsonPath('analysis.status', 'outdated')->assertJsonPath('analysis.recommendations', []);
        $this->post(route('profile.analysis.store'))->assertRedirect();
        Queue::assertPushed(AnalyzeLearnerInterview::class, 1);
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
