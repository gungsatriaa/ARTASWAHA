<?php

namespace Tests\Feature;

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
            ->assertSee('md:scale-[1.4]', false)
            ->assertDontSee('Bawak-bawak madaging banyu')
            ->assertSee('Pengangge Suara')
            ->assertSee('Ruang belajar paribasa dan aksara Bali')
            ->assertSee('Kembali ke atas')
            ->assertSee('group-hover:visible')
            ->assertSee('group-has-[:focus-visible]:visible')
            ->assertDontSee('group-focus-within:visible')
            ->assertSee('Tentang ARTASWAHA')
            ->assertSee('Keunggulan ARTASWAHA')
            ->assertSee('Mulai tanpa akun')
            ->assertSee('Materi singkat dan terarah')
            ->assertSee('Main kuis bersama')
            ->assertSee('Apa kata mereka')
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
            ->assertSee('lg:sticky lg:top-28 lg:self-start', false)
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
            ->assertSee('Ringkasan kebijakan privasi')
            ->assertSee('ID acak pada cookie perangkat')
            ->assertSee('belum ada penghapusan akun mandiri')
            ->assertSee(route('profile.show'));
    }

    public function test_profile_shows_a_clear_empty_state_when_no_lessons_exist(): void
    {
        $this->seed(LearningPathSeeder::class);

        $this->actingAs(User::factory()->create())
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Materi belajar sedang disiapkan')
            ->assertSee('Segera hadir')
            ->assertDontSee('0 dari 0 pelajaran selesai');
    }

    public function test_path_page_only_lists_published_reviewed_lessons(): void
    {
        $this->seed(LearningPathSeeder::class);
        $path = LearningPath::query()->where('slug', 'cecimpedan')->firstOrFail();
        Lesson::factory()->create([
            'learning_path_id' => $path->id,
            'title' => 'Pelajaran yang ditinjau',
        ]);
        Lesson::factory()->create([
            'learning_path_id' => $path->id,
            'title' => 'Draf internal',
            'is_published' => false,
        ]);

        $this->get(route('learning.paths.show', $path->slug))
            ->assertOk()
            ->assertSee('Pelajaran yang ditinjau')
            ->assertDontSee('Draf internal');
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
