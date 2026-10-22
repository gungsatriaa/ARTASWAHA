<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\LearningProgress;
use App\Models\Lesson;
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

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Cecimpedan')
            ->assertSee('Pengangge Suara')
            ->assertSee('Ruang belajar paribasa dan aksara Bali')
            ->assertSee('Kembali ke atas')
            ->assertSee('group-hover:visible')
            ->assertSee('group-has-[:focus-visible]:visible')
            ->assertDontSee('group-focus-within:visible')
            ->assertSee('Tentang ArtaSwaha')
            ->assertSee('Apa kata mereka')
            ->assertSee('Yang sering ditanyakan')
            ->assertSee('bukan testimoni pengguna nyata')
            ->assertSee(route('learning.paths.show', 'cecimpedan'));
    }

    public function test_unreviewed_lessons_are_not_accessible(): void
    {
        $lesson = Lesson::factory()->create(['reviewed_at' => null]);

        $this->get(route('learning.lessons.show', $lesson->slug))->assertNotFound();
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
