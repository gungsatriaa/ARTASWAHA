<?php

namespace Tests\Feature;

use App\Jobs\GenerateAiReport;
use App\Models\AiReport;
use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\QuizDraft;
use App\Models\User;
use App\Services\AdminAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminAiManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['services.ai' => ['url' => 'http://localhost:20128/v1', 'model' => 'test-model', 'key' => 'private-secret']]);
    }

    public function test_ai_management_and_audio_require_admin_access(): void
    {
        $this->get(route('admin.ai.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.ai.index'))->assertForbidden();
        $this->get(route('admin.audio.index'))->assertForbidden();
        $this->post(route('admin.ai.reports.store'), ['start' => now()->toDateString(), 'end' => now()->toDateString()])->assertForbidden();
        $lesson = Lesson::factory()->create();
        $this->get(route('admin.lessons.preview', $lesson))->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_admin_tabs_render_without_calling_ai_or_exposing_secrets(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach (['overview', 'interviews', 'drafts', 'services'] as $tab) {
            $this->get(route('admin.ai.index', ['tab' => $tab]))->assertOk()->assertInertiaContains('Pusat AI')->assertInertiaNotContains('private-secret');
        }
        $this->get(route('admin.index'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
        Http::assertNothingSent();
    }

    public function test_snapshot_uses_period_data_and_excludes_identity(): void
    {
        $path = LearningPath::factory()->create(['title' => 'Aksara']);
        $lesson = Lesson::factory()->create(['learning_path_id' => $path->id]);
        LearningProgress::factory()->create(['lesson_id' => $lesson->id, 'completed_at' => now()]);
        LearningProgress::factory()->create(['lesson_id' => $lesson->id, 'completed_at' => now()->subDays(30)]);
        $profile = $this->profile($path);
        $snapshot = app(AdminAiService::class)->snapshot(now()->subDays(13), now());

        $this->assertSame(1, $snapshot['new_interviews']);
        $this->assertSame(1, $snapshot['completed_lessons']);
        $this->assertSame(0, $snapshot['quiz_answers']);
        $this->assertSame([['title' => 'Aksara', 'total' => 1]], $snapshot['primary_materials']);
        $this->assertStringNotContainsString($profile->visitor_id, json_encode($snapshot));
        $this->assertStringNotContainsString('Private learner', json_encode($snapshot));
    }

    public function test_report_requests_are_queued_and_reused_for_identical_snapshot(): void
    {
        Queue::fake([GenerateAiReport::class]);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $data = ['start' => now()->subDays(13)->toDateString(), 'end' => now()->toDateString()];

        $this->post(route('admin.ai.reports.store'), $data)->assertRedirect();
        $report = AiReport::firstOrFail();
        $this->post(route('admin.ai.reports.store'), $data)->assertRedirect(route('admin.ai.reports.show', $report));

        $this->assertDatabaseCount('ai_reports', 1);
        $this->assertSame('queued', $report->status);
        Queue::assertPushed(GenerateAiReport::class, 1);
        Http::assertNothingSent();
    }

    public function test_report_job_sends_only_aggregate_data_and_escapes_saved_output(): void
    {
        $path = LearningPath::factory()->create();
        $profile = $this->profile($path);
        $snapshot = app(AdminAiService::class)->snapshot(now()->subDays(13), now());
        $report = AiReport::factory()->create(['snapshot' => $snapshot, 'status' => 'queued']);
        $result = ['summary' => '<script>unsafe()</script>', 'observations' => ['Data periode tersedia.'], 'suggestions' => ['Tinjau materi pilihan.']];
        Http::fake(['localhost:20128/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => json_encode($result)]]]])]);

        (new GenerateAiReport($report->id))->handle(app(AdminAiService::class));

        $this->assertSame('completed', $report->fresh()->status);
        Http::assertSent(fn (ClientRequest $request): bool => ! str_contains($request['messages'][1]['content'], $profile->visitor_id)
            && ! str_contains($request['messages'][1]['content'], 'Private learner') && $request['stream'] === false);
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('admin.ai.reports.show', $report))
            ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc())->assertInertiaContains('<script>unsafe()')->assertDontSee('<script>unsafe()', false);
    }

    public function test_invalid_report_output_is_failed_and_not_displayed_as_statistics(): void
    {
        $report = AiReport::factory()->create(['status' => 'queued']);
        Http::fake(['localhost:20128/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => '{"summary":"Bad","invented_metric":100}']]]])]);

        (new GenerateAiReport($report->id))->handle(app(AdminAiService::class));

        $this->assertSame('failed', $report->fresh()->status);
        $this->assertNull($report->fresh()->result);
    }

    public function test_unconfigured_reports_keep_statistics_available_without_jobs(): void
    {
        Queue::fake([GenerateAiReport::class]);
        config(['services.ai.model' => '']);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->post(route('admin.ai.reports.store'), ['start' => now()->toDateString(), 'end' => now()->toDateString()])->assertRedirect();
        $report = AiReport::firstOrFail();
        $this->assertSame('unavailable', $report->status);
        $this->get(route('admin.ai.reports.show', $report))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
        Queue::assertNotPushed(GenerateAiReport::class);
        Http::assertNothingSent();
    }

    public function test_interview_details_use_admin_retry_without_polling_another_profile(): void
    {
        $profile = $this->profile(LearningPath::factory()->create());
        $profile->forceFill(['analysis_status' => 'queued'])->save();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('admin.ai.profiles.show', $profile))
            ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc())->assertInertiaContains(route('admin.ai.profiles.retry', $profile))
            ->assertInertiaNotContains($profile->visitor_id);
    }

    public function test_draft_details_are_admin_only_and_escape_ai_content(): void
    {
        $owner = User::factory()->create();
        $draft = QuizDraft::create(['user_id' => $owner->id, 'status' => 'completed', 'source_path' => 'private/file.pdf', 'questions' => [['prompt' => '<script>bad()</script>', 'options' => ['A', 'B'], 'correct_option' => 0, 'explanation' => 'Alasan']]]);
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('admin.ai.drafts.show', $draft))
            ->assertOk()->assertInertiaContains('<script>bad()')->assertDontSee('<script>bad()', false)->assertInertiaNotContains('private/file.pdf');
        $this->actingAs($owner)->get(route('admin.ai.drafts.show', $draft))->assertForbidden();
    }

    public function test_new_module_flow_handles_missing_paths_and_prefills_selected_path(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get(route('admin.lessons.create'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
        $path = LearningPath::factory()->create();
        $response = $this->get(route('admin.lessons.create', ['learning_path_id' => $path->id]))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
        $this->assertSame($path->id, $response->viewData('page')['props']['lesson']['learning_path_id']);
        $response = $this->post(route('admin.lessons.store'), ['learning_path_id' => $path->id, 'title' => 'Salam Bali Baru', 'slug' => '', 'summary' => 'Salam', 'content' => 'Rahajeng semeng.', 'sort_order' => 1]);
        $response->assertRedirect(route('admin.lessons.preview', Lesson::where('slug', 'salam-bali-baru')->firstOrFail()));
        $this->assertDatabaseHas('lessons', ['slug' => 'salam-bali-baru', 'is_published' => false]);
    }

    public function test_preview_is_private_and_does_not_publish_or_complete_learning(): void
    {
        $lesson = Lesson::factory()->create(['is_published' => false, 'reviewed_at' => null, 'content' => '<script>bad()</script>']);
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('admin.lessons.preview', $lesson))
            ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc())->assertInertiaContains('<script>bad()')->assertDontSee('<script>bad()', false);
        $this->get(route('learning.lessons.show', $lesson->slug))->assertNotFound();
        $this->assertFalse($lesson->fresh()->is_published);
        $this->assertDatabaseCount('learning_progress', 0);
    }

    public function test_interview_and_module_lists_filter_and_paginate(): void
    {
        $path = LearningPath::factory()->create();
        Lesson::factory()->count(22)->create(['learning_path_id' => $path->id]);
        $profile = $this->profile($path);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $response = $this->get(route('admin.lessons.index'))->assertOk();
        $this->assertCount(20, $response->viewData('page')['props']['lessons']['data']);
        $this->get(route('admin.ai.index', ['tab' => 'interviews', 'role' => 'murid']))->assertOk()->assertInertiaValue('profiles.data.0.id', $profile->id);
        $this->get(route('admin.ai.index', ['tab' => 'interviews', 'role' => 'guru']))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->has('profiles.data', 0));
        $this->getJson(route('admin.ai.index', ['start' => now()->subDays(100)->toDateString(), 'end' => now()->toDateString()]))->assertUnprocessable();
    }

    private function profile(LearningPath $path): LearnerProfile
    {
        return LearnerProfile::create(['visitor_id' => (string) Str::uuid(), 'learning_path_id' => $path->id, 'learning_path_ids' => [$path->id], 'role' => 'murid', 'name' => 'Private learner', 'mode' => 'anonymous']);
    }
}
