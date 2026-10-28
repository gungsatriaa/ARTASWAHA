<?php

namespace Tests\Feature;

use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\User;
use Database\Seeders\AksaraBaliLessonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AksaraBaliLessonSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_ten_available_modules_and_125_mixed_questions_without_duplicates(): void
    {
        $this->seed(AksaraBaliLessonSeeder::class);
        $original = Lesson::query()->get()->map->getAttributes()->all();
        $this->seed(AksaraBaliLessonSeeder::class);
        $this->assertSame($original, Lesson::query()->get()->map->getAttributes()->all());
        $this->assertDatabaseCount('learning_paths', 5);
        $this->assertDatabaseCount('lessons', 10);
        $this->assertDatabaseCount('quizzes', 0);
        $total = 0;
        foreach (Lesson::all() as $lesson) {
            $this->assertTrue($lesson->is_published);
            $this->assertNotNull($lesson->reviewed_at);
            $this->assertStringContainsString('## Sumber belajar', $lesson->content);
            $this->assertStringContainsString('repo.undiksha.ac.id', $lesson->content);
            $this->assertContains(count($lesson->exercises), [10, 15]);
            $this->assertEqualsCanonicalizing(['single', 'multiple', 'essay'], array_values(array_unique(array_column($lesson->exercises, 'question_type'))));
            $this->assertArrayNotHasKey('exercises', $lesson->toArray());
            foreach ($lesson->exercises as $exercise) {
                $this->assertNotEmpty($exercise['exercise_explanation']);
                $this->assertContains($exercise['section'], [0, 1, 2]);
                if ($exercise['question_type'] === 'essay') {
                    $this->assertTrue($exercise['keyboard_auto_open']);
                    $this->assertMatchesRegularExpression('/[\x{1B00}-\x{1B7F}]/u', $exercise['accepted_answers'][0]);
                    $this->assertStringNotContainsString('◌', $exercise['accepted_answers'][0]);
                }
            }
            $total += count($lesson->exercises);
        }
        $this->assertSame(125, $total);
    }

    public function test_seeder_preserves_editorial_changes_old_modules_and_existing_progress(): void
    {
        $this->seed(AksaraBaliLessonSeeder::class);
        $lesson = Lesson::firstOrFail();
        $lesson->update(['content' => 'Materi suntingan admin', 'is_published' => false, 'reviewed_at' => null]);
        $progress = LearningProgress::factory()->for($lesson)->create();
        $old = Lesson::factory()->create(['slug' => 'wreastra-gantungan-dasar-andap']);
        $original = $lesson->fresh()->getAttributes();
        $this->seed(AksaraBaliLessonSeeder::class);
        $this->assertSame($original, $lesson->fresh()->getAttributes());
        $this->assertNotNull($old->fresh());
        $this->assertNotNull($progress->fresh());
        $this->assertDatabaseCount('lessons', 11);
    }

    public function test_every_seeded_question_is_answerable_without_leaking_future_answers_or_granting_early_points(): void
    {
        $this->seed(AksaraBaliLessonSeeder::class);
        $this->actingAs(User::factory()->create())->withCookie('artaswaha_device', (string) Str::uuid());
        foreach (Lesson::all() as $lesson) {
            $this->get(route('learning.lessons.show', $lesson))->assertOk()
                ->assertInertia(fn (Assert $page): Assert => $page->component('Learning/Lesson')
                    ->where('quiz.total', count($lesson->exercises))->where('quiz.index', 0)
                    ->missing('quiz.question.correct_option')->missing('quiz.question.accepted_answers')->missing('lesson.exercises'));
            $this->post(route('learning.progress.store', $lesson))->assertForbidden();
            foreach ($lesson->exercises as $index => $exercise) {
                $this->assertDatabaseMissing('learning_progress', ['lesson_id' => $lesson->id]);
                $answer = match ($exercise['question_type']) {
                    'essay' => ['answer_text' => $exercise['accepted_answers'][0]],
                    'multiple' => ['answer_indexes' => $exercise['correct_options']],
                    default => ['answer_index' => $exercise['correct_option']],
                };
                $this->travel(5)->seconds();
                $this->post(route('learning.exercises.answer', $lesson), ['question_index' => $index, 'answer_index' => '', 'answer_indexes' => [], 'answer_text' => '', ...$answer])
                    ->assertRedirectToRoute('learning.lessons.show', $lesson);
            }
            $this->assertDatabaseHas('learning_progress', ['lesson_id' => $lesson->id, 'points' => 10]);
            $this->get(route('learning.lessons.show', $lesson))->assertInertia(fn (Assert $page): Assert => $page->where('quiz.question', null)->where('progress.points', 10));
        }
        $this->assertDatabaseCount('learning_progress', 10);
    }

    public function test_wrong_answer_retries_and_stale_or_skipped_question_is_rejected(): void
    {
        $this->seed(AksaraBaliLessonSeeder::class);
        $lesson = Lesson::firstOrFail();
        $this->actingAs(User::factory()->create())->withCookie('artaswaha_device', (string) Str::uuid());
        $answer = $lesson->exercises[0]['correct_option'];
        $this->post(route('learning.exercises.answer', $lesson), ['question_index' => 0, 'answer_index' => ($answer + 1) % 4])
            ->assertSessionHas('lesson_feedback.correct', false);
        $this->get(route('learning.lessons.show', $lesson))->assertInertia(fn (Assert $page): Assert => $page->where('quiz.index', 0));
        $this->post(route('learning.exercises.answer', $lesson), ['question_index' => 1, 'answer_index' => $answer])->assertConflict();
        $this->post(route('learning.exercises.answer', $lesson), ['question_index' => 0, 'answer_index' => $answer])->assertSessionHas('lesson_feedback.correct', true);
        $this->post(route('learning.exercises.answer', $lesson), ['question_index' => 0, 'answer_index' => $answer])->assertConflict();
        $this->assertDatabaseCount('learning_progress', 0);
    }
}
