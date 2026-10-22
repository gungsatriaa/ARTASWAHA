<?php

namespace Tests\Feature;

use App\Events\RoomUpdated;
use App\Models\Quiz;
use App\Models\Room;
use App\Models\RoomAnswer;
use App\Models\RoomParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizRoomFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_private_quiz_with_questions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('quizzes.store'), [
            'title' => 'Teka-teki Bali',
            'questions' => [[
                'prompt' => 'Contoh pertanyaan?',
                'options' => ['Jawaban A', 'Jawaban B'],
                'correct_option' => 1,
            ]],
        ]);

        $quiz = Quiz::query()->firstOrFail();
        $response->assertRedirect(route('quizzes.show', $quiz));
        $this->assertSame($user->id, $quiz->user_id);
        $this->assertSame(1, $quiz->questions()->count());
        $this->assertArrayNotHasKey('correct_option', $quiz->questions()->first()->toArray());
    }

    public function test_quiz_rejects_correct_answer_outside_available_options(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('quizzes.store'), [
                'title' => 'Pertanyaan tidak valid',
                'questions' => [[
                    'prompt' => 'Contoh pertanyaan?',
                    'options' => ['Jawaban A', 'Jawaban B'],
                    'correct_option' => 3,
                ]],
            ])
            ->assertSessionHasErrors('questions.0.correct_option');

        $this->assertDatabaseCount('quizzes', 0);
    }

    public function test_private_quiz_cannot_be_read_or_hosted_by_another_user(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $quiz = $this->createQuiz($owner);

        $this->actingAs($otherUser)->get(route('quizzes.show', $quiz))->assertNotFound();
        $this->actingAs($otherUser)->post(route('rooms.store', $quiz))->assertForbidden();
    }

    public function test_quiz_and_waiting_room_pages_render_for_their_owner(): void
    {
        $host = User::factory()->create();
        $quiz = $this->createQuiz($host);

        $this->actingAs($host)->get(route('quizzes.index'))->assertOk()->assertSee('Kuis uji');
        $this->get(route('quizzes.create'))->assertOk()->assertSee('Tambah pertanyaan');
        $this->get(route('quizzes.show', $quiz))->assertOk()->assertSee('Pertanyaan pertama?');

        $this->post(route('rooms.store', $quiz))->assertRedirect();
        $room = Room::query()->firstOrFail();
        $this->get(route('rooms.show', $room->code))->assertOk()->assertSee($room->code)->assertSee('Mulai kuis');
    }

    public function test_room_join_answer_scoring_and_host_controls_are_server_authorized(): void
    {
        $host = User::factory()->create();
        $player = User::factory()->create();
        $quiz = $this->createQuiz($host);

        $this->actingAs($host)->post(route('rooms.store', $quiz))->assertRedirect();
        $room = Room::query()->firstOrFail();
        $this->assertSame(1, RoomParticipant::query()->where('room_id', $room->id)->count());

        $this->actingAs($player)->post(route('rooms.join'), ['code' => strtolower($room->code)])
            ->assertRedirect(route('rooms.show', $room->code));
        $this->actingAs($player)->post(route('rooms.start', $room->code))->assertForbidden();
        $this->actingAs($host)->post(route('rooms.start', $room->code))->assertRedirect(route('rooms.show', $room->code));

        $question = $quiz->questions()->firstOrFail();
        $answerData = ['question_id' => $question->id, 'answer_index' => 1, 'points_awarded' => 9999];
        $this->actingAs($player)->post(route('rooms.answer', $room->code), $answerData)->assertRedirect();
        $this->post(route('rooms.answer', $room->code), $answerData)->assertRedirect();

        $this->assertSame(10, RoomParticipant::query()->where('room_id', $room->id)->where('user_id', $player->id)->value('score'));
        $this->assertSame(1, RoomAnswer::query()->count());

        $this->actingAs($host)->post(route('rooms.advance', $room->code))->assertRedirect(route('rooms.show', $room->code));
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'status' => 'finished']);
    }

    public function test_private_room_channel_only_allows_its_host_and_participants(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
            'broadcasting.connections.reverb.options' => [
                'host' => 'localhost',
                'port' => 8080,
                'scheme' => 'http',
                'useTLS' => false,
            ],
        ]);
        require base_path('routes/channels.php');

        $host = User::factory()->create();
        $player = User::factory()->create();
        $outsider = User::factory()->create();
        $room = Room::factory()->create(['host_id' => $host->id]);
        RoomParticipant::factory()->create(['room_id' => $room->id, 'user_id' => $player->id]);
        $this->assertSame($host->id, $room->host_id);
        $this->assertTrue(Room::query()->where('code', $room->code)->where('host_id', $host->id)->exists());
        $payload = ['socket_id' => '1234.5678', 'channel_name' => 'private-rooms.'.$room->code];

        $this->actingAs($host)->postJson('/broadcasting/auth', $payload)->assertOk();
        $this->actingAs($player)->postJson('/broadcasting/auth', $payload)->assertOk();
        $this->actingAs($outsider)->postJson('/broadcasting/auth', $payload)->assertForbidden();
    }

    public function test_room_update_event_does_not_expose_correct_answer(): void
    {
        $event = new RoomUpdated('ROOMCODE12', [
            'status' => 'active',
            'question' => ['id' => 1, 'prompt' => 'Pertanyaan?', 'options' => ['A', 'B'], 'sort_order' => 1],
        ]);

        $this->assertSame('private-rooms.ROOMCODE12', $event->broadcastOn()->name);
        $this->assertSame(['status', 'question'], array_keys($event->broadcastWith()));
        $this->assertArrayNotHasKey('correct_option', $event->broadcastWith()['question']);
    }

    private function createQuiz(User $owner): Quiz
    {
        $quiz = $owner->quizzes()->create(['title' => 'Kuis uji', 'leaderboard_enabled' => true]);
        $quiz->questions()->create([
            'prompt' => 'Pertanyaan pertama?',
            'options' => ['Salah', 'Benar'],
            'correct_option' => 1,
            'sort_order' => 1,
        ]);

        return $quiz;
    }
}
