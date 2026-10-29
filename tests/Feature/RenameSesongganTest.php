<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\LessonAttempt;
use App\Models\QuizQuestion;
use Database\Seeders\LearningPathSeeder;
use Database\Seeders\ParibasaLessonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenameSesongganTest extends TestCase
{
    use RefreshDatabase;

    public function test_correction_keeps_ids_urls_answers_and_progress_and_can_run_twice(): void
    {
        $path = LearningPath::factory()->create(['title' => 'Sesonggakan', 'slug' => 'sesonggakan']);
        $lesson = Lesson::factory()->for($path)->create([
            'title' => 'Materi Sesonggakan', 'slug' => 'sesonggakan-makna',
            'content' => 'Nama jalur Sesonggakan dipertahankan dari menu platform. Belajar sesonggakan.',
        ]);
        $progress = LearningProgress::factory()->for($lesson)->create();
        $attempt = LessonAttempt::factory()->for($lesson)->create([
            'questions' => [['prompt' => 'Sesonggakan', 'accepted_answers' => ['sesonggakan']]],
            'answers' => [['answer_text' => 'sesonggakan', 'is_correct' => false]],
            'completed_at' => now(),
        ]);
        $question = QuizQuestion::factory()->create(['prompt' => 'Makna SESONGGAKAN?', 'explanation' => 'Contoh Sesonggakan.']);
        $before = $attempt->fresh()->getAttributes();
        $progressBefore = $progress->fresh()->getAttributes();
        $questionBefore = $question->getAttributes();
        $migration = require database_path('migrations/2026_10_29_214459_rename_sesonggakan_display_text_to_sesonggan.php');
        $migration->up();
        $migration->up();
        $this->assertSame('Sesonggan', $path->fresh()->title);
        $this->assertSame('sesonggakan', $path->fresh()->slug);
        $this->assertSame('Materi Sesonggan', $lesson->fresh()->title);
        $this->assertSame('Belajar sesonggan.', $lesson->fresh()->content);
        $this->assertSame('sesonggakan-makna', $lesson->fresh()->slug);
        $this->assertSame($before, $attempt->fresh()->getAttributes());
        $this->assertSame($progressBefore, $progress->fresh()->getAttributes());
        $this->assertSame('Makna SESONGGAN?', $question->fresh()->prompt);
        $this->assertSame($questionBefore['options'], $question->fresh()->getAttributes()['options']);
        $this->assertSame($questionBefore['correct_option'], $question->fresh()->correct_option);
        $this->assertDatabaseCount('learning_paths', 1);
        $this->assertDatabaseCount('lessons', 1);
        $migration->down();
        $this->assertSame('Sesonggan', $path->fresh()->title);
    }

    public function test_reseeding_uses_legacy_path_and_module_ids_without_creating_duplicates(): void
    {
        $path = LearningPath::factory()->create(['title' => 'Sesonggan', 'slug' => 'sesonggakan']);
        $this->seed(ParibasaLessonSeeder::class);
        $ids = $path->lessons()->pluck('id')->all();
        $this->assertCount(6, $ids);
        $progress = LearningProgress::factory()->create(['lesson_id' => $ids[0]]);
        $this->seed(LearningPathSeeder::class);
        $this->seed(ParibasaLessonSeeder::class);
        $this->assertSame($ids, $path->lessons()->pluck('id')->all());
        $this->assertSame('Sesonggan', $path->fresh()->title);
        $this->assertDatabaseMissing('learning_paths', ['slug' => 'sesonggan']);
        $this->assertDatabaseCount('learning_paths', 8);
        $this->assertDatabaseCount('lessons', 18);
        $this->assertNotNull($progress->fresh());
    }

    public function test_fresh_seeding_uses_the_correct_name_and_canonical_slug(): void
    {
        $this->seed(LearningPathSeeder::class);
        $this->seed(ParibasaLessonSeeder::class);
        $this->assertDatabaseHas('learning_paths', ['slug' => 'sesonggan', 'title' => 'Sesonggan']);
        $this->assertDatabaseMissing('learning_paths', ['slug' => 'sesonggakan']);
        $this->assertDatabaseCount('learning_paths', 8);
        $this->assertDatabaseCount('lessons', 18);
    }
}
