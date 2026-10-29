<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\User;
use App\Services\QuestionAnswerService;
use Database\Seeders\ParibasaLessonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\AssertablePage as Assert;
use Tests\TestCase;

class ParibasaLessonSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_six_modules_per_path_with_valid_mixed_questions_and_sources(): void
    {
        $this->seed(ParibasaLessonSeeder::class);
        $this->assertDatabaseCount('learning_paths', 3);
        $this->assertDatabaseCount('lessons', 18);
        $this->assertDatabaseCount('quizzes', 0);
        foreach (LearningPath::all() as $path) {
            $this->assertSame('paribasa', $path->group);
            $this->assertSame(range(1, 6), $path->lessons->pluck('sort_order')->all());
        }
        $quizOnly = 0;
        foreach (Lesson::all() as $lesson) {
            $this->assertTrue($lesson->is_published);
            $this->assertNotNull($lesson->reviewed_at);
            $this->assertStringContainsString('## Sumber belajar', $lesson->content);
            $this->assertStringContainsString('https://', $lesson->content);
            $this->assertCount(10, $lesson->exercises);
            $this->assertEqualsCanonicalizing(['single', 'multiple', 'essay'], array_values(array_unique(array_column($lesson->exercises, 'question_type'))));
            $data = ['exercises' => $lesson->exercises];
            $validator = Validator::make($data, app(QuestionAnswerService::class)->lessonExerciseRules($data));
            $this->assertFalse($validator->fails(), json_encode($validator->errors()->toArray()));
            $this->assertArrayNotHasKey('exercises', $lesson->toArray());
            $this->assertFalse(collect($lesson->exercises)->contains('keyboard_auto_open', true));
            $quizOnly += str_starts_with($lesson->content, '## Sumber belajar') ? 1 : 0;
        }
        $this->assertSame(8, $quizOnly);
        $this->assertSame(180, Lesson::all()->sum(fn (Lesson $lesson): int => count($lesson->exercises)));
    }

    public function test_reseeding_preserves_editorial_changes_and_progress_without_duplicates(): void
    {
        $this->seed(ParibasaLessonSeeder::class);
        $lesson = Lesson::firstOrFail();
        $lesson->update(['content' => 'Suntingan pengelola', 'is_published' => false, 'reviewed_at' => null]);
        $path = $lesson->learningPath;
        $path->update(['title' => 'Nama suntingan', 'is_published' => false]);
        $progress = LearningProgress::factory()->for($lesson)->create();
        $before = Lesson::all()->map->getAttributes()->all();
        $this->seed(ParibasaLessonSeeder::class);
        $this->assertSame($before, Lesson::all()->map->getAttributes()->all());
        $this->assertSame('Nama suntingan', $path->fresh()->title);
        $this->assertFalse($path->fresh()->is_published);
        $this->assertNotNull($progress->fresh());
        $this->assertDatabaseCount('lessons', 18);
    }

    public function test_all_modules_can_be_answered_in_order_without_leaking_keys_or_skipping_completion(): void
    {
        $this->seed(ParibasaLessonSeeder::class);
        $this->actingAs(User::factory()->create())->withCookie('artaswaha_device', (string) Str::uuid());
        foreach (Lesson::orderBy('id')->get() as $lesson) {
            $this->get(route('learning.lessons.show', $lesson))->assertOk()->assertReact(fn (Assert $page): Assert => $page
                ->where('quiz.index', 0)->where('quiz.total', 10)->missing('quiz.question.correct_option')->missing('lesson.exercises'));
            $this->post(route('learning.progress.store', $lesson))->assertForbidden();
            foreach ($lesson->exercises as $index => $exercise) {
                $answer = match ($exercise['question_type']) {
                    'essay' => ['answer_text' => $exercise['accepted_answers'][0]],
                    'multiple' => ['answer_indexes' => $exercise['correct_options']],
                    default => ['answer_index' => $exercise['correct_option']],
                };
                $this->travel(5)->seconds();
                $this->post(route('learning.exercises.answer', $lesson), ['question_index' => $index, 'answer_index' => '', 'answer_indexes' => [], 'answer_text' => '', ...$answer])
                    ->assertSessionHasNoErrors()->assertSessionMissing('lesson_feedback')->assertRedirect();
                if ($index < 9) {
                    $this->assertDatabaseMissing('learning_progress', ['lesson_id' => $lesson->id]);
                }
            }
            $this->get(route('learning.lessons.show', $lesson))->assertReact(fn (Assert $page): Assert => $page->where('progress.points', 10)->where('quiz.question', null));
        }
        $this->assertDatabaseCount('learning_progress', 18);
    }
}
