<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReactLearningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ui.react_enabled' => true]);
    }

    public function test_public_visitors_keep_server_rendered_blade_content(): void
    {
        LearningPath::factory()->create(['title' => 'Materi publik']);
        $this->get(route('learning.paths.index'))->assertOk()->assertSee('Materi publik')->assertDontSee('resources/js/react/app.jsx');
    }

    public function test_authenticated_catalog_is_paginated_and_has_only_public_fields(): void
    {
        LearningPath::factory()->count(14)->create(['is_published' => true]);
        LearningPath::factory()->create(['is_published' => false, 'title' => 'Draf rahasia']);
        $this->actingAs(User::factory()->create(['name' => 'Komang']))->get(route('learning.paths.index'))
            ->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('Learning/Catalog')
            ->has('paths.data', 12)->where('paths.total', 14)->where('viewer.name', 'Komang')
            ->where('viewer.mode', 'account')->missing('viewer.email')->missing('viewer.password')
            ->missing('paths.data.0.is_published')->missing('paths.data.0.created_at'));
    }

    public function test_completed_anonymous_profile_uses_react_without_exposing_device_identity(): void
    {
        $path = LearningPath::factory()->create();
        $profile = LearnerProfile::create([
            'visitor_id' => (string) Str::uuid(), 'learning_path_id' => $path->id, 'learning_path_ids' => [$path->id],
            'name' => 'Kadek', 'role' => 'murid', 'mode' => 'anonymous', 'avatar' => 'avatar/penyu.webp',
        ]);
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('learning.paths.index'))
            ->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('Learning/Catalog')
            ->where('viewer.mode', 'anonymous')->where('viewer.name', 'Kadek')->missing('viewer.visitor_id')
            ->where('viewer.avatar', asset('avatar/penyu.webp')));
    }

    public function test_catalog_search_and_group_filter_are_applied_on_the_server(): void
    {
        LearningPath::factory()->create(['title' => 'Aksara pilihan', 'group' => 'aksara']);
        LearningPath::factory()->create(['title' => 'Aksara lainnya', 'group' => 'paribasa']);
        $this->actingAs(User::factory()->create())->get(route('learning.paths.index', ['q' => 'pilihan', 'group' => 'aksara']))
            ->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('Learning/Catalog')
            ->has('paths.data', 1)->where('paths.data.0.title', 'Aksara pilihan')->where('filters.q', 'pilihan')->where('filters.group', 'aksara'));
    }

    public function test_path_only_includes_published_reviewed_lessons_without_answers(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $lesson = Lesson::factory()->for($path, 'learningPath')->create(['title' => 'Pelajaran tersedia']);
        Lesson::factory()->for($path, 'learningPath')->create(['is_published' => false]);
        Lesson::factory()->for($path, 'learningPath')->create(['reviewed_at' => null]);
        $this->actingAs(User::factory()->create())->get(route('learning.paths.show', $path->slug))->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page->component('Learning/Path')->has('lessons.data', 1)
                ->where('lessons.data.0.title', $lesson->title)->where('lessons.data.0.url', route('learning.lessons.show', $lesson->slug))
                ->missing('lessons.data.0.correct_option')->missing('lessons.data.0.exercise_explanation')->missing('lessons.data.0.content'));
        $path->update(['is_published' => false]);
        $this->get(route('learning.paths.show', $path->slug))->assertNotFound();
    }

    public function test_renderer_switch_keeps_the_original_blade_page_available(): void
    {
        config(['ui.react_enabled' => false]);
        LearningPath::factory()->create(['title' => 'Katalog lama']);
        $this->actingAs(User::factory()->create())->get(route('learning.paths.index'))
            ->assertOk()->assertSee('Katalog lama')->assertDontSee('resources/js/react/app.jsx');
    }

    public function test_react_is_opt_in_outside_the_local_environment(): void
    {
        config(['ui.react_enabled' => null]);
        LearningPath::factory()->create(['title' => 'Materi Blade']);
        $this->actingAs(User::factory()->create())->get(route('learning.paths.index'))
            ->assertOk()->assertSee('Materi Blade')->assertDontSee('resources/js/react/app.jsx');
    }

    public function test_inertia_visit_to_a_legacy_page_requests_a_document_navigation(): void
    {
        $version = (string) app(HandleInertiaRequests::class)->version(Request::create('/'));
        $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => $version])->get(route('home'))
            ->assertStatus(409)->assertHeader('X-Inertia-Location', route('home'));
    }

    public function test_partial_reload_omits_other_page_data(): void
    {
        LearningPath::factory()->create();
        $version = (string) app(HandleInertiaRequests::class)->version(Request::create('/'));
        $this->actingAs(User::factory()->create())->withHeaders([
            'X-Inertia' => 'true', 'X-Inertia-Version' => $version,
            'X-Inertia-Partial-Component' => 'Learning/Catalog', 'X-Inertia-Partial-Data' => 'paths',
        ])->get(route('learning.paths.index'))->assertOk()->assertJsonPath('component', 'Learning/Catalog')
            ->assertJsonMissingPath('props.filters')->assertJsonMissingPath('props.viewer')->assertJsonCount(1, 'props.paths.data');
    }

    public function test_external_validation_rejects_invalid_filter_values(): void
    {
        $this->actingAs(User::factory()->create())->get(route('learning.paths.index', ['group' => 'invalid']))
            ->assertRedirect()->assertSessionHasErrors('group');
    }
}
