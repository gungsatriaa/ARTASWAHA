<?php

namespace Tests\Feature;

use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\Quiz;
use App\Models\User;
use App\Services\RoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnonymousQuizParticipationTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_profile_can_join_answer_and_leave_without_login(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('avatars/komang.webp', 'avatar');
        $host = User::factory()->create();
        $quiz = Quiz::factory()->create(['user_id' => $host->id]);
        $question = $quiz->questions()->create([
            'prompt' => 'Apa jawabannya?',
            'options' => ['Salah', 'Benar'],
            'correct_option' => 1,
            'sort_order' => 1,
        ]);
        $room = app(RoomService::class)->create($quiz, $host);
        $profile = LearnerProfile::query()->create([
            'visitor_id' => (string) Str::uuid(),
            'learning_path_id' => LearningPath::factory()->create()->id,
            'role' => 'murid',
            'name' => 'Komang',
            'mode' => 'anonymous',
            'avatar' => 'avatars/komang.webp',
        ]);

        $this->get(route('quizzes.index'))->assertOk()->assertSee('buat profil anonim');
        $this->post(route('rooms.join'), ['code' => $room->code])->assertRedirect(route('learning.interview.show'));

        $this->withCookie('artaswaha_device', $profile->visitor_id)
            ->get(route('quizzes.index'))->assertOk()->assertDontSee('buat profil anonim');
        $this->post(route('rooms.join'), ['code' => $room->code])->assertRedirect(route('rooms.show', $room->code));
        $this->post(route('rooms.join'), ['code' => $room->code])->assertRedirect(route('rooms.show', $room->code));
        $this->assertDatabaseHas('room_participants', ['room_id' => $room->id, 'learner_profile_id' => $profile->id, 'user_id' => null]);
        $this->assertSame(2, $room->participants()->count());
        $avatarUrl = route('rooms.anonymous-avatar', [$room->code, $profile->id]);
        $this->get(route('rooms.show', $room->code))->assertOk()->assertSee('Komang')->assertSee($avatarUrl);
        $this->get($avatarUrl)->assertOk();
        $this->get(route('rooms.status', $room->code))->assertNoContent()->assertHeader('X-Room-Status', 'waiting');
        $this->post(route('rooms.start', $room->code))->assertRedirect(route('login'));

        app(RoomService::class)->start($room, $host);
        $this->get(route('rooms.show', $room->code))->assertSee('Apa jawabannya?');
        $this->post(route('rooms.answer', $room->code), ['question_id' => $question->id, 'answer_index' => 1])->assertRedirect();
        $this->assertDatabaseHas('room_answers', ['quiz_question_id' => $question->id, 'is_correct' => true]);
        $this->get(route('rooms.show', $room->code))->assertOk();
        $this->post(route('rooms.leave', $room->code))->assertRedirect(route('quizzes.index'));
        $this->assertDatabaseMissing('room_participants', ['room_id' => $room->id, 'learner_profile_id' => $profile->id]);
        $this->get($avatarUrl)->assertForbidden();
    }

    public function test_other_anonymous_devices_cannot_access_room_or_answer(): void
    {
        $host = User::factory()->create();
        $quiz = Quiz::factory()->create(['user_id' => $host->id]);
        $quiz->questions()->create(['prompt' => 'Soal?', 'options' => ['A', 'B'], 'correct_option' => 1, 'sort_order' => 1]);
        $room = app(RoomService::class)->create($quiz, $host);

        $this->withCookie('artaswaha_device', (string) Str::uuid())
            ->get(route('rooms.show', $room->code))->assertRedirect(route('learning.interview.show'));
        $this->get(route('rooms.status', $room->code))->assertForbidden();
        $this->post(route('rooms.answer', $room->code), ['question_id' => 1, 'answer_index' => 0])->assertForbidden();
    }
}
