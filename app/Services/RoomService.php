<?php

namespace App\Services;

use App\Events\RoomUpdated;
use App\Models\LearnerProfile;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Room;
use App\Models\RoomAnswer;
use App\Models\RoomParticipant;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RoomService
{
    public function create(Quiz $quiz, User $host): Room
    {
        abort_unless($quiz->user_id === $host->id, 403);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            try {
                $room = DB::transaction(function () use ($quiz, $host): Room {
                    $quiz = Quiz::query()->whereKey($quiz->id)->lockForUpdate()->firstOrFail();
                    abort_if($quiz->is_archived, 422, 'Versi kuis ini sudah diarsipkan. Pilih versi terbaru.');
                    abort_if($quiz->questions()->doesntExist(), 422, 'Tambahkan setidaknya satu pertanyaan terlebih dahulu.');

                    $room = Room::query()->create([
                        'quiz_id' => $quiz->id,
                        'host_id' => $host->id,
                        'code' => sprintf('%06d', random_int(0, 999999)),
                        'leaderboard_enabled' => $quiz->leaderboard_enabled,
                        'seconds_per_question' => $quiz->seconds_per_question,
                        'time_limit_seconds' => $quiz->time_limit_seconds,
                        'host_last_seen_at' => now(),
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

    public function join(string $code, User|LearnerProfile $player): Room
    {
        $this->deactivateIfHostAbsent(Room::query()->where('code', Str::upper(trim($code)))->firstOrFail());

        $room = DB::transaction(function () use ($code, $player): Room {
            $room = Room::query()->where('code', Str::upper(trim($code)))->lockForUpdate()->firstOrFail();
            if ($room->status !== 'waiting') {
                throw ValidationException::withMessages(match ($room->status) {
                    'active' => ['room_started' => 'Kuis sudah dimulai. Kamu tidak bisa bergabung lagi.'],
                    'inactive' => ['room_inactive' => 'Host sudah keluar. Room ini tidak aktif lagi.'],
                    default => ['room_finished' => 'Kuis sudah selesai. Kamu tidak bisa bergabung lagi.'],
                });
            }

            $room->participants()->firstOrCreate(
                $this->participantKey($player),
                ['display_name' => $player->name],
            );

            return $room;
        });

        $this->broadcastRoomUpdate($room);

        return $room;
    }

    public function leave(Room $room, User|LearnerProfile $player): void
    {
        $changed = DB::transaction(function () use ($room, $player): bool {
            $room = Room::query()->whereKey($room->id)->lockForUpdate()->firstOrFail();
            $isHost = $player instanceof User && $room->host_id === $player->id;
            if (! $isHost) {
                $room->participants()->where($this->participantKey($player))->firstOrFail();
            }
            if ($this->finishIfTimeLimitReached($room)) {
                return true;
            }
            if ($room->status === 'finished') {
                return false;
            }
            if ($player instanceof User && $room->host_id === $player->id) {
                if (! in_array($room->status, ['waiting', 'active'], true)) {
                    return false;
                }

                $room->update(['status' => 'inactive', 'question_ends_at' => null]);

                return true;
            }

            $room->participants()->where($this->participantKey($player))->firstOrFail()->delete();
            if ($room->hasQuestionTimers()) {
                $this->finishTimedIfComplete($room);
            } else {
                $this->finishUntimedIfComplete($room);
            }

            return true;
        });

        if ($changed) {
            $this->broadcastRoomUpdate($room->fresh());
        }
    }

    public function heartbeat(Room $room, User $host): Room
    {
        abort_unless($room->host_id === $host->id, 403);
        $this->deactivateIfHostAbsent($room);

        return DB::transaction(function () use ($room): Room {
            $room = Room::query()->whereKey($room->id)->lockForUpdate()->firstOrFail();
            if ($room->status === 'finished') {
                return $room;
            }
            abort_unless(in_array($room->status, ['waiting', 'active'], true), 410, 'Room sudah tidak aktif.');
            $room->update(['host_last_seen_at' => now()]);

            return $room;
        });
    }

    public function deactivateIfHostAbsent(Room $room): void
    {
        $changed = DB::transaction(function () use ($room): bool {
            $lockedRoom = Room::query()->whereKey($room->id)->lockForUpdate()->firstOrFail();
            if ($this->finishIfTimeLimitReached($lockedRoom)) {
                return true;
            }
            if (! in_array($lockedRoom->status, ['waiting', 'active'], true)
                || ($lockedRoom->host_last_seen_at && $lockedRoom->host_last_seen_at->greaterThan(now()->subSeconds(90)))) {
                return false;
            }

            $lockedRoom->update(['status' => 'inactive', 'question_ends_at' => null]);

            return true;
        });

        if ($changed) {
            $this->broadcastRoomUpdate($room->fresh());
        }
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
        $this->deactivateIfHostAbsent($room);

        $room = DB::transaction(function () use ($room, $user): Room {
            $room = Room::query()->whereKey($room->id)->lockForUpdate()->firstOrFail();
            abort_unless($room->host_id === $user->id, 403);
            abort_unless($room->status === 'waiting', 422, 'Room sudah dimulai atau berakhir.');

            $question = $room->quiz->questions()->first();
            abort_if($question === null, 422, 'Kuis ini belum memiliki pertanyaan.');
            $started = now();
            $seconds = $question->time_limit_seconds ?? $room->seconds_per_question;

            $room->update([
                'status' => 'active',
                'current_question_id' => $question->id,
                'started_at' => $started,
                'ends_at' => $room->time_limit_seconds ? $started->copy()->addSeconds($room->time_limit_seconds) : null,
                'question_ends_at' => $seconds ? $started->copy()->addSeconds($seconds) : null,
            ]);

            if ($room->hasQuestionTimers()) {
                $room->participants()->update([
                    'question_position' => 0,
                    'question_ends_at' => $room->question_ends_at,
                ]);
            }

            return $room->refresh();
        });

        $this->broadcastRoomUpdate($room);

        return $room;
    }

    public function answer(Room $room, User|LearnerProfile $player, array $data): RoomAnswer
    {
        $this->syncTimedRoom($room);

        $answer = DB::transaction(function () use ($room, $player, $data): RoomAnswer {
            $room = Room::query()->whereKey($room->id)->lockForUpdate()->firstOrFail();
            abort_unless($room->status === 'active', 422, 'Room belum aktif.');
            abort_if($room->ends_at && now()->greaterThanOrEqualTo($room->ends_at), 422, 'Waktu kuis sudah habis.');

            $participant = $room->participants()->where($this->participantKey($player))->firstOrFail();
            $existingAnswer = $participant->answers()->where('quiz_question_id', $data['question_id'])->first();
            if ($existingAnswer) {
                return $existingAnswer;
            }

            $question = $room->hasQuestionTimers()
                ? $room->quiz->questions->get($participant->question_position)
                : $this->nextQuestionFor($room, $participant);
            if (! $question || (int) $data['question_id'] !== $question->id) {
                throw ValidationException::withMessages(['question_id' => 'Pertanyaan ini sudah tidak aktif.']);
            }
            if ($participant->question_ends_at && now()->greaterThanOrEqualTo($participant->question_ends_at)) {
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

            if ($room->hasQuestionTimers()) {
                $nextPosition = $participant->question_position + 1;
                $nextQuestion = $room->quiz->questions->get($nextPosition);
                $seconds = $nextQuestion ? ($nextQuestion->time_limit_seconds ?? $room->seconds_per_question) : null;
                $participant->update([
                    'question_position' => $nextPosition,
                    'question_ends_at' => $seconds !== null
                        ? now()->addSeconds($seconds + ($room->quiz->show_answers_immediately ? 3 : 0))
                        : null,
                ]);
                $this->finishTimedIfComplete($room);
            } else {
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
        $this->deactivateIfHostAbsent($room);

        $changed = DB::transaction(function () use ($room): bool {
            $room = Room::query()->whereKey($room->id)->lockForUpdate()->firstOrFail();
            if ($this->finishIfTimeLimitReached($room)) {
                return true;
            }
            if ($room->status !== 'active' || ! $room->hasQuestionTimers()) {
                return false;
            }

            $questions = $room->quiz->questions;
            $questionCount = $questions->count();
            $firstSeconds = $questions->first()?->time_limit_seconds ?? $room->seconds_per_question;
            $changed = false;
            foreach ($room->participants as $participant) {
                if ($participant->question_ends_at === null && $participant->question_position === 0 && $firstSeconds !== null) {
                    $participant->question_position = max(0, $questions->search(fn ($question) => $question->id === $room->current_question_id));
                    $participant->question_ends_at = $room->question_ends_at ?? $room->started_at?->copy()->addSeconds($firstSeconds);
                    $changed = true;
                }

                while ($participant->question_position < $questionCount && $participant->question_ends_at && now()->greaterThanOrEqualTo($participant->question_ends_at)) {
                    $participant->question_position++;
                    $next = $questions->get($participant->question_position);
                    $seconds = $next ? ($next->time_limit_seconds ?? $room->seconds_per_question) : null;
                    $participant->question_ends_at = $seconds !== null ? $participant->question_ends_at->copy()->addSeconds($seconds) : null;
                    $changed = true;
                }

                if ($participant->isDirty()) {
                    $participant->save();
                }
            }

            $this->finishTimedIfComplete($room);

            return $changed || $room->status === 'finished';
        });

        if ($changed) {
            $this->broadcastRoomUpdate($room->fresh());
        }
    }

    /** @return array{room: Room, participant: RoomParticipant|null, activeQuestion: QuizQuestion|null, currentAnswer: RoomAnswer|null, isFeedback: bool, answersByQuestion: Collection} */
    public function viewData(Room $room, User|LearnerProfile $player): array
    {
        abort_unless(
            ($player instanceof User && $room->host_id === $player->id) || $room->participants()->where($this->participantKey($player))->exists(),
            403,
        );

        $this->syncTimedRoom($room);
        $room->refresh();
        $room->load(['quiz.questions', 'currentQuestion', 'participants.user', 'participants.learnerProfile']);
        $participant = $room->participants->first(fn (RoomParticipant $participant): bool => $participant->getAttribute(key($this->participantKey($player))) === $player->id);
        $feedbackAnswer = $participant && $room->quiz->show_answers_immediately && ! ($room->ends_at && now()->greaterThanOrEqualTo($room->ends_at))
            ? $participant->answers()->whereKey(request()->query('feedback'))->first()
            : null;
        $isFeedback = $feedbackAnswer !== null;
        $activeQuestion = null;
        if ($feedbackAnswer) {
            $activeQuestion = $room->quiz->questions->firstWhere('id', $feedbackAnswer->quiz_question_id);
        } elseif ($room->status === 'active' && $participant) {
            $activeQuestion = $room->hasQuestionTimers()
                ? $room->quiz->questions->get($participant->question_position)
                : $this->nextQuestionFor($room, $participant);
        }
        $currentAnswer = $feedbackAnswer;
        $answersByQuestion = $room->status === 'finished' && $participant
            ? $participant->answers()->get()->keyBy('quiz_question_id')
            : collect();

        return compact('room', 'participant', 'activeQuestion', 'currentAnswer', 'isFeedback', 'answersByQuestion');
    }

    /** @return array{user_id: int}|array{learner_profile_id: int} */
    public function participantKey(User|LearnerProfile $player): array
    {
        return $player instanceof User ? ['user_id' => $player->id] : ['learner_profile_id' => $player->id];
    }

    private function nextQuestionFor(Room $room, RoomParticipant $participant): ?QuizQuestion
    {
        return $room->quiz->questions()
            ->whereNotIn('id', $participant->answers()->select('quiz_question_id'))
            ->first();
    }

    private function finishIfTimeLimitReached(Room $room): bool
    {
        if ($room->status !== 'active' || $room->ends_at === null || now()->lessThan($room->ends_at)) {
            return false;
        }
        $room->update(['status' => 'finished', 'finished_at' => $room->ends_at, 'question_ends_at' => null]);
        $room->participants()->update(['question_ends_at' => null]);

        return true;
    }

    private function finishUntimedIfComplete(Room $room): void
    {
        if ($room->status !== 'active' || $room->hasQuestionTimers()) {
            return;
        }

        $participantCount = $room->participants()->count();
        $answerCount = RoomAnswer::query()->whereIn('room_participant_id', $room->participants()->select('id'))->count();

        if ($answerCount === $participantCount * $room->quiz->questions()->count()) {
            $room->update(['status' => 'finished', 'finished_at' => now()]);
        }
    }

    private function finishTimedIfComplete(Room $room): void
    {
        if ($room->status === 'active' && $room->participants()->where('question_position', '<', $room->quiz->questions()->count())->doesntExist()) {
            $room->update(['status' => 'finished', 'finished_at' => now(), 'question_ends_at' => null]);
        }
    }

    private function broadcastRoomUpdate(Room $room): void
    {
        $room->load('currentQuestion');
        $question = $room->status === 'active' && ! $room->hasQuestionTimers() ? $room->currentQuestion : null;

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
