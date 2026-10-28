<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use Database\Seeders\AksaraBaliLessonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LessonModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_edit_a_mixed_bank_without_changing_answer_types_or_publishing_automatically(): void
    {
        $this->seed(AksaraBaliLessonSeeder::class);
        $lesson = Lesson::firstOrFail();
        $data = $lesson->only(['learning_path_id', 'title', 'slug', 'summary', 'content', 'sort_order', 'exercises']);
        $data['has_exercise'] = false;
        $data['exercises'][0]['exercise_explanation'] = 'Penjelasan hasil suntingan admin.';
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->put(route('admin.lessons.update', $lesson), $data)->assertSessionHasNoErrors()->assertRedirect();
        $lesson->refresh();
        $this->assertFalse($lesson->is_published);
        $this->assertNull($lesson->reviewed_at);
        $this->assertCount(10, $lesson->exercises);
        $this->assertSame('Penjelasan hasil suntingan admin.', $lesson->exercises[0]['exercise_explanation']);
        $this->assertSame(3, collect($lesson->exercises)->where('question_type', 'essay')->count());
        $this->get(route('admin.lessons.preview', $lesson))->assertInertia(fn (Assert $page): Assert => $page->has('lesson.exercises', 10));
        $this->actingAs(User::factory()->create())->put(route('admin.lessons.update', $lesson), $data)->assertForbidden();
    }

    public function test_admin_bank_rejects_invalid_types_keys_answer_indices_and_empty_essays(): void
    {
        $path = LearningPath::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $data = ['learning_path_id' => $path->id, 'title' => 'Modul uji', 'slug' => 'modul-uji', 'summary' => 'Ringkasan', 'content' => '## Materi', 'sort_order' => 1];
        $bad = [
            ['section' => 0, 'question_type' => 'single', 'exercise_prompt' => 'Pilih', 'exercise_options' => ['Ha', 'Na'], 'correct_option' => 9],
            ['section' => 0, 'question_type' => 'essay', 'exercise_prompt' => 'Tulis', 'accepted_answers' => []],
            ['section' => 0, 'question_type' => 'invalid', 'exercise_prompt' => 'Pilih'],
            ['section' => 0, 'question_type' => 'essay', 'exercise_prompt' => 'Tulis', 'accepted_answers' => ['ᬳ'], 'injected' => true],
        ];
        foreach ($bad as $exercise) {
            $this->post(route('admin.lessons.store'), [...$data, 'exercises' => [$exercise]])->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('lessons', 0);
    }

    public function test_bank_changes_restart_partial_attempt(): void
    {
        $this->seed(AksaraBaliLessonSeeder::class);
        $lesson = Lesson::firstOrFail();
        $user = User::factory()->create();
        $this->actingAs($user)->withCookie('artaswaha_device', (string) Str::uuid());
        $this->post(route('learning.exercises.answer', $lesson), ['question_index' => 0, 'answer_index' => $lesson->exercises[0]['correct_option']]);
        $this->get(route('learning.lessons.show', $lesson))->assertInertia(fn (Assert $page): Assert => $page->where('quiz.index', 1));
        $exercises = $lesson->exercises;
        $exercises[0]['exercise_prompt'] = 'Pertanyaan yang telah diubah';
        $lesson->update(['exercises' => $exercises]);
        $this->get(route('learning.lessons.show', $lesson))->assertInertia(fn (Assert $page): Assert => $page->where('quiz.index', 0));
        $this->assertDatabaseCount('learning_progress', 0);
    }
}
