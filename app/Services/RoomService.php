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
                        'seconds_per_question' => $quiz->seconds_per_question,
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

        DB::transaction(function () use ($room, $user): void {
            $room = Room::query()->whereKey($room->id)->lockForUpdate()->firstOrFail();
            $room->participants()->where('user_id', $user->id)->firstOrFail()->delete();
            $this->finishUntimedIfComplete($room);
        });

        $this->broadcastRoomUpdate($room->fresh());
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
                'question_ends_at' => $room->seconds_per_question ? now()->addSeconds($room->seconds_per_question) : null,
            ]);

            return $room->refresh();
        });

        $this->broadcastRoomUpdate($room);

        return $room;
    }

    public function answer(Room $room, User $user, array $data): RoomAnswer
    {
        $this->syncTimedRoom($room);

        $answer = DB::transaction(function () use ($room, $user, $data): RoomAnswer {
            $room = Room::query()->whereKey($room->id)->lockForUpdate()->firstOrFail();
            abort_unless($room->status === 'active', 422, 'Room belum aktif.');

            $participant = $room->participants()->where('user_id', $user->id)->firstOrFail();
            $existingAnswer = $participant->answers()->where('quiz_question_id', $data['question_id'])->first();
            if ($existingAnswer) {
                return $existingAnswer;
            }

            $question = $room->seconds_per_question
                ? $room->currentQuestion
                : $this->nextQuestionFor($room, $participant);
            if (! $question || (int) $data['question_id'] !== $question->id) {
                throw ValidationException::withMessages(['question_id' => 'Pertanyaan ini sudah tidak aktif.']);
            }
            if ($room->question_ends_at && now()->greaterThanOrEqualTo($room->question_ends_at)) {
                throw ValidationException::withMessages(['question_id' => 'Waktu soal ini sudah habis.']);
            }

            $answerIndex = (int) $data['answer_index'];

            if (! array_key_exists($answerIndex, $question->options)) {
                throw ValidationException::withMessages(['answer_index' => 'Opsi jawaban tidak tersedia.']);
            }

            $isCorrect = $answerIndex === $question->correct_option;
            $previousCorrectAnswers = RoomAnswer::query()
                ->where('quiz_question_id', $question->id)
                ->whereHas('participant', fn ($query) => $query->where('room_id', $room->id))
                ->where('is_correct', true)
                ->count();
            $answer = RoomAnswer::query()->firstOrCreate(
                ['room_participant_id' => $participant->id, 'quiz_question_id' => $question->id],
                ['answer_index' => $answerIndex, 'is_correct' => $isCorrect, 'points_awarded' => $isCorrect ? max(5, 10 - $previousCorrectAnswers) : 0],
            );

            if ($answer->wasRecentlyCreated && $answer->is_correct) {
                $participant->increment('score', $answer->points_awarded);
            }

            if (! $room->seconds_per_question) {
                $this->finishUntimedIfComplete($room);
            }

            return $answer;
        });

        if ($room->fresh()->status === 'finished') {
            $this->broadcastRoomUpdate($room->fresh());
        }

        return $answer;
    }

    public function syncTimedRoom(Room $room): void
    {
        $changed = DB::transaction(function () use ($room): bool {
            $room = Room::query()->whereKey($room->id)->lockForUpdate()->firstOrFail();
            if ($room->status !== 'active' || ! $room->seconds_per_question || ! $room->question_ends_at) {
                return false;
            }

            $changed = false;
            while ($room->status === 'active' && now()->greaterThanOrEqualTo($room->question_ends_at)) {
                $nextQuestion = $room->quiz->questions()
                    ->where('sort_order', '>', $room->currentQuestion->sort_order)
                    ->first();

                $room->update($nextQuestion
                    ? ['current_question_id' => $nextQuestion->id, 'question_ends_at' => $room->question_ends_at->copy()->addSeconds($room->seconds_per_question)]
                    : ['status' => 'finished', 'finished_at' => $room->question_ends_at, 'question_ends_at' => null]);
                $room->unsetRelation('currentQuestion');
                $changed = true;
            }

            return $changed;
        });

        if ($changed) {
            $this->broadcastRoomUpdate($room->fresh());
        }
    }

    /** @return array{room: Room, participant: RoomParticipant, activeQuestion: QuizQuestion|null, currentAnswer: RoomAnswer|null} */
    public function viewData(Room $room, User $user): array
    {
        abort_unless(
            $room->host_id === $user->id || $room->participants()->where('user_id', $user->id)->exists(),
            403,
        );

        $this->syncTimedRoom($room);
        $room->refresh();
        $room->load(['quiz.questions', 'currentQuestion', 'participants.user']);
        $participant = $room->participants->firstWhere('user_id', $user->id);
        $activeQuestion = $room->status === 'active'
            ? ($room->seconds_per_question ? $room->currentQuestion : $this->nextQuestionFor($room, $participant))
            : null;
        $currentAnswer = $room->seconds_per_question && $activeQuestion
            ? $participant->answers()->where('quiz_question_id', $activeQuestion->id)->first()
            : null;

        return compact('room', 'participant', 'activeQuestion', 'currentAnswer');
    }

    private function nextQuestionFor(Room $room, RoomParticipant $participant): ?QuizQuestion
    {
        return $room->quiz->questions()
            ->whereNotIn('id', $participant->answers()->select('quiz_question_id'))
            ->first();
    }

    private function finishUntimedIfComplete(Room $room): void
    {
        if ($room->status !== 'active' || $room->seconds_per_question) {
            return;
        }

        $participantCount = $room->participants()->count();
        $answerCount = RoomAnswer::query()->whereIn('room_participant_id', $room->participants()->select('id'))->count();

        if ($answerCount === $participantCount * $room->quiz->questions()->count()) {
            $room->update(['status' => 'finished', 'finished_at' => now()]);
        }
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
