<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\RoomService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\AssertablePage;
use Tests\TestCase;

class QuizTimeLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_survive_edit_versioning_duplication_and_room_snapshot(): void
    {
        $owner = User::factory()->create();
        $data = ['title' => 'Kuis waktu', 'seconds_per_question' => 30, 'time_limit_minutes' => 2,
            'questions' => [['prompt' => 'Ka?', 'options' => ['Ka', 'Na'], 'correct_option' => 0, 'time_limit_seconds' => 10, 'keyboard_auto_open' => true]]];
        $this->actingAs($owner)->post(route('quizzes.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $quiz = Quiz::query()->sole();
        $question = $quiz->questions()->sole();
        $this->assertSame(120, $quiz->time_limit_seconds);
        $this->assertSame(10, $question->time_limit_seconds);
        $this->get(route('quizzes.edit', $quiz))->assertOk()->assertReact(fn (AssertablePage $page) => $page->etc());
        $room = app(RoomService::class)->create($quiz, $owner);
        $data['time_limit_minutes'] = 5;
        $data['questions'][0]['id'] = $question->id;
        $data['questions'][0]['time_limit_seconds'] = 40;

        $this->put(route('quizzes.update', $quiz), $data)->assertSessionHasNoErrors()->assertRedirect();

        $new = Quiz::query()->where('is_archived', false)->sole();
        $this->assertSame(300, $new->time_limit_seconds);
        $this->assertSame(40, $new->questions->sole()->time_limit_seconds);
        $this->assertSame(120, $room->fresh()->time_limit_seconds);
        $this->assertSame(10, $question->fresh()->time_limit_seconds);
        $this->post(route('quizzes.duplicate', $new))->assertRedirect();
        $copy = Quiz::query()->latest('id')->firstOrFail();
        $this->assertSame(300, $copy->time_limit_seconds);
        $this->assertSame(40, $copy->questions->sole()->time_limit_seconds);
        $this->assertTrue($copy->questions->sole()->keyboard_auto_open);
    }

    public function test_total_deadline_starts_on_start_and_is_not_reset_on_reload(): void
    {
        $this->travelTo(now()->startOfSecond());
        [$quiz, $room, $questions] = $this->room([300], 30, 60);
        $this->assertNull($room->ends_at);
        app(RoomService::class)->start($room, $quiz->user);
        $deadline = now()->addMinute();

        $this->actingAs($quiz->user)->get(route('rooms.show', $room->code))->assertOk()->assertReactValue('room.deadline', $deadline->toISOString());
        $this->travel(20)->seconds();
        $this->get(route('rooms.show', $room->code))->assertOk();

        $this->assertTrue($room->fresh()->ends_at->equalTo($deadline));
        $this->assertTrue($room->participants()->sole()->question_ends_at->equalTo(now()->addSeconds(280)));
    }

    public function test_total_limit_finishes_untimed_quiz_and_rejects_late_answers(): void
    {
        $this->travelTo(now()->startOfSecond());
        [$quiz, $room, $questions] = $this->room([null, null], null, 60);
        app(RoomService::class)->start($room, $quiz->user);
        $this->actingAs($quiz->user)->post(route('rooms.answer', $room->code), ['question_id' => $questions[0]->id, 'answer_index' => 0])->assertSessionHasNoErrors();
        $deadline = $room->fresh()->ends_at;
        $this->travel(60)->seconds();

        $this->post(route('rooms.answer', $room->code), ['question_id' => $questions[1]->id, 'answer_index' => 0])->assertStatus(422);

        $this->assertSame('finished', $room->fresh()->status);
        $this->assertTrue($room->fresh()->finished_at->equalTo($deadline));
        $this->assertDatabaseCount('room_answers', 1);
        $this->get(route('rooms.show', $room->code))->assertOk()->assertReactValue('room.status', 'finished');
    }

    public function test_mixed_timed_and_unlimited_questions_keep_independent_deadlines(): void
    {
        $this->travelTo(now()->startOfSecond());
        [$quiz, $room, $questions] = $this->room([10, null, 20]);
        app(RoomService::class)->start($room, $quiz->user);
        $this->travel(10)->seconds();
        app(RoomService::class)->syncTimedRoom($room);
        $participant = $room->participants()->sole();
        $this->assertSame(1, $participant->question_position);
        $this->assertNull($participant->question_ends_at);
        $this->travel(30)->seconds();
        app(RoomService::class)->syncTimedRoom($room);
        $this->assertSame(1, $participant->fresh()->question_position);

        $this->actingAs($quiz->user)->post(route('rooms.answer', $room->code), ['question_id' => $questions[1]->id, 'answer_index' => 0])->assertSessionHasNoErrors();

        $this->assertTrue($participant->fresh()->question_ends_at->equalTo(now()->addSeconds(20)));
        $this->travel(20)->seconds();
        app(RoomService::class)->syncTimedRoom($room);
        $this->assertSame('finished', $room->fresh()->status);
    }

    public function test_timeout_catches_up_using_each_duration_instead_of_restart_from_now(): void
    {
        $this->travelTo(now()->startOfSecond());
        [$quiz, $room] = $this->room([5, 10, 20]);
        app(RoomService::class)->start($room, $quiz->user);
        $this->travel(30)->seconds();

        app(RoomService::class)->syncTimedRoom($room);

        $participant = $room->participants()->sole();
        $this->assertSame(2, $participant->question_position);
        $this->assertTrue($participant->question_ends_at->equalTo(now()->addSeconds(5)));
        $this->travel(5)->seconds();
        app(RoomService::class)->syncTimedRoom($room);
        $this->assertSame('finished', $room->fresh()->status);
    }

    public function test_next_question_without_override_uses_quiz_default(): void
    {
        $this->travelTo(now()->startOfSecond());
        [$quiz, $room, $questions] = $this->room([5, null], 15);
        app(RoomService::class)->start($room, $quiz->user);
        $this->travel(2)->seconds();

        $this->actingAs($quiz->user)->post(route('rooms.answer', $room->code), ['question_id' => $questions[0]->id, 'answer_index' => 0])->assertSessionHasNoErrors();

        $this->assertTrue($room->participants()->sole()->question_ends_at->equalTo(now()->addSeconds(15)));
    }

    public function test_total_limit_expires_during_feedback_without_extending_for_feedback_pause(): void
    {
        $this->travelTo(now()->startOfSecond());
        [$quiz, $room, $questions] = $this->room([300, 300], null, 60);
        $quiz->update(['show_answers_immediately' => true]);
        app(RoomService::class)->start($room, $quiz->user);
        $this->travel(59)->seconds();
        $this->actingAs($quiz->user)->post(route('rooms.answer', $room->code), ['question_id' => $questions[0]->id, 'answer_index' => 0])->assertSessionHasNoErrors();
        $answer = $room->participants()->sole()->answers()->sole();
        $this->get(route('rooms.show', ['room' => $room->code, 'feedback' => $answer->id]))->assertOk()->assertReact(fn (AssertablePage $page) => $page->etc());
        $this->travel(1)->seconds();

        $this->get(route('rooms.show', ['room' => $room->code, 'feedback' => $answer->id]))->assertOk()->assertReactValue('room.status', 'finished');

        $this->assertSame('finished', $room->fresh()->status);
        $this->assertDatabaseCount('room_answers', 1);
    }

    public function test_heartbeat_reports_total_expiry_as_finished_instead_of_host_absence(): void
    {
        $this->travelTo(now()->startOfSecond());
        [$quiz, $room] = $this->room([null], null, 60);
        app(RoomService::class)->start($room, $quiz->user);
        $this->travel(95)->seconds();

        $this->actingAs($quiz->user)->post(route('rooms.heartbeat', $room->code))
            ->assertNoContent()->assertHeader('X-Room-Status', 'finished');

        $this->assertSame('finished', $room->fresh()->status);
    }

    public function test_existing_scheduler_closes_expired_room_even_when_host_is_recent(): void
    {
        $this->travelTo(now()->startOfSecond());
        [$quiz, $room] = $this->room([null], null, 60);
        app(RoomService::class)->start($room, $quiz->user);
        $this->travel(60)->seconds();
        $room->update(['host_last_seen_at' => now()]);
        $schedule = app(Schedule::class);
        $event = collect($schedule->events())->first(fn ($event): bool => $event->description === 'rooms:deactivate-absent-hosts');
        $this->assertNotNull($event);

        $event->run(app());

        $this->assertSame('finished', $room->fresh()->status);
    }

    public function test_leaving_at_total_deadline_keeps_submitted_answers_and_finished_status(): void
    {
        $this->travelTo(now()->startOfSecond());
        [$quiz, $room, $questions] = $this->room([null, null], null, 60);
        app(RoomService::class)->start($room, $quiz->user);
        $this->actingAs($quiz->user)->post(route('rooms.answer', $room->code), ['question_id' => $questions[0]->id, 'answer_index' => 0])->assertSessionHasNoErrors();
        $this->travel(60)->seconds();

        $this->post(route('rooms.leave', $room->code))->assertRedirect(route('quizzes.index'))->assertSessionHas('status', 'Kamu sudah keluar dari room.');

        $this->assertSame('finished', $room->fresh()->status);
        $this->assertDatabaseCount('room_answers', 1);
        $this->assertDatabaseCount('room_participants', 1);
    }

    #[TestWith(['time_limit_minutes', 0, 'Batas total minimal 1 menit.'])]
    #[TestWith(['time_limit_minutes', 181, 'Batas total maksimal 180 menit.'])]
    #[TestWith(['time_limit_minutes', 1.5, 'Isi batas total dengan menit bulat.'])]
    #[TestWith(['question', 4, 'Waktu khusus soal minimal 5 detik.'])]
    #[TestWith(['question', 301, 'Waktu khusus soal maksimal 300 detik.'])]
    #[TestWith(['question', 5.5, 'Isi waktu khusus soal dengan detik bulat.'])]
    public function test_invalid_limits_are_rejected_without_creating_quiz(string $field, int|float $value, string $message): void
    {
        $data = ['title' => 'Invalid', 'questions' => [['prompt' => 'Ka?', 'options' => ['Ka', 'Na'], 'correct_option' => 0]]];
        if ($field === 'question') {
            $data['questions'][0]['time_limit_seconds'] = $value;
            $error = 'questions.0.time_limit_seconds';
        } else {
            $data[$field] = $value;
            $error = $field;
        }

        $this->actingAs(User::factory()->create())->post(route('quizzes.store'), $data)->assertSessionHasErrors([$error => $message]);

        $this->assertDatabaseCount('quizzes', 0);
    }

    #[TestWith([5, 1])]
    #[TestWith([300, 180])]
    public function test_minimum_and_maximum_limits_are_saved_in_seconds(int $seconds, int $minutes): void
    {
        $this->actingAs(User::factory()->create())->post(route('quizzes.store'), [
            'title' => 'Batas sah', 'time_limit_minutes' => $minutes,
            'questions' => [['prompt' => 'Ka?', 'options' => ['Ka', 'Na'], 'correct_option' => 0, 'time_limit_seconds' => $seconds]],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $quiz = Quiz::query()->sole();
        $this->assertSame($minutes * 60, $quiz->time_limit_seconds);
        $this->assertSame($seconds, $quiz->questions->sole()->time_limit_seconds);
    }

    private function room(array $durations, ?int $default = null, ?int $total = null): array
    {
        $quiz = Quiz::factory()->create(['seconds_per_question' => $default, 'time_limit_seconds' => $total, 'show_answers_immediately' => false]);
        $questions = collect();
        foreach ($durations as $index => $duration) {
            $questions->push(QuizQuestion::factory()->for($quiz)->create(['sort_order' => $index + 1, 'time_limit_seconds' => $duration]));
        }
        $room = app(RoomService::class)->create($quiz, $quiz->user);

        return [$quiz, $room, $questions];
    }
}
