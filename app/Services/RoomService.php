<?php

namespace App\Services;

use App\Events\RoomUpdated;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Room;
use App\Models\RoomAnswer;
use App\Models\RoomParticipant;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RoomService
{
    public function create(Quiz $quiz, User $host): Room
    {
        abort_unless($quiz->user_id === $host->id, 403);
        abort_if($quiz->questions()->doesntExist(), 422, 'Tambahkan setidaknya satu pertanyaan terlebih dahulu.');

        for ($attempt = 0; $attempt < 10; $attempt++) {
            try {
                $room = DB::transaction(function () use ($quiz, $host): Room {
                    $room = Room::query()->create([
                        'quiz_id' => $quiz->id,
                        'host_id' => $host->id,
                        'code' => Str::upper(Str::random(4)),
                        'leaderboard_enabled' => $quiz->leaderboard_enabled,
                    ]);

                    $room->participants()->create([
                        'user_id' => $host->id,
                        'display_name' => $host->name,
                    ]);

                    return $room;
                });

                break;
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === 9) {
                    throw $exception;
                }
            }
        }

        $this->broadcastRoomUpdate($room);

        return $room;
    }

    public function join(string $code, User $user): Room
    {
        $room = DB::transaction(function () use ($code, $user): Room {
            $room = Room::query()->where('code', Str::upper(trim($code)))->lockForUpdate()->firstOrFail();
            abort_unless($room->status === 'waiting', 422, 'Room tidak menerima peserta baru.');

            $room->participants()->firstOrCreate(
                ['user_id' => $user->id],
                ['display_name' => $user->name],
            );

            return $room;
        });

        $this->broadcastRoomUpdate($room);

        return $room;
    }

    public function leave(Room $room, User $user): void
    {
        abort_unless($room->host_id !== $user->id, 403, 'Host tidak dapat keluar dari room.');

        $participant = $room->participants()->where('user_id', $user->id)->firstOrFail();
        $participant->delete();

        $this->broadcastRoomUpdate($room);
    }

    public function destroy(Room $room, User $host): void
    {
        DB::transaction(function () use ($room, $host): void {
            $room = Room::query()->whereKey($room->id)->lockForUpdate()->firstOrFail();
            abort_unless($room->host_id === $host->id, 403);

            $room->delete();
        });

        event(new RoomUpdated($room->code, ['status' => 'deleted', 'question' => null]));
    }

    public function start(Room $room, User $user): Room
    {
        $room = DB::transaction(function () use ($room, $user): Room {
            $room = Room::query()->whereKey($room->id)->lockForUpdate()->firstOrFail();
            abort_unless($room->host_id === $user->id, 403);
            abort_unless($room->status === 'waiting', 422, 'Room sudah dimulai atau berakhir.');

            $question = $room->quiz->questions()->first();
            abort_if($question === null, 422, 'Kuis ini belum memiliki pertanyaan.');

            $room->update([
                'status' => 'active',
                'current_question_id' => $question->id,
                'started_at' => now(),
            ]);

            return $room->refresh();
        });

        $this->broadcastRoomUpdate($room);

        return $room;
    }

    public function answer(Room $room, User $user, array $data): RoomAnswer
    {
        $answer = DB::transaction(function () use ($room, $user, $data): RoomAnswer {
            $room = Room::query()->whereKey($room->id)->lockForUpdate()->firstOrFail();
            abort_unless($room->status === 'active', 422, 'Room belum aktif.');

            $participant = $room->participants()->where('user_id', $user->id)->firstOrFail();
            abort_unless((int) $data['question_id'] === $room->current_question_id, 422, 'Pertanyaan ini sudah tidak aktif.');

            $question = QuizQuestion::query()
                ->where('quiz_id', $room->quiz_id)
                ->whereKey($room->current_question_id)
                ->firstOrFail();
            $answerIndex = (int) $data['answer_index'];

            if (! array_key_exists($answerIndex, $question->options)) {
                throw ValidationException::withMessages(['answer_index' => 'Opsi jawaban tidak tersedia.']);
            }

            $isCorrect = $answerIndex === $question->correct_option;
            $answer = RoomAnswer::query()->firstOrCreate(
                ['room_participant_id' => $participant->id, 'quiz_question_id' => $question->id],
                ['answer_index' => $answerIndex, 'is_correct' => $isCorrect, 'points_awarded' => $isCorrect ? 10 : 0],
            );

            if ($answer->wasRecentlyCreated && $answer->is_correct) {
                $participant->increment('score', $answer->points_awarded);
            }

            return $answer;
        });

        $this->broadcastRoomUpdate($room);

        return $answer;
    }

    public function advance(Room $room, User $user): Room
    {
        $room = DB::transaction(function () use ($room, $user): Room {
            $room = Room::query()->whereKey($room->id)->lockForUpdate()->firstOrFail();
            abort_unless($room->host_id === $user->id, 403);
            abort_unless($room->status === 'active', 422, 'Room tidak sedang berjalan.');

            $currentQuestion = $room->currentQuestion;
            abort_if($currentQuestion === null, 422, 'Room tidak memiliki pertanyaan aktif.');
            $nextQuestion = $room->quiz->questions()
                ->where('sort_order', '>', $currentQuestion->sort_order)
                ->first();

            $room->update($nextQuestion
                ? ['current_question_id' => $nextQuestion->id]
                : ['status' => 'finished', 'finished_at' => now()]);

            return $room->refresh();
        });

        $this->broadcastRoomUpdate($room);

        return $room;
    }

    /** @return array{room: Room, participant: RoomParticipant, currentAnswer: RoomAnswer|null} */
    public function viewData(Room $room, User $user): array
    {
        abort_unless(
            $room->host_id === $user->id || $room->participants()->where('user_id', $user->id)->exists(),
            403,
        );

        $room->load(['quiz.questions', 'currentQuestion', 'participants.user']);
        $participant = $room->participants->firstWhere('user_id', $user->id);
        $currentAnswer = $room->current_question_id
            ? $participant->answers()->where('quiz_question_id', $room->current_question_id)->first()
            : null;

        return compact('room', 'participant', 'currentAnswer');
    }

    private function broadcastRoomUpdate(Room $room): void
    {
        $room->load('currentQuestion');
        $question = $room->currentQuestion;

        event(new RoomUpdated($room->code, [
            'status' => $room->status,
            'question' => $question ? [
                'id' => $question->id,
                'prompt' => $question->prompt,
                'options' => $question->options,
                'sort_order' => $question->sort_order,
            ] : null,
        ]));
    }
}
