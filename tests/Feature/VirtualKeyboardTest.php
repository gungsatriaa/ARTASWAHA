<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\RoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class VirtualKeyboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_aksara_and_change_automatic_keyboard_setting(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $path = LearningPath::factory()->create(['group' => 'aksara']);
        $data = [
            'learning_path_id' => $path->id, 'title' => 'Aksara Ka', 'slug' => 'aksara-ka',
            'summary' => 'Mengenal ka', 'content' => "\u{1B13}\u{1B44}\u{1B26}", 'sort_order' => 1, 'keyboard_auto_open' => '1',
        ];

        $this->actingAs($admin)->post(route('admin.lessons.store'), $data)->assertRedirect();

        $lesson = Lesson::query()->sole();
        $this->assertSame($data['content'], $lesson->content);
        $this->assertTrue($lesson->keyboard_auto_open);
        $this->get(route('admin.lessons.edit', $lesson))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
        $this->get(route('admin.lessons.preview', $lesson))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
        unset($data['keyboard_auto_open']);
        $this->put(route('admin.lessons.update', $lesson), $data)->assertRedirect();
        $this->assertFalse($lesson->fresh()->keyboard_auto_open);
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function test_lesson_keyboard_opens_only_when_configured(bool $automatic): void
    {
        $lesson = Lesson::factory()->create([
            'learning_path_id' => LearningPath::factory()->create(['group' => 'aksara'])->id,
            'keyboard_auto_open' => $automatic,
        ]);

        $response = $this->get(route('learning.lessons.show', $lesson->slug));

        $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
        $response->assertInertiaValue('lesson.keyboard_auto_open', $automatic);
    }

    public function test_quiz_settings_round_trip_per_question_and_survive_versioning(): void
    {
        $owner = User::factory()->create();
        $data = ['title' => 'Aksara Bali', 'questions' => [
            ['prompt' => "\u{1B13}", 'options' => ['Ka', 'Na'], 'correct_option' => 0, 'keyboard_auto_open' => '1'],
            ['prompt' => 'Soal biasa', 'options' => ['Ya', 'Tidak'], 'correct_option' => 1],
        ]];

        $this->actingAs($owner)->post(route('quizzes.store'), $data)->assertRedirect();

        $quiz = Quiz::query()->sole();
        $this->assertSame([true, false], $quiz->questions->pluck('keyboard_auto_open')->all());
        $this->get(route('quizzes.edit', $quiz))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
        app(RoomService::class)->create($quiz, $owner);
        $data['questions'][0]['keyboard_auto_open'] = '0';
        $data['questions'][1]['keyboard_auto_open'] = '1';
        $this->put(route('quizzes.update', $quiz), $data)->assertRedirect();
        $newQuiz = Quiz::query()->where('is_archived', false)->sole();
        $this->assertNotSame($quiz->id, $newQuiz->id);
        $this->assertSame([false, true], $newQuiz->questions->pluck('keyboard_auto_open')->all());
        $this->assertSame([true, false], $quiz->fresh()->questions->pluck('keyboard_auto_open')->all());
    }

    public function test_exercise_keyboard_is_available_before_and_after_completing_lesson(): void
    {
        $lesson = Lesson::factory()->create([
            'keyboard_auto_open' => true,
            'exercise_prompt' => 'Aksara ka?', 'exercise_options' => ['Ka', 'Na', 'Pa', 'Ma'], 'correct_option' => 0,
        ]);
        $visitorId = (string) Str::uuid();

        $this->withCookie('artaswaha_device', $visitorId)->get(route('learning.lessons.show', $lesson->slug))
            ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
        $this->withCookie('artaswaha_device', $visitorId)->post(route('learning.exercises.answer', $lesson->slug), ['answer_index' => 0])->assertRedirect();
        $this->withCookie('artaswaha_device', $visitorId)->get(route('learning.lessons.show', $lesson->slug))
            ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
    }

    public function test_admin_cannot_save_invalid_keyboard_setting(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $lesson = Lesson::factory()->create(['keyboard_auto_open' => true]);

        $this->actingAs($admin)->put(route('admin.lessons.update', $lesson), [
            'learning_path_id' => $lesson->learning_path_id, 'title' => $lesson->title, 'slug' => $lesson->slug,
            'summary' => $lesson->summary, 'content' => $lesson->content, 'sort_order' => 1,
            'keyboard_auto_open' => 'always',
        ])->assertSessionHasErrors('keyboard_auto_open');

        $this->assertTrue($lesson->fresh()->keyboard_auto_open);
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function test_active_room_exposes_manual_keyboard_and_per_question_auto_open(bool $automatic): void
    {
        $host = User::factory()->create();
        $quiz = Quiz::factory()->for($host, 'user')->create();
        QuizQuestion::factory()->for($quiz)->create(['keyboard_auto_open' => $automatic]);
        $service = app(RoomService::class);
        $room = $service->create($quiz, $host);
        $service->start($room, $host);

        $response = $this->actingAs($host)->get(route('rooms.show', $room->code));

        $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->etc());
        $response->assertInertiaValue('room.question.keyboard_auto_open', $automatic);
    }

    public function test_invalid_keyboard_setting_is_rejected_without_creating_quiz(): void
    {
        $this->actingAs(User::factory()->create())->post(route('quizzes.store'), [
            'title' => 'Kuis uji', 'questions' => [[
                'prompt' => 'Ka?', 'options' => ['A', 'B'], 'correct_option' => 0, 'keyboard_auto_open' => 'always',
            ]],
        ])->assertSessionHasErrors('questions.0.keyboard_auto_open');

        $this->assertDatabaseCount('quizzes', 0);
    }
}
