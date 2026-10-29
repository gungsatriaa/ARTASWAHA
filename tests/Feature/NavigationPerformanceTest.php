<?php

namespace Tests\Feature;

use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class NavigationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_path_navigation_reuses_the_anonymous_identity_and_bounds_lesson_queries(): void
    {
        $path = LearningPath::factory()->create();
        $lessons = Lesson::factory()->count(15)->create(['learning_path_id' => $path->id]);
        $visitor = (string) Str::uuid();
        LearnerProfile::create(['visitor_id' => $visitor, 'learning_path_id' => $path->id, 'role' => 'murid', 'name' => 'Kadek', 'mode' => 'anonymous']);
        LearningProgress::factory()->create(['visitor_id' => $visitor, 'lesson_id' => $lessons->first()->id]);
        DB::enableQueryLog();
        $response = $this->withCookie('artaswaha_device', $visitor)->get(route('learning.paths.show', $path->slug), ['X-React-Page' => 'true']);
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();
        $response->assertOk()->assertJsonPath('props.path.completedLessons', 1)
            ->assertJsonPath('props.lessons.data.0.progress', 100)
            ->assertJsonPath('props.lessons.data.1.locked', false)
            ->assertJsonPath('props.lessons.data.2.locked', true);
        $this->assertCount(1, $queries->filter(fn (string $query): bool => str_starts_with($query, 'select * from "learner_profiles"')));
        $this->assertLessThanOrEqual(3, $queries->filter(fn (string $query): bool => str_contains($query, 'from "lessons"'))->count());
    }

    public function test_partial_catalog_search_does_not_reload_navigation_materials(): void
    {
        $path = LearningPath::factory()->create();
        $this->actingAs(User::factory()->create());
        DB::enableQueryLog();
        $response = $this->get(route('learning.paths.index'), ['X-React-Page' => 'true', 'X-React-Component' => 'Learning/Catalog', 'X-React-Only' => 'paths,filters']);
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();
        $response->assertOk()->assertJsonPath('props.paths.data.0.id', $path->id)->assertJsonMissingPath('props.materials');
        $this->assertCount(0, $queries->filter(fn (string $query): bool => str_contains($query, '"title", "slug"')));
    }

    public function test_shared_materials_use_one_query_and_keep_each_group_bounded(): void
    {
        LearningPath::factory()->count(13)->create(['group' => 'paribasa']);
        LearningPath::factory()->count(13)->create(['group' => 'aksara']);
        DB::enableQueryLog();
        $response = $this->get(route('login'), ['X-React-Page' => 'true']);
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();
        $response->assertOk()->assertJsonCount(12, 'props.materials.paribasa')->assertJsonCount(12, 'props.materials.aksara');
        $this->assertCount(1, $queries->filter(fn (string $query): bool => str_contains($query, 'from "learning_paths"')));
    }

    public function test_shared_props_remain_fresh_for_every_identity(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Pertama']))
            ->get(route('learning.paths.index'), ['X-React-Page' => 'true'])
            ->assertOk()->assertJsonPath('props.viewer.name', 'Pertama')->assertJsonPath('props.navigation.learning', route('learning.paths.index'));
        $this->actingAs(User::factory()->create(['name' => 'Kedua']))
            ->get(route('learning.paths.index'), ['X-React-Page' => 'true'])
            ->assertOk()->assertJsonPath('props.viewer.name', 'Kedua')->assertJsonPath('props.navigation.learning', route('learning.paths.index'))
            ->assertJsonStructure(['props' => ['brand', 'links']])->assertJsonStructure(['props' => ['csrfToken', 'materials']]);
    }

    public function test_anonymous_about_page_reuses_the_middleware_profile(): void
    {
        $path = LearningPath::factory()->create();
        $visitor = (string) Str::uuid();
        LearnerProfile::create(['visitor_id' => $visitor, 'learning_path_id' => $path->id, 'role' => 'murid', 'name' => 'Kadek', 'mode' => 'anonymous']);
        DB::enableQueryLog();
        $this->withCookie('artaswaha_device', $visitor)->get(route('profile.about'), ['X-React-Page' => 'true'])
            ->assertOk()->assertJsonPath('props.viewer.name', 'Kadek');
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();
        $this->assertCount(1, $queries->filter(fn (string $query): bool => str_starts_with($query, 'select * from "learner_profiles"')));
    }

    public function test_prefetching_catalog_does_not_start_attempts_or_enqueue_analysis(): void
    {
        Lesson::factory()->create();
        $this->actingAs(User::factory()->create());
        Queue::fake();
        $this->get(route('learning.paths.index'), ['X-React-Page' => 'true', 'Purpose' => 'prefetch'])
            ->assertOk()->assertJsonCount(1, 'props.paths.data');
        $this->assertDatabaseCount('lesson_attempts', 0);
        Queue::assertNothingPushed();
    }

    public function test_statistics_queries_run_only_on_the_deferred_admin_request(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        DB::enableQueryLog();
        $this->get(route('admin.analytics.index'), ['X-React-Page' => 'true'])
            ->assertOk()->assertJsonMissingPath('props.statistics')->assertJsonPath('deferred', ['statistics']);
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::flushQueryLog();
        $this->assertCount(0, $queries->filter(fn (string $query): bool => str_contains($query, 'learning_progress')));
        $this->get(route('admin.analytics.index'), ['X-React-Page' => 'true', 'X-React-Component' => 'Admin/Analytics', 'X-React-Only' => 'statistics'])
            ->assertOk()->assertJsonPath('props.statistics.metrics.users', 1)->assertJsonMissingPath('props.materials');
        $this->assertGreaterThan(0, collect(DB::getQueryLog())->filter(fn (array $query): bool => str_contains($query['query'], 'learning_progress'))->count());
        DB::disableQueryLog();
    }
}
