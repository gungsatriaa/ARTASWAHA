<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\LearningProgress;
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

    public function test_subject_navigation_only_shares_published_paths_and_public_fields(): void
    {
        $paribasa = LearningPath::factory()->create(['group' => 'paribasa', 'is_published' => true]);
        $aksara = LearningPath::factory()->create(['group' => 'aksara', 'is_published' => true]);
        $hidden = LearningPath::factory()->create(['is_published' => false]);
        $this->get(route('login'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->has('materials.paribasa', 1)->has('materials.aksara', 1)
            ->where('materials.paribasa.0.title', $paribasa->title)
            ->where('materials.aksara.0.url', route('learning.paths.show', $aksara->slug))
            ->missing('materials.paribasa.0.created_at')->missing('materials.paribasa.0.is_published'))
            ->assertInertiaNotContains($hidden->title);
    }

    public function test_public_visitors_use_the_react_catalog(): void
    {
        LearningPath::factory()->create(['title' => 'Materi publik']);
        $this->get(route('learning.paths.index'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('Learning/Catalog')->where('viewer', null)->where('paths.data.0.title', 'Materi publik'));
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

    public function test_path_progress_counts_unique_completed_modules_across_all_pages_for_the_current_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $path = LearningPath::factory()->create();
        $lessons = Lesson::factory()->count(13)->for($path, 'learningPath')->sequence(fn ($sequence) => ['sort_order' => $sequence->index])->create();
        LearningProgress::factory()->count(2)->create(['user_id' => $user->id, 'lesson_id' => $lessons[12]->id]);
        LearningProgress::factory()->create(['user_id' => $other->id, 'lesson_id' => $lessons[0]->id]);
        LearningProgress::factory()->create(['user_id' => $user->id, 'lesson_id' => $lessons[1]->id, 'completed_at' => null]);
        $hidden = Lesson::factory()->for($path, 'learningPath')->create(['is_published' => false]);
        LearningProgress::factory()->create(['user_id' => $user->id, 'lesson_id' => $hidden->id]);

        $this->actingAs($user)->get(route('learning.paths.show', ['learningPath' => $path->slug, 'page' => 2]))
            ->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->where('path.progress', 8)->where('path.completedLessons', 1)
            ->where('lessons.total', 13)->has('lessons.data', 1)
            ->where('lessons.data.0.progress', 100)->missing('lessons.data.0.visitor_id'));
    }

    public function test_path_progress_only_includes_unclaimed_progress_for_the_anonymous_device(): void
    {
        $path = LearningPath::factory()->create();
        $lessons = Lesson::factory()->count(2)->for($path, 'learningPath')->sequence(['sort_order' => 0], ['sort_order' => 1])->create();
        $profile = LearnerProfile::create([
            'visitor_id' => (string) Str::uuid(), 'learning_path_id' => $path->id, 'learning_path_ids' => [$path->id],
            'name' => 'Kadek', 'role' => 'murid', 'mode' => 'anonymous', 'avatar' => 'avatar/penyu.webp',
        ]);
        LearningProgress::factory()->create(['visitor_id' => $profile->visitor_id, 'lesson_id' => $lessons[0]->id]);
        LearningProgress::factory()->create(['visitor_id' => $profile->visitor_id, 'lesson_id' => $lessons[1]->id, 'user_id' => User::factory()->create()->id]);
        LearningProgress::factory()->create(['lesson_id' => $lessons[1]->id]);

        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('learning.paths.show', $path->slug))
            ->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->where('path.progress', 50)->where('path.completedLessons', 1)
            ->where('lessons.data.0.progress', 100)->where('lessons.data.1.progress', 0));
    }

    public function test_empty_path_has_zero_progress_for_public_visitors(): void
    {
        $path = LearningPath::factory()->create();
        $this->get(route('learning.paths.show', $path->slug))->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->where('path.progress', 0)->where('path.completedLessons', 0)->where('lessons.total', 0));
    }

    public function test_legacy_preview_query_does_not_switch_the_renderer(): void
    {
        $this->get(route('learning.paths.index', ['ui' => 'blade']))->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page->component('Learning/Catalog'));
    }

    public function test_home_is_an_inertia_page_in_every_environment(): void
    {
        $version = (string) app(HandleInertiaRequests::class)->version(Request::create('/'));
        $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => $version])->get(route('home'))
            ->assertOk()->assertJsonPath('component', 'Home');
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
