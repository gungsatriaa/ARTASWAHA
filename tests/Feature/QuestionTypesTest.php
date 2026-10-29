<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\LessonAttempt;
use App\Models\RoomAnswer;
use App\Models\User;
use App\Services\QuizService;
use App\Services\RoomService;
use Database\Seeders\LearningPathSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\AssertablePage as Assert;
use Tests\TestCase;

class QuestionTypesTest extends TestCase
{
    use RefreshDatabase;

    private function questions(): array
    {
        return [
            ['prompt' => 'Pilih Ha', 'options' => ['ᬳ', 'ᬦ'], 'correct_option' => 0],
            ['prompt' => 'Pilih dua aksara', 'question_type' => 'multiple', 'options' => ['ᬳ', 'ᬦ', 'ᬓ'], 'correct_options' => [0, 2]],
            ['prompt' => 'Tulis nama tanda', 'question_type' => 'essay', 'options' => ['', '', '', ''], 'accepted_answers' => ['Taleng Tedong', 'ᬓᭀ'], 'keyboard_auto_open' => true],
        ];
    }

    public function test_quiz_editor_stores_all_types_and_duplicate_preserves_keys(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('quizzes.store'), ['title' => 'Tiga tipe', 'questions' => $this->questions()])
            ->assertSessionHasNoErrors()->assertRedirect();
        $quiz = $user->quizzes()->sole();
        $this->assertSame(['single', 'multiple', 'essay'], $quiz->questions->pluck('question_type')->all());
        $this->assertSame([0, 2], $quiz->questions[1]->correct_options);
        $this->assertNull($quiz->questions[1]->correct_option);
        $this->assertSame([], $quiz->questions[2]->options);
        $this->assertSame(['Taleng Tedong', 'ᬓᭀ'], $quiz->questions[2]->accepted_answers);

        $copy = app(QuizService::class)->duplicate($quiz, $user);
        $this->assertSame([0, 2], $copy->questions[1]->correct_options);
        $this->assertSame($quiz->questions[2]->accepted_answers, $copy->questions[2]->accepted_answers);
        $this->assertArrayNotHasKey('accepted_answers', $copy->questions[2]->toArray());
    }

    public function test_room_grades_checkbox_and_normalized_essay_without_leaking_keys_or_double_scoring(): void
    {
        $user = User::factory()->create();
        $quiz = app(QuizService::class)->create($user, ['title' => 'Latihan', 'questions' => $this->questions()]);
        $service = app(RoomService::class);
        $room = $service->create($quiz, $user);
        $service->start($room, $user);
        $this->actingAs($user);

        foreach ([['answer_index' => 0], ['answer_indexes' => [2, 0]], ['answer_text' => "  TALENG\n TEDONG  "]] as $index => $answer) {
            $question = $quiz->questions[$index];
            $this->get(route('rooms.show', $room->code))->assertOk()->assertReact(fn (Assert $page): Assert => $page
                ->where('room.question.id', $question->id)
                ->missing('room.question.correct_options')->missing('room.question.accepted_answers')
                ->where('room.correctText', null));
            $payload = ['question_id' => $question->id, 'answer_index' => '', 'answer_indexes' => [], 'answer_text' => '', ...$answer];
            if ($index === 1) {
                $this->post(route('rooms.answer', $room->code), [...$payload, 'answer_indexes' => []])->assertSessionHasErrors('answer_indexes');
            }
            $this->post(route('rooms.answer', $room->code), $payload)->assertSessionHasNoErrors()->assertRedirect();
            $record = RoomAnswer::query()->where('quiz_question_id', $question->id)->sole();
            $this->assertTrue($record->is_correct);
            $this->assertSame(10, $record->points_awarded);
            if ($index === 1) {
                $this->post(route('rooms.answer', $room->code), $payload)->assertRedirect();
                $this->assertSame(20, $room->participants()->sole()->score);
                $this->assertSame([2, 0], $record->answer_indexes);
            }
        }
        $this->assertSame(30, $room->participants()->sole()->score);
        $this->assertDatabaseCount('room_answers', 3);
        $this->get(route('rooms.show', $room->code))->assertOk()->assertReact(fn (Assert $page): Assert => $page
            ->where('room.results.1.correct', 'ᬳ, ᬓ')->where('room.results.1.selected', 'ᬓ, ᬳ')
            ->where('room.results.2.isCorrect', true));
    }

    public function test_new_types_survive_versioning_and_timed_feedback_preserves_the_next_deadline(): void
    {
        $user = User::factory()->create();
        $service = app(QuizService::class);
        $quiz = $service->create($user, ['title' => 'Bertimer', 'seconds_per_question' => 20,
            'show_answers_immediately' => true, 'questions' => array_slice($this->questions(), 1)]);
        $rooms = app(RoomService::class);
        $room = $rooms->create($quiz, $user);
        $rooms->start($room, $user);
        $originalQuestion = $quiz->questions[0];
        $this->actingAs($user)->post(route('rooms.answer', $room->code), [
            'question_id' => $originalQuestion->id, 'answer_indexes' => [0, 2],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $answer = RoomAnswer::where('quiz_question_id', $originalQuestion->id)->sole();
        $deadline = $room->participants()->sole()->question_ends_at;
        $this->assertNotNull($deadline);
        $this->get(route('rooms.show', ['room' => $room->code, 'feedback' => $answer->id]))
            ->assertOk()->assertReact(fn (Assert $page): Assert => $page
            ->where('room.feedback', true)->where('room.correctOptions', [0, 2])
            ->where('room.question.question_type', 'multiple')->where('room.correctText', 'ᬳ, ᬓ'));
        $this->assertTrue($deadline->equalTo($room->participants()->sole()->question_ends_at));

        $updated = $service->update($quiz, $user, ['title' => 'Versi baru', 'questions' => array_slice($this->questions(), 1)]);
        $this->assertNotSame($quiz->id, $updated->id);
        $this->assertSame([0, 2], $updated->questions[0]->correct_options);
        $this->assertSame(['Taleng Tedong', 'ᬓᭀ'], $updated->questions[1]->accepted_answers);
        $this->assertSame($quiz->id, $room->fresh()->quiz_id);
    }

    public function test_server_enforces_the_stored_type_and_rejects_invalid_selections(): void
    {
        $user = User::factory()->create();
        $quiz = app(QuizService::class)->create($user, ['title' => 'Validasi', 'questions' => [$this->questions()[1]]]);
        $service = app(RoomService::class);
        $room = $service->create($quiz, $user);
        $service->start($room, $user);
        $question = $quiz->questions->sole();
        $this->actingAs($user);
        foreach ([
            [['answer_index' => 0, 'question_type' => 'single'], 'answer_indexes'],
            [['answer_indexes' => [0, 0]], 'answer_indexes.0'],
            [['answer_indexes' => [4]], 'answer_indexes.0'],
            [['answer_indexes' => []], 'answer_indexes'],
        ] as [$answer, $error]) {
            $this->post(route('rooms.answer', $room->code), ['question_id' => $question->id, ...$answer])
                ->assertSessionHasErrors($error);
        }
        $this->assertDatabaseCount('room_answers', 0);
        $this->post(route('rooms.answer', $room->code), [
            'question_id' => $question->id, 'answer_indexes' => [0], 'is_correct' => true,
        ])->assertRedirect();
        $this->assertFalse(RoomAnswer::sole()->is_correct);
        $this->assertSame(0, $room->participants()->sole()->score);
    }

    public function test_invalid_definitions_are_rejected_before_saving(): void
    {
        $this->actingAs(User::factory()->create());
        $invalid = [
            [['question_type' => 'essay', 'prompt' => 'Tulis', 'accepted_answers' => []], 'accepted_answers'],
            [['question_type' => 'multiple', 'prompt' => 'Pilih', 'options' => ['A', 'B'], 'correct_options' => [0, 0]], 'correct_options.0'],
            [['question_type' => 'multiple', 'prompt' => 'Pilih', 'options' => ['A', 'B'], 'correct_options' => [3]], 'correct_options.0'],
            [['question_type' => 'unknown', 'prompt' => 'Pilih', 'options' => ['A', 'B'], 'correct_option' => 0], 'question_type'],
        ];
        foreach ($invalid as [$question, $error]) {
            $this->post(route('quizzes.store'), ['title' => 'Tidak valid', 'questions' => [$question]])
                ->assertSessionHasErrors('questions.0.'.$error);
        }
        $this->assertDatabaseCount('quizzes', 0);
    }

    public function test_lesson_admin_can_create_both_new_types_and_learner_can_retry_after_finishing(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->seed(LearningPathSeeder::class);
        $path = LearningPath::query()->where('slug', 'pengangge-suara')->sole();
        $learner = User::factory()->create();
        foreach (['multiple', 'essay'] as $type) {
            $this->actingAs($admin)->post(route('admin.lessons.store'), [
                'learning_path_id' => $path->id, 'title' => 'Latihan '.$type, 'slug' => 'latihan-'.$type,
                'summary' => 'Materi', 'content' => 'Baca lalu jawab.', 'sort_order' => 1,
                'has_exercise' => true, 'question_type' => $type, 'exercise_prompt' => 'Jawab soal',
                'exercise_options' => ['A', 'B', 'C', 'D'], 'correct_options' => [0, 2],
                'accepted_answers' => ['ᬓᭀ'],
            ])->assertSessionHasNoErrors()->assertRedirect();
            $lesson = Lesson::where('slug', 'latihan-'.$type)->sole();
            $this->assertSame('Jawab soal', $lesson->exercise_prompt);
            $lesson->update(['reviewed_at' => now(), 'is_published' => true]);
            $this->actingAs($learner)->withCookie('artaswaha_device', (string) Str::uuid());
            $this->get(route('learning.lessons.show', $lesson))->assertOk()->assertReact(fn (Assert $page): Assert => $page
                ->where('lesson.question_type', $type)->missing('lesson.accepted_answers')->missing('lesson.correct_options'));
            $this->post(route('learning.exercises.answer', $lesson), $type === 'multiple'
                ? ['answer_indexes' => [0]] : ['answer_text' => 'Salah'])->assertSessionHasNoErrors()->assertRedirect();
            $this->assertDatabaseHas('learning_progress', ['lesson_id' => $lesson->id]);
            $attempt = LessonAttempt::query()->where('lesson_id', $lesson->id)->sole();
            $this->assertFalse($attempt->answers[0]['is_correct']);
            $this->post(route('learning.exercises.retry', $lesson), ['attempt_id' => $attempt->id])->assertRedirect();
            $this->post(route('learning.exercises.answer', $lesson), $type === 'multiple'
                ? ['answer_index' => '', 'answer_indexes' => [2, 0], 'answer_text' => ''] : ['answer_index' => '', 'answer_indexes' => [], 'answer_text' => 'ᬓᭀ'])->assertSessionHasNoErrors()->assertRedirect();
            $this->assertDatabaseHas('learning_progress', ['lesson_id' => $lesson->id, 'user_id' => $learner->id, 'points' => 10]);
        }
        $this->assertSame(2, LearningProgress::count());
    }
}
