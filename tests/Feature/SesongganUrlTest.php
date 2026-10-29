<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\LessonAttempt;
use App\Models\User;
use Database\Seeders\LearningPathSeeder;
use Database\Seeders\ParibasaLessonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SesongganUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_migration_keeps_progress_attempts_and_ids_and_reseeding_does_not_duplicate_modules(): void
    {
        $path = LearningPath::factory()->create(['slug' => 'sesonggakan', 'title' => 'Sesonggan']);
        $this->seed(ParibasaLessonSeeder::class);
        $lesson = $path->lessons()->firstOrFail();
        $progress = LearningProgress::factory()->for($lesson)->create();
        $attempt = LessonAttempt::factory()->for($lesson)->create();
        $before = $attempt->fresh()->getAttributes();
        $progressBefore = $progress->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_10_29_234310_rename_sesonggakan_slugs_to_sesonggan.php');
        $migration->up();
        $migration->up();
        $this->assertSame('sesonggan', $path->fresh()->slug);
        $this->assertStringStartsWith('sesonggan-', $lesson->fresh()->slug);
        $this->assertSame($before, $attempt->fresh()->getAttributes());
        $this->assertSame($progressBefore, $progress->fresh()->getAttributes());
        $this->seed(LearningPathSeeder::class);
        $this->seed(ParibasaLessonSeeder::class);
        $this->assertDatabaseCount('lessons', 18);
        $this->assertDatabaseCount('learning_paths', 8);
        $this->assertSame($lesson->id, $path->lessons()->firstOrFail()->id);
    }

    public function test_old_urls_redirect_to_canonical_urls_and_new_path_opens(): void
    {
        $user = User::factory()->create();
        $path = LearningPath::factory()->create(['slug' => 'sesonggan']);
        $this->actingAs($user)->get('/belajar/sesonggakan')->assertStatus(301)->assertRedirect(route('learning.paths.show', $path));
        $this->get('/belajar/sesonggan')->assertOk();
        $this->get('/pelajaran/sesonggakan-makna')->assertStatus(301)->assertRedirect(route('learning.lessons.show', 'sesonggan-makna'));
        foreach (['selesai', 'latihan', 'ulang'] as $action) {
            $this->post('/pelajaran/sesonggakan-makna/'.$action, ['answer_index' => 1])
                ->assertStatus(307)->assertRedirect('/pelajaran/sesonggan-makna/'.$action);
        }
    }

    public function test_slug_collision_rolls_back_without_merging_or_deleting_user_data(): void
    {
        $path = LearningPath::factory()->create(['slug' => 'sesonggakan']);
        $lesson = Lesson::factory()->for($path)->create(['slug' => 'sesonggakan-makna']);
        Lesson::factory()->create(['slug' => 'sesonggan-makna']);
        $migration = require database_path('migrations/2026_10_29_234310_rename_sesonggakan_slugs_to_sesonggan.php');
        try {
            $migration->up();
            $this->fail('Expected a collision to stop the migration.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('sudah dipakai', $exception->getMessage());
        }
        $this->assertSame('sesonggakan', $path->fresh()->slug);
        $this->assertSame('sesonggakan-makna', $lesson->fresh()->slug);
        $this->assertDatabaseCount('lessons', 2);
    }
}
