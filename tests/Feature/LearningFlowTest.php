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
use Tests\TestCase;

class LearningFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_lists_learning_features_and_footer_links(): void
    {
        $this->seed(LearningPathSeeder::class);

        $response = $this->get(route('home'))
            ->assertOk()
            ->assertSee('ARTASWAHA - Platform Edukasi Bahasa, Sastra, dan Aksara Bali.')
            ->assertSee('Cecimpedan')
            ->assertSee('Belajar Aksara Bali')
            ->assertSee('Om Swastyastu')
            ->assertSee('ᬒᬁ ᬲ᭄ᬯᬲ᭄ᬢ᭄ᬬᬲ᭄ᬢᬸ᭟')
            ->assertSee(asset('image/asset1.webp'))
            ->assertSee('absolute right-0 top-12', false)
            ->assertSee('max-[380px]:scale-[1.0]', false)
            ->assertSee('max-[380px]:translate-x-8', false)
            ->assertSee('sm:absolute sm:right-0 sm:top-0', false)
            ->assertSee('lg:grid-cols-[1.05fr_.95fr]', false)
            ->assertSee('md:h-[20rem]', false)
            ->assertDontSee('Bawak-bawak madaging banyu')
            ->assertSee('Pengangge Suara')
            ->assertSee('Kenali paribasa dan aksara Bali lewat cerita')
            ->assertDontSee('Kembali ke atas')
            ->assertSee('group-hover:visible')
            ->assertSee('group-has-[:focus-visible]:visible')
            ->assertSee('aria-controls="mobile-navigation"', false)
            ->assertSee('data-mobile-nav', false)
            ->assertDontSee('group-focus-within:visible')
            ->assertSee('Tentang ARTASWAHA')
            ->assertSee('Keunggulan ARTASWAHA')
            ->assertSee('Belajar Bali lewat tantangan kecil yang seru')
            ->assertSee('Mulai petualanganmu')
            ->assertSee('Jelajahi paribasa dan aksara')
            ->assertSee('Ajak teman ke arena kuis')
            ->assertSee('Apa kata mereka')
            ->assertSee('data-testimonial-carousel', false)
            ->assertSee('data-testimonial-track', false)
            ->assertSee('data-testimonial-controls', false)
            ->assertSee('snap-x snap-mandatory')
            ->assertSee('aria-label="Testimoni sebelumnya"', false)
            ->assertSee('aria-label="Testimoni berikutnya"', false)
            ->assertSee('Ilustrasi · contoh suara')
            ->assertSee('h-[22rem]', false)
            ->assertSee('line-clamp-5', false)
            ->assertSee(asset('avatar/kupu_kupu.webp'))
            ->assertSee(asset('avatar/gajah.webp'))
            ->assertSee(asset('avatar/jalak_bali.webp'))
            ->assertSee('Yang sering ditanyakan')
            ->assertSee('bukan testimoni pengguna nyata')
            ->assertSee(route('learning.paths.show', 'cecimpedan'));

        $html = $response->getContent();
        preg_match_all('/<blockquote[^>]*>(.*?)<\/blockquote>/s', $html, $reviews);
        $this->assertCount(3, $reviews[1]);

        foreach ($reviews[1] as $review) {
            $this->assertLessThanOrEqual(120, mb_strlen(html_entity_decode(strip_tags($review))));
        }

        $sections = ['tentang', 'keunggulan', 'testimoni', 'faq'];
        $positions = array_map(fn (string $section) => strpos($html, 'id="'.$section.'"'), $sections);

        $this->assertNotContains(false, $positions);
        $sortedPositions = $positions;
        sort($sortedPositions);
        $this->assertSame($sortedPositions, $positions);
    }

    public function test_homepage_learning_picker_lists_every_published_learning_path_by_group(): void
    {
        $this->seed(LearningPathSeeder::class);

        $response = $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-learning-picker-open', false)
            ->assertSee('id="learning-picker"', false)
            ->assertSee('Paribasa Bali')
            ->assertSee('Aksara Bali');

        foreach (LearningPath::query()->where('is_published', true)->get() as $learningPath) {
            $response->assertSee($learningPath->title)
                ->assertSee(route('learning.paths.show', $learningPath->slug));
        }
    }

    public function test_mobile_dashboard_is_only_rendered_for_a_signed_in_user_or_completed_anonymous_profile(): void
    {
        $path = LearningPath::factory()->create(['title' => 'Cecimpedan', 'is_published' => true]);
        $lesson = Lesson::factory()->for($path, 'learningPath')->create();
        $user = User::factory()->create(['name' => 'Komang']);
        LearningProgress::factory()->for($user)->for($lesson)->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('id="mobile-featured-title"', false)
            ->assertSee('Bahasa Bali jadi');

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('id="mobile-featured-title"', false)
            ->assertSee('Riwayat belajar terakhir')
            ->assertSee('Pelajari kembali')
            ->assertSee('hidden md:block', false)
            ->assertSee('Bahasa Bali jadi');

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
            ->assertSee('id="mobile-featured-title"', false)
            ->assertSee('1 dari 1 pelajaran')
            ->assertSee('hidden md:block', false)
            ->assertSee('Bahasa Bali jadi');

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
            ->assertSee('id="mobile-featured-title"', false)
            ->assertSee('Akun anonim · di perangkat ini')
            ->assertSee('Made')
            ->assertSee('Belum ada riwayat belajar');

        $this->withCookie('artaswaha_device', (string) Str::uuid())->get(route('home'))
            ->assertOk()
            ->assertDontSee('id="mobile-featured-title"', false);
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
            ->assertViewHas('lastLearningProgress', fn (LearningProgress $progress): bool => $progress->is($latest))
            ->assertSee(route('learning.lessons.show', $latest->lesson->slug))
            ->assertSee('Pelajari kembali');
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
            ->assertViewHas('lastLearningProgress', fn (LearningProgress $progress): bool => $progress->is($latest))
            ->assertSee(route('learning.lessons.show', $latest->lesson->slug));
    }

    public function test_mobile_app_navigation_stays_on_the_main_pages_but_not_during_a_lesson(): void
    {
        $path = LearningPath::factory()->create();
        $lesson = Lesson::factory()->for($path, 'learningPath')->create();
        $user = User::factory()->create();

        foreach (['home', 'learning.paths.show', 'quizzes.index', 'profile.show'] as $routeName) {
            $url = $routeName === 'learning.paths.show' ? route($routeName, $path->slug) : route($routeName);
            $this->actingAs($user)->get($url)->assertOk()->assertSee('data-mobile-app-nav', false);
        }

        $this->actingAs($user)->get(route('learning.lessons.show', $lesson->slug))
            ->assertOk()
            ->assertDontSee('data-mobile-app-nav', false);
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
        preg_match('/<nav data-mobile-app-nav.*?<\/nav>/s', $response->getContent(), $navigation);
        $this->assertNotEmpty($navigation);
        $this->assertStringContainsString('href="'.route('learning.paths.index').'"', $navigation[0]);
        $this->assertStringNotContainsString('href="'.route('learning.interview.show').'"', $navigation[0]);

        foreach (['quizzes.index', 'profile.show'] as $routeName) {
            $this->withCookie('artaswaha_device', $profile->visitor_id)
                ->get(route($routeName))
                ->assertOk()
                ->assertSee('data-mobile-app-nav', false);
        }

        $publishedPath->update(['is_published' => false]);
        $response = $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('home'))->assertOk();
        preg_match('/<nav data-mobile-app-nav.*?<\/nav>/s', $response->getContent(), $navigation);
        $this->assertStringContainsString('href="'.route('learning.paths.index').'"', $navigation[0]);
    }

    public function test_learning_catalog_lists_published_materials_and_lesson_counts(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true, 'group' => 'aksara']);
        Lesson::factory()->create(['learning_path_id' => $path->id]);
        $hiddenPath = LearningPath::factory()->create(['is_published' => false]);

        $this->actingAs(User::factory()->create())->get(route('learning.paths.index'))
            ->assertOk()
            ->assertSee('Mau belajar apa hari ini?')
            ->assertSee($path->title)
            ->assertSee('1 pelajaran')
            ->assertSee(route('learning.paths.show', $path->slug))
            ->assertDontSee($hiddenPath->title)
            ->assertSee('data-mobile-app-nav', false);
    }

    public function test_learning_catalog_is_accessible_without_an_account_and_handles_no_materials(): void
    {
        $this->get(route('learning.paths.index'))
            ->assertOk()
            ->assertSee('Materi belajar sedang disiapkan.')
            ->assertDontSee('data-mobile-app-nav', false);
    }

    public function test_unreviewed_lessons_are_not_accessible(): void
    {
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
            ->assertSee('aria-label="Menu profil Gung Satria"', false)
            ->assertSee('Profil')
            ->assertSee(route('profile.show'))
            ->assertSee('Keluar')
            ->assertDontSee('>Daftar</a>', false);

        $this->assertStringContainsString('src="'.asset($user->avatar).'"', $response->getContent());
        $this->assertStringNotContainsString('>G</button>', $response->getContent());
    }

    public function test_profile_page_requires_authentication_and_shows_account_details(): void
    {
        $this->get(route('profile.show'))->assertRedirect(route('login'));

        $this->seed(LearningPathSeeder::class);
        $user = User::factory()->create(['name' => 'Gung Satria', 'email' => 'gung@example.com']);
        $path = LearningPath::query()->where('slug', 'cecimpedan')->firstOrFail();
        $lesson = Lesson::factory()->create(['learning_path_id' => $path->id]);
        LearningProgress::factory()->for($user)->for($lesson)->create();
        Quiz::factory()->for($user)->count(2)->create();

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Progres belajar')
            ->assertSee('Gung Satria')
            ->assertSee('gung@example.com')
            ->assertSee('Tentang aplikasi ini')
            ->assertSee(route('profile.about'))
            ->assertSee('Ringkasan belajar')
            ->assertSee('Paribasa Bali')
            ->assertSee('Aksara Bali')
            ->assertSee('1 dari 1 pelajaran selesai')
            ->assertSee('aria-label="Progres Cecimpedan: 1 dari 1 pelajaran"', false)
            ->assertSee(route('learning.paths.show', 'cecimpedan'))
            ->assertSee(route('quizzes.index'))
            ->assertSee('min-h-[calc(100svh-6rem)]', false)
            ->assertDontSee('Ruang belajar paribasa dan aksara Bali yang seru');
    }

    public function test_profile_about_page_explains_the_system_version_and_privacy(): void
    {
        $this->get(route('profile.about'))->assertRedirect(route('login'));

        $this->seed(LearningPathSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.about'))
            ->assertOk()
            ->assertSee('Tentang ARTASWAHA')
            ->assertSee('Platform Edukasi Bahasa, Sastra, dan Aksara Bali')
            ->assertSee('Versi 0.1.0')
            ->assertSee('Data dan privasi')
            ->assertSee('Baca kebijakan privasi')
            ->assertSee(route('privacy-policy'))
            ->assertSee(route('profile.show'));
    }

    public function test_profile_shows_a_clear_empty_state_when_no_lessons_exist(): void
    {
        $this->seed(LearningPathSeeder::class);

        $this->actingAs(User::factory()->create())
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Belum ada progres belajar')
            ->assertSee('Pilih materi')
            ->assertDontSee('Segera hadir')
            ->assertDontSee('0 dari 0 pelajaran selesai');
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
            ->assertViewHas('totalLessons', 2)
            ->assertViewHas('completedLessons', 1)
            ->assertViewHas('learningPathsByGroup', fn ($groups): bool => $groups->flatten()->pluck('id')->all() === [$studiedPath->id])
            ->assertSee('1 dari 2 pelajaran selesai')
            ->assertSee('50%')
            ->assertDontSee('Segera hadir');
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
            ->assertViewHas('totalLessons', 1)
            ->assertViewHas('completedLessons', 1)
            ->assertViewHas('learningPathsByGroup', fn ($groups): bool => $groups->flatten()->pluck('id')->all() === [$path->id]);
    }

    public function test_path_page_only_lists_published_reviewed_lessons(): void
    {
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
            ->assertSee('Pilih pelajaran')
            ->assertSee('1 pelajaran')
            ->assertSee('md:grid-cols-2', false)
            ->assertSee('Pelajaran yang ditinjau')
            ->assertSee(route('learning.lessons.show', 'pelajaran-yang-ditinjau'))
            ->assertDontSee('Draf internal');
    }

    public function test_empty_learning_path_offers_another_topic(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);

        $this->get(route('learning.paths.show', $path->slug))
            ->assertOk()
            ->assertSee('Pelajaran sedang disiapkan')
            ->assertSee('Lihat materi lain')
            ->assertSee(route('home'), false);
    }

    public function test_anonymous_progress_is_saved_once_for_the_device(): void
    {
        $lesson = Lesson::factory()->create();
        $visitorId = (string) Str::uuid();

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
