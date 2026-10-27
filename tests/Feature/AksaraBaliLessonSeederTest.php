<?php

namespace Tests\Feature;

use App\Models\LearningPath;
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

    public function test_seeder_creates_five_available_modules_with_material_and_exercises_without_duplicates(): void
    {
        $this->seed(AksaraBaliLessonSeeder::class);
        $originalLessons = Lesson::query()->get()->toArray();

        $this->seed(AksaraBaliLessonSeeder::class);

        $this->assertDatabaseCount('learning_paths', 5);
        $this->assertDatabaseCount('lessons', 5);
        $this->assertSame($originalLessons, Lesson::query()->get()->toArray());
        $this->assertDatabaseCount('quizzes', 0);

        foreach (LearningPath::query()->get() as $path) {
            $this->assertSame('aksara', $path->group);
            $this->assertCount(1, $path->lessons);
            $lesson = $path->lessons->first();
            $this->assertNotEmpty($lesson->content);
            $this->assertNotEmpty($lesson->exercise_prompt);
            $this->assertCount(4, $lesson->exercise_options);
            $this->assertArrayHasKey($lesson->correct_option, $lesson->exercise_options);
            $this->assertNotEmpty($lesson->exercise_explanation);
        }

        $vowels = Lesson::query()->where('slug', 'pengangge-suara-dasar-andap')->firstOrFail();
        $this->assertStringContainsString('ᬳᬾᬤᬦ᭄', $vowels->content);
        $this->assertStringContainsString('ᬢᭀᬓᭀ', $vowels->content);
        $this->assertStringContainsString('ᬳᭂᬜᭂᬃ', $vowels->content);
        $numbers = Lesson::query()->where('slug', 'angka-aksara-bali-dasar-andap')->firstOrFail();
        $this->assertStringContainsString('0 → ᭐', $numbers->content);
        $this->assertSame('᭞᭓᭐᭞', $numbers->exercise_options[$numbers->correct_option]);
    }

    public function test_seeder_preserves_existing_editorial_changes_and_visibility(): void
    {
        $path = LearningPath::factory()->create([
            'slug' => 'pengangge-suara', 'title' => 'Judul dari admin', 'is_published' => false,
        ]);
        $lesson = Lesson::factory()->for($path)->create([
            'slug' => 'pengangge-suara-dasar-andap', 'content' => 'Materi hasil suntingan admin',
            'is_published' => false, 'reviewed_at' => null,
        ]);
        $originalAttributes = $lesson->refresh()->getAttributes();

        $this->seed(AksaraBaliLessonSeeder::class);

        $this->assertSame($originalAttributes, $lesson->fresh()->getAttributes());
        $this->assertSame('Judul dari admin', $path->fresh()->title);
        $this->assertFalse($path->fresh()->is_published);
        $this->assertDatabaseCount('learning_paths', 5);
        $this->assertDatabaseCount('lessons', 5);
    }

    public function test_seeded_modules_show_material_and_grade_the_embedded_exercise(): void
    {
        $this->seed(AksaraBaliLessonSeeder::class);
        $user = User::factory()->create();
        $deviceId = (string) Str::uuid();
        $this->actingAs($user)->withCookie('artaswaha_device', $deviceId);

        foreach (Lesson::query()->get() as $lesson) {
            $this->get(route('learning.lessons.show', $lesson))->assertOk()
                ->assertInertia(fn (Assert $page): Assert => $page->component('Learning/Lesson')
                    ->where('lesson.content', $lesson->content)
                    ->where('lesson.exercise_prompt', $lesson->exercise_prompt)
                    ->has('lesson.exercise_options', 4)
                    ->missing('lesson.correct_option'));

            $this->post(route('learning.exercises.answer', $lesson), [
                'answer_index' => ($lesson->correct_option + 1) % 4,
            ])->assertRedirectToRoute('learning.lessons.show', $lesson);
            $this->assertDatabaseMissing('learning_progress', ['lesson_id' => $lesson->id]);

            $this->post(route('learning.exercises.answer', $lesson), [
                'answer_index' => $lesson->correct_option,
            ])->assertRedirectToRoute('learning.lessons.show', $lesson);
            $this->assertDatabaseHas('learning_progress', [
                'lesson_id' => $lesson->id, 'visitor_id' => $deviceId, 'user_id' => $user->id, 'points' => 10,
            ]);
        }

        $this->assertSame(5, LearningProgress::query()->whereNotNull('completed_at')->count());
    }
}
