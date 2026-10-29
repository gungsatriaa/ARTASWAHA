<?php

namespace Tests\Feature;

use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\User;
use Database\Seeders\LearningPathSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\AssertablePage;
use Tests\TestCase;

class LearningFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_visitors_receive_the_loading_root_on_the_interview_page(): void
    {
        $this->get(route('home'))->assertRedirectToRoute('learning.interview.show');
        $this->get(route('learning.interview.show'))->assertOk()->assertSee('<div data-react-loader', false);
        $this->get(route('login'))->assertOk();
    }

    public function test_homepage_lists_learning_features_and_footer_links(): void
    {
        $this->actingAs(User::factory()->create());
        $this->seed(LearningPathSeeder::class);

        $response = $this->get(route('home'))
            ->assertOk()
            ->assertReactContains('Cecimpedan')
            ->assertReactContains(asset('image/asset1.webp'))
            ->assertReactNotContains('Bawak-bawak madaging banyu')
            ->assertReactContains('Pengangge Suara')
            ->assertReactNotContains('Kembali ke atas')
            ->assertReactNotContains('group-focus-within:visible')
            ->assertReactContains(route('learning.paths.show', 'cecimpedan'));

        $response->assertReactValue('navigation.privacy', route('privacy-policy'));

    }

    public function test_homepage_learning_picker_lists_every_published_learning_path_by_group(): void
    {
        $this->actingAs(User::factory()->create());
        $this->seed(LearningPathSeeder::class);

        $response = $this->get(route('home'))
            ->assertOk()
            ->assertReact(fn (AssertablePage $page) => $page->etc());

        foreach (LearningPath::query()->where('is_published', true)->get() as $learningPath) {
            $response->assertReactContains($learningPath->title)
                ->assertReactContains(route('learning.paths.show', $learningPath->slug));
        }
    }

    public function test_mobile_dashboard_is_only_rendered_for_a_signed_in_user_or_completed_anonymous_profile(): void
    {
        $path = LearningPath::factory()->create(['title' => 'Cecimpedan', 'is_published' => true]);
        $lesson = Lesson::factory()->for($path, 'learningPath')->create();
        $user = User::factory()->create(['name' => 'Komang']);
        LearningProgress::factory()->for($user)->for($lesson)->create();

        $this->get(route('home'))->assertRedirectToRoute('learning.interview.show');

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertReact(fn (AssertablePage $page) => $page->etc());

        LearnerProfile::query()->create([
            'visitor_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'learning_path_id' => $path->id,
            'learning_path_ids' => [$path->id],
            'role' => 'murid',
            'name' => $user->name,
            'mode' => 'account',
        ]);

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertReact(fn (AssertablePage $page) => $page->etc());

        $this->post(route('logout'));
        $profile = LearnerProfile::query()->create([
            'visitor_id' => (string) Str::uuid(),
            'learning_path_id' => $path->id,
            'learning_path_ids' => [$path->id],
            'role' => 'murid',
            'name' => 'Made',
            'mode' => 'anonymous',
        ]);

        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('home'))
            ->assertOk()
            ->assertReactContains('Made')
            ->assertReact(fn (AssertablePage $page) => $page->etc());

        $this->withCookie('artaswaha_device', (string) Str::uuid())->get(route('home'))
            ->assertRedirectToRoute('learning.interview.show');
    }

    public function test_dashboard_shows_the_users_latest_available_completed_lesson(): void
    {
        $user = User::factory()->create();
        LearningProgress::factory()->for($user)->create(['completed_at' => now()->subDays(2)]);
        $latest = LearningProgress::factory()->for($user)->create(['completed_at' => now()->subDay()]);
        LearningProgress::factory()->for(User::factory())->create();
        LearningProgress::factory()->for($user)->for(Lesson::factory()->create(['is_published' => false]))->create();

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertReactValue('recent.url', route('learning.lessons.show', $latest->lesson->slug))
            ->assertReactContains(route('learning.lessons.show', $latest->lesson->slug))
            ->assertReact(fn (AssertablePage $page) => $page->etc());
    }

    public function test_anonymous_learning_history_only_uses_unclaimed_progress_from_its_device(): void
    {
        $path = LearningPath::factory()->create();
        $profile = LearnerProfile::query()->create([
            'visitor_id' => (string) Str::uuid(),
            'learning_path_id' => $path->id,
            'role' => 'murid',
            'name' => 'Made',
            'mode' => 'anonymous',
        ]);
        $latest = LearningProgress::factory()->create(['visitor_id' => $profile->visitor_id, 'completed_at' => now()->subDay()]);
        LearningProgress::factory()->create();
        LearningProgress::factory()->for(User::factory())->create(['visitor_id' => $profile->visitor_id]);

        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('home'))
            ->assertOk()
            ->assertReactValue('recent.url', route('learning.lessons.show', $latest->lesson->slug))
            ->assertReactContains(route('learning.lessons.show', $latest->lesson->slug));
    }

    public function test_mobile_app_navigation_stays_on_the_main_pages_but_not_during_a_lesson(): void
    {
        $path = LearningPath::factory()->create();
        $lesson = Lesson::factory()->for($path, 'learningPath')->create();
        $user = User::factory()->create();

        foreach (['home', 'learning.paths.show', 'quizzes.index', 'profile.show'] as $routeName) {
            $url = $routeName === 'learning.paths.show' ? route($routeName, $path->slug) : route($routeName);
            $this->actingAs($user)->get($url)->assertOk()->assertReact(fn (AssertablePage $page) => $page->etc());
        }

        $this->actingAs($user)->get(route('learning.lessons.show', $lesson->slug))
            ->assertOk();
    }

    public function test_mobile_learning_tab_opens_catalog_when_anonymous_selected_material_is_unpublished(): void
    {
        $unpublishedPath = LearningPath::factory()->create(['is_published' => false]);
        $publishedPath = LearningPath::factory()->create(['is_published' => true]);
        $profile = LearnerProfile::query()->create([
            'visitor_id' => (string) Str::uuid(),
            'learning_path_id' => $unpublishedPath->id,
            'learning_path_ids' => [$unpublishedPath->id],
            'role' => 'murid',
            'name' => 'Made',
            'mode' => 'anonymous',
        ]);

        $response = $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('home'))->assertOk();
        $response->assertReactValue('navigation.learning', route('learning.paths.index'))
            ->assertReactValue('viewer.mode', 'anonymous');

        foreach (['quizzes.index', 'profile.show'] as $routeName) {
            $this->withCookie('artaswaha_device', $profile->visitor_id)
                ->get(route($routeName))
                ->assertOk()
                ->assertReact(fn (AssertablePage $page) => $page->etc());
        }

        $publishedPath->update(['is_published' => false]);
        $response = $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('home'))->assertOk();
        $response->assertReactValue('navigation.learning', route('learning.paths.index'));
    }

    public function test_learning_catalog_lists_published_materials_and_lesson_counts(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true, 'group' => 'aksara']);
        Lesson::factory()->create(['learning_path_id' => $path->id]);
        $hiddenPath = LearningPath::factory()->create(['is_published' => false]);

        $this->actingAs(User::factory()->create())->get(route('learning.paths.index'))
            ->assertOk()
            ->assertReactContains($path->title)
            ->assertReactContains(route('learning.paths.show', $path->slug))
            ->assertReactNotContains($hiddenPath->title)
            ->assertReact(fn (AssertablePage $page) => $page->etc());
    }

    public function test_learning_catalog_handles_no_materials_for_an_existing_account(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('learning.paths.index'))
            ->assertOk()
            ->assertReact(fn (AssertablePage $page) => $page->etc());
    }

    public function test_unreviewed_lessons_are_not_accessible(): void
    {
        $this->actingAs(User::factory()->create());
        $lesson = Lesson::factory()->create(['reviewed_at' => null]);

        $this->get(route('learning.lessons.show', $lesson->slug))->assertNotFound();
    }

    public function test_authenticated_navigation_uses_a_circular_profile_menu(): void
    {
        $this->seed(LearningPathSeeder::class);
        $user = User::factory()->create(['name' => 'Gung Satria']);

        $response = $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertReactContains(route('profile.show'))
            ->assertReact(fn (AssertablePage $page) => $page->etc());

        $response->assertReactValue('viewer.avatar', asset($user->avatar));
        $this->assertStringNotContainsString('>G</button>', $response->getContent());
    }

    public function test_profile_page_requires_authentication_and_shows_account_details(): void
    {
        $this->get(route('profile.show'))->assertRedirect(route('learning.interview.show'));

        $this->seed(LearningPathSeeder::class);
        $user = User::factory()->create(['name' => 'Gung Satria', 'email' => 'gung@example.com']);
        $path = LearningPath::query()->where('slug', 'cecimpedan')->firstOrFail();
        $lesson = Lesson::factory()->create(['learning_path_id' => $path->id]);
        LearningProgress::factory()->for($user)->for($lesson)->create();
        Quiz::factory()->for($user)->count(2)->create();

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertReactContains('Gung Satria')
            ->assertReactContains('gung@example.com')
            ->assertReactContains(route('profile.about'))
            ->assertReactContains(route('learning.paths.show', 'cecimpedan'))
            ->assertReactContains(route('quizzes.index'))
            ->assertReactNotContains('Ruang belajar paribasa dan aksara Bali yang seru');
    }

    public function test_profile_about_page_explains_the_system_version_and_privacy(): void
    {
        $this->get(route('profile.about'))->assertRedirect(route('learning.interview.show'));

        $this->seed(LearningPathSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.about'))
            ->assertOk()
            ->assertReactContains(route('privacy-policy'))
            ->assertReactContains(route('profile.show'));
    }

    public function test_profile_shows_a_clear_empty_state_when_no_lessons_exist(): void
    {
        $this->seed(LearningPathSeeder::class);

        $this->actingAs(User::factory()->create())
            ->get(route('profile.show'))
            ->assertOk()
            ->assertReactNotContains('Segera hadir')
            ->assertReactNotContains('0 dari 0 pelajaran selesai');
    }

    public function test_profile_progress_only_includes_materials_studied_by_the_current_user(): void
    {
        $user = User::factory()->create();
        $studiedPath = LearningPath::factory()->create();
        $studiedLesson = Lesson::factory()->for($studiedPath, 'learningPath')->create();
        Lesson::factory()->for($studiedPath, 'learningPath')->create();
        LearningProgress::factory()->for($user)->for($studiedLesson)->create();
        $otherPath = LearningPath::factory()->create();
        $otherLesson = Lesson::factory()->for($otherPath, 'learningPath')->create();
        LearningProgress::factory()->for(User::factory())->for($otherLesson)->create();

        $this->actingAs($user)->get(route('profile.show'))
            ->assertOk()
            ->assertReactValue('totalLessons', 2)
            ->assertReactValue('completedLessons', 1)
            ->assertReactValue('paths.0.id', $studiedPath->id)
            ->assertReactNotContains('Segera hadir');
    }

    public function test_anonymous_profile_progress_excludes_other_devices_and_account_progress(): void
    {
        $path = LearningPath::factory()->create();
        $profile = LearnerProfile::query()->create([
            'visitor_id' => (string) Str::uuid(),
            'learning_path_id' => $path->id,
            'role' => 'murid',
            'name' => 'Made',
            'mode' => 'anonymous',
        ]);
        $lesson = Lesson::factory()->for($path, 'learningPath')->create();
        LearningProgress::factory()->for($lesson)->create(['visitor_id' => $profile->visitor_id]);
        LearningProgress::factory()->create();
        LearningProgress::factory()->for(User::factory())->create(['visitor_id' => $profile->visitor_id]);

        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('profile.show'))
            ->assertOk()
            ->assertReactValue('totalLessons', 1)
            ->assertReactValue('completedLessons', 1)
            ->assertReactValue('paths.0.id', $path->id);
    }

    public function test_path_page_only_lists_published_reviewed_lessons(): void
    {
        $this->actingAs(User::factory()->create());
        $this->seed(LearningPathSeeder::class);
        $path = LearningPath::query()->where('slug', 'cecimpedan')->firstOrFail();
        Lesson::factory()->create([
            'learning_path_id' => $path->id,
            'title' => 'Pelajaran yang ditinjau',
            'slug' => 'pelajaran-yang-ditinjau',
        ]);
        Lesson::factory()->create([
            'learning_path_id' => $path->id,
            'title' => 'Draf internal',
            'is_published' => false,
        ]);

        $this->get(route('learning.paths.show', $path->slug))
            ->assertOk()
            ->assertReactContains('Pelajaran yang ditinjau')
            ->assertReactContains(route('learning.lessons.show', 'pelajaran-yang-ditinjau'))
            ->assertReactNotContains('Draf internal');
    }

    public function test_empty_learning_path_offers_another_topic(): void
    {
        $this->actingAs(User::factory()->create());
        $path = LearningPath::factory()->create(['is_published' => true]);

        $this->get(route('learning.paths.show', $path->slug))
            ->assertOk()
            ->assertReactContains(route('home'));
    }

    public function test_anonymous_progress_is_saved_once_for_the_device(): void
    {
        $lesson = Lesson::factory()->create();
        $visitorId = (string) Str::uuid();
        LearnerProfile::create(['visitor_id' => $visitorId, 'learning_path_id' => $lesson->learning_path_id, 'role' => 'murid', 'mode' => 'anonymous', 'name' => 'Made']);

        $this->withCookie('artaswaha_device', $visitorId)
            ->post(route('learning.progress.store', $lesson->slug), ['points' => 9000])
            ->assertRedirect(route('learning.lessons.show', $lesson->slug));

        $this->withCookie('artaswaha_device', $visitorId)
            ->post(route('learning.progress.store', $lesson->slug))
            ->assertRedirect(route('learning.lessons.show', $lesson->slug));

        $this->assertSame(1, LearningProgress::query()->count());
        $this->assertDatabaseHas('learning_progress', [
            'visitor_id' => $visitorId,
            'lesson_id' => $lesson->id,
            'points' => 10,
        ]);
    }
}
