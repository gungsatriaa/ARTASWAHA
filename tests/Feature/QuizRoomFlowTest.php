<?php

namespace Tests\Feature;

use App\Events\RoomUpdated;
use App\Models\Quiz;
use App\Models\Room;
use App\Models\RoomAnswer;
use App\Models\RoomParticipant;
use App\Models\User;
use App\Services\RoomService;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public function test_quiz_timer_is_optional_and_validated(): void
    {
        $user = User::factory()->create();
        $question = ['prompt' => 'Soal?', 'options' => ['A', 'B'], 'correct_option' => 1];

        $this->actingAs($user)->post(route('quizzes.store'), [
            'title' => 'Kuis cepat', 'seconds_per_question' => 10, 'questions' => [$question],
        ])->assertRedirect();
        $quiz = Quiz::query()->firstOrFail();
        $this->assertSame(10, $quiz->seconds_per_question);
        $this->assertSame(10, app(RoomService::class)->create($quiz, $user)->seconds_per_question);

        $this->post(route('quizzes.store'), [
            'title' => 'Terlalu cepat', 'seconds_per_question' => 1, 'questions' => [$question],
        ])->assertSessionHasErrors('seconds_per_question');
        $this->assertDatabaseCount('quizzes', 1);
    }

    public function test_quiz_allows_duplicate_options(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('quizzes.store'), [
            'title' => 'Pilihan yang boleh sama',
            'questions' => [[
                'prompt' => 'Contoh pertanyaan?',
                'options' => ['Jawaban A', 'Jawaban A'],
                'correct_option' => 0,
            ]],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('quizzes', 1);
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
        $this->assertMatchesRegularExpression('/\A[A-Z0-9]{4}\z/', $room->code);
        $this->get(route('rooms.show', $room->code))->assertOk()->assertSee($room->code)->assertSee('Mulai kuis');
    }

    public function test_room_creation_retries_a_duplicate_four_character_code(): void
    {
        $host = User::factory()->create();
        $quiz = $this->createQuiz($host);
        Room::factory()->create(['code' => 'AB12']);

        Str::createRandomStringsUsingSequence(['AB12', 'CD34']);

        try {
            $room = app(RoomService::class)->create($quiz, $host);
        } finally {
            Str::createRandomStringsUsing();
        }

        $this->assertSame('CD34', $room->code);
        $this->assertDatabaseCount('rooms', 2);
    }

    public function test_room_join_rejects_codes_with_invalid_length(): void
    {
        $player = User::factory()->create();

        $this->actingAs($player)->post(route('rooms.join'), ['code' => 'ABCDE'])
            ->assertSessionHasErrors('code');
    }

    public function test_existing_ten_character_room_code_still_allows_joining(): void
    {
        $host = User::factory()->create();
        $player = User::factory()->create();
        $room = Room::factory()->create(['host_id' => $host->id, 'code' => 'ABCD123456']);

        $this->actingAs($player)->post(route('rooms.join'), ['code' => strtolower($room->code)])
            ->assertRedirect(route('rooms.show', $room->code));
    }

    public function test_room_invitation_link_opens_join_form_with_room_code_prefilled(): void
    {
        $host = User::factory()->create();
        $room = Room::factory()->create(['host_id' => $host->id]);
        RoomParticipant::factory()->create(['room_id' => $room->id, 'user_id' => $host->id]);

        $response = $this->actingAs($host)->get(route('rooms.show', $room->code));

        $response->assertOk()
            ->assertSee(route('quizzes.index', ['code' => $room->code]), false);
        $this->get(route('quizzes.index', ['code' => $room->code]))
            ->assertOk()
            ->assertSee('value="'.$room->code.'"', false);
    }

    public function test_room_displays_current_profile_names_and_round_avatar_images(): void
    {
        $host = User::factory()->create(['name' => 'Host Bali', 'avatar' => 'avatar/gajah.webp']);
        $player = User::factory()->create(['name' => 'Pemain Bali', 'avatar' => 'avatar/kucing.webp']);
        $room = Room::factory()->create(['host_id' => $host->id]);
        RoomParticipant::factory()->create(['room_id' => $room->id, 'user_id' => $host->id, 'display_name' => 'Nama lama host']);
        RoomParticipant::factory()->create(['room_id' => $room->id, 'user_id' => $player->id, 'display_name' => 'Nama lama pemain']);

        $this->actingAs($host)->get(route('rooms.show', $room->code))
            ->assertOk()
            ->assertSee('Host Bali')
            ->assertSee('Pemain Bali')
            ->assertDontSee('Nama lama pemain')
            ->assertSee(asset('avatar/gajah.webp'))
            ->assertSee(asset('avatar/kucing.webp'))
            ->assertSee('rounded-full object-cover ring-2 ring-primary/40', false);
    }

    public function test_uploaded_room_avatar_is_only_available_to_room_members(): void
    {
        Storage::fake('local');
        $host = User::factory()->create();
        $player = User::factory()->create(['avatar' => 'avatars/player.webp']);
        $outsider = User::factory()->create(['avatar' => 'avatars/outsider.webp']);
        Storage::disk('local')->put('avatars/player.webp', 'player avatar');
        Storage::disk('local')->put('avatars/outsider.webp', 'outsider avatar');
        $room = Room::factory()->create(['host_id' => $host->id]);
        RoomParticipant::factory()->create(['room_id' => $room->id, 'user_id' => $host->id]);
        RoomParticipant::factory()->create(['room_id' => $room->id, 'user_id' => $player->id]);
        $avatarUrl = route('rooms.participant-avatar', [$room->code, $player->id]);

        $this->actingAs($host)->get(route('rooms.show', $room->code))->assertSee($avatarUrl);
        $this->get($avatarUrl)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(route('rooms.participant-avatar', [$room->code, $outsider->id]))->assertNotFound();
        $this->actingAs($outsider)->get($avatarUrl)->assertForbidden();
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

        $this->actingAs($player)->get(route('rooms.show', $room->code))
            ->assertOk()
            ->assertSee('Pertanyaan 1 dari 1')
            ->assertSee('Pilih satu jawaban');

        $question = $quiz->questions()->firstOrFail();
        $answerData = ['question_id' => $question->id, 'answer_index' => 1, 'points_awarded' => 9999];
        $this->actingAs($player)->post(route('rooms.answer', $room->code), $answerData)->assertRedirect();
        $this->post(route('rooms.answer', $room->code), $answerData)->assertRedirect();

        $this->assertSame(10, RoomParticipant::query()->where('room_id', $room->id)->where('user_id', $player->id)->value('score'));
        $this->assertSame(1, RoomAnswer::query()->count());

        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'status' => 'active']);
        $this->actingAs($host)->post(route('rooms.answer', $room->code), $answerData)->assertRedirect();
        $this->assertSame(9, RoomParticipant::query()->where('room_id', $room->id)->where('user_id', $host->id)->value('score'));
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'status' => 'finished']);
    }

    public function test_untimed_players_advance_independently_and_receive_speed_rank_points(): void
    {
        $host = User::factory()->create();
        $player = User::factory()->create();
        $quiz = $this->createQuiz($host);
        $secondQuestion = $quiz->questions()->create([
            'prompt' => 'Pertanyaan kedua?',
            'options' => ['Salah', 'Benar'],
            'correct_option' => 1,
            'sort_order' => 2,
        ]);
        $room = app(RoomService::class)->create($quiz, $host);
        app(RoomService::class)->join($room->code, $player);
        app(RoomService::class)->start($room, $host);
        $firstQuestion = $quiz->questions()->firstOrFail();

        $this->actingAs($player)->post(route('rooms.answer', $room->code), ['question_id' => $firstQuestion->id, 'answer_index' => 1])->assertRedirect();
        $this->get(route('rooms.show', $room->code))->assertOk()->assertSee('Pertanyaan kedua?');
        $this->actingAs($host)->get(route('rooms.show', $room->code))->assertOk()->assertSee('Pertanyaan pertama?');
        $this->actingAs($player)->post(route('rooms.answer', $room->code), ['question_id' => $secondQuestion->id, 'answer_index' => 1])->assertRedirect();
        $this->get(route('rooms.show', $room->code))->assertOk()->assertSee('Kamu sudah menjawab semua soal!');
        $this->assertSame('active', $room->fresh()->status);
        $this->actingAs($host)->post(route('rooms.answer', $room->code), ['question_id' => $firstQuestion->id, 'answer_index' => 1])->assertRedirect();
        $this->assertSame(9, $room->participants()->where('user_id', $host->id)->value('score'));
        $this->post(route('rooms.answer', $room->code), ['question_id' => $secondQuestion->id, 'answer_index' => 1])->assertRedirect();
        $this->assertSame('finished', $room->fresh()->status);
    }

    public function test_timed_room_advances_everyone_at_deadline_and_rejects_late_answers(): void
    {
        $this->freezeTime();
        $host = User::factory()->create();
        $player = User::factory()->create();
        $quiz = $this->createQuiz($host);
        $quiz->update(['seconds_per_question' => 10]);
        $secondQuestion = $quiz->questions()->create([
            'prompt' => 'Soal berikutnya?',
            'options' => ['Salah', 'Benar'],
            'correct_option' => 1,
            'sort_order' => 2,
        ]);
        $room = app(RoomService::class)->create($quiz, $host);
        app(RoomService::class)->join($room->code, $player);
        app(RoomService::class)->start($room, $host);
        $firstQuestion = $quiz->questions()->firstOrFail();

        $this->actingAs($player)->post(route('rooms.answer', $room->code), ['question_id' => $firstQuestion->id, 'answer_index' => 1])->assertRedirect();
        $this->get(route('rooms.show', $room->code))->assertSee('Soal berikutnya muncul saat waktu habis.');
        $this->travel(11)->seconds();
        $this->actingAs($host)->get(route('rooms.status', $room->code))->assertNoContent();
        $this->assertSame($secondQuestion->id, $room->fresh()->current_question_id);
        $this->actingAs($player)->get(route('rooms.show', $room->code))->assertSee('Soal berikutnya?');
        $this->actingAs($host)->post(route('rooms.answer', $room->code), ['question_id' => $firstQuestion->id, 'answer_index' => 1])->assertSessionHasErrors('question_id');
        $this->travel(10)->seconds();
        $this->get(route('rooms.status', $room->code))->assertNoContent();
        $this->assertSame('finished', $room->fresh()->status);
    }

    public function test_timed_room_catches_up_after_clients_were_offline(): void
    {
        $this->freezeTime();
        $host = User::factory()->create();
        $quiz = $this->createQuiz($host);
        $quiz->update(['seconds_per_question' => 5]);
        $quiz->questions()->create([
            'prompt' => 'Pertanyaan kedua?',
            'options' => ['Salah', 'Benar'],
            'correct_option' => 1,
            'sort_order' => 2,
        ]);
        $room = app(RoomService::class)->create($quiz, $host);
        app(RoomService::class)->start($room, $host);

        $this->travel(11)->seconds();
        $this->actingAs($host)->get(route('rooms.status', $room->code))->assertNoContent();
        $this->assertSame('finished', $room->fresh()->status);
    }

    public function test_participant_can_leave_room_and_is_removed_from_live_room_data(): void
    {
        $host = User::factory()->create();
        $player = User::factory()->create();
        $room = Room::factory()->create(['host_id' => $host->id]);
        RoomParticipant::factory()->create(['room_id' => $room->id, 'user_id' => $host->id]);
        RoomParticipant::factory()->create(['room_id' => $room->id, 'user_id' => $player->id]);
        Event::fake([RoomUpdated::class]);

        $this->actingAs($player)
            ->post(route('rooms.leave', $room->code))
            ->assertRedirect(route('quizzes.index'))
            ->assertSessionHas('status', 'Kamu sudah keluar dari room.');

        $this->assertDatabaseMissing('room_participants', ['room_id' => $room->id, 'user_id' => $player->id]);
        $this->assertDatabaseHas('room_participants', ['room_id' => $room->id, 'user_id' => $host->id]);
        Event::assertDispatched(RoomUpdated::class);
    }

    public function test_room_host_cannot_leave_and_orphan_the_room(): void
    {
        $host = User::factory()->create();
        $room = Room::factory()->create(['host_id' => $host->id]);
        RoomParticipant::factory()->create(['room_id' => $room->id, 'user_id' => $host->id]);

        $this->actingAs($host)->post(route('rooms.leave', $room->code))->assertForbidden();

        $this->assertDatabaseHas('room_participants', ['room_id' => $room->id, 'user_id' => $host->id]);
    }

    public function test_only_host_can_delete_room_and_participants_receive_deletion_event(): void
    {
        $host = User::factory()->create();
        $player = User::factory()->create();
        $quiz = $this->createQuiz($host);
        $room = Room::factory()->create(['quiz_id' => $quiz->id, 'host_id' => $host->id]);
        $participant = RoomParticipant::factory()->create(['room_id' => $room->id, 'user_id' => $player->id]);
        RoomAnswer::factory()->create([
            'room_participant_id' => $participant->id,
            'quiz_question_id' => $quiz->questions()->firstOrFail()->id,
        ]);
        Event::fake([RoomUpdated::class]);

        $this->actingAs($player)->get(route('rooms.show', $room->code))
            ->assertOk()
            ->assertSee('Room dihapus oleh host')
            ->assertDontSee('data-room-delete-open', false);
        $this->get(route('rooms.status', $room->code))->assertNoContent();
        $this->actingAs(User::factory()->create())->get(route('rooms.status', $room->code))->assertForbidden();
        $this->actingAs($player)->delete(route('rooms.destroy', $room->code))->assertForbidden();
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);

        $this->actingAs($host)->get(route('rooms.show', $room->code))
            ->assertOk()
            ->assertSee('data-room-delete-open', false);
        $this->delete(route('rooms.destroy', $room->code))
            ->assertRedirect(route('quizzes.index'))
            ->assertSessionHas('status', 'Room berhasil dihapus.');

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
        $this->assertDatabaseMissing('room_participants', ['id' => $participant->id]);
        $this->assertDatabaseCount('room_answers', 0);
        $this->assertDatabaseHas('quizzes', ['id' => $quiz->id]);
        $this->actingAs($player)->get(route('rooms.show', $room->code))->assertNotFound();
        $this->get(route('rooms.status', $room->code))->assertNotFound();
        Event::assertDispatched(RoomUpdated::class, fn (RoomUpdated $event) => $event->roomCode === $room->code
            && $event->broadcastWith() === ['status' => 'deleted', 'question' => null]);
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
        $payload = ['socket_id' => '1234.5678', 'channel_name' => 'presence-rooms.'.$room->code];

        $this->actingAs($host)->postJson('/broadcasting/auth', $payload)->assertOk()->assertJsonStructure(['auth', 'channel_data']);
        $this->actingAs($player)->postJson('/broadcasting/auth', $payload)->assertOk()->assertJsonStructure(['auth', 'channel_data']);
        $this->actingAs($outsider)->postJson('/broadcasting/auth', $payload)->assertForbidden();
    }

    public function test_room_update_event_does_not_expose_correct_answer(): void
    {
        $event = new RoomUpdated('ROOMCODE12', [
            'status' => 'active',
            'question' => ['id' => 1, 'prompt' => 'Pertanyaan?', 'options' => ['A', 'B'], 'sort_order' => 1],
        ]);

        $this->assertInstanceOf(ShouldBroadcastNow::class, $event);
        $this->assertInstanceOf(PresenceChannel::class, $event->broadcastOn());
        $this->assertSame('presence-rooms.ROOMCODE12', $event->broadcastOn()->name);
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
