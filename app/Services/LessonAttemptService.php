<?php

namespace App\Services;

use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\LessonAttempt;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LessonAttemptService
{
    public function latest(Lesson $lesson, string $deviceId, ?User $user): ?LessonAttempt
    {
        return LessonAttempt::query()->forLearner($deviceId, $user?->id)->where('lesson_id', $lesson->id)
            ->where('version', $lesson->exerciseVersion())->latest('id')->first();
    }

    /** @return array<string, mixed>|null */
    public function presentation(Lesson $lesson, ?LearningProgress $progress, string $deviceId, ?User $user): ?array
    {
        $questions = $lesson->exerciseQuestions();
        if ($questions === []) {
            return null;
        }
        $attempt = $this->latest($lesson, $deviceId, $user);
        $completed = $attempt !== null ? $attempt->completed_at !== null : $progress?->completed_at !== null;
        $index = $completed ? count($questions) : count($attempt?->answers ?? []);
        $current = $questions[$index] ?? null;
        $history = LessonAttempt::query()->forLearner($deviceId, $user?->id)->where('lesson_id', $lesson->id)
            ->whereNotNull('completed_at')->latest('id')->limit(5)->get();

        return ['id' => $attempt?->id, 'total' => count($questions), 'index' => $index, 'completed' => $completed,
            'question' => $completed || $current === null ? null : array_intersect_key($current, array_flip(['exercise_prompt', 'exercise_options', 'question_type', 'keyboard_auto_open', 'section'])),
            'results' => $completed ? ($attempt?->answers ?? []) : [],
            'correct' => $completed ? collect($attempt?->answers ?? [])->where('is_correct', true)->count() : null,
            'history' => $history->map(fn (LessonAttempt $item): array => ['id' => $item->id, 'completedAt' => $item->completed_at->toIso8601String(),
                'total' => count($item->questions), 'correct' => collect($item->answers)->where('is_correct', true)->count()])->all()];
    }

    /** @return array{daily: array<string, array{total: int, correct: int}>, completed: int, modules: list<array<string, mixed>>} */
    public function activity(string $deviceId, ?int $userId, Carbon $start): array
    {
        $daily = [];
        $modules = [];
        $completed = 0;
        foreach (LessonAttempt::query()->forLearner($deviceId, $userId)->where('updated_at', '>=', $start)
            ->select(['id', 'lesson_id', 'answers', 'completed_at'])->with('lesson:id,title')->lazyById(100) as $attempt) {
            $correct = 0;
            foreach ($attempt->answers as $answer) {
                $at = Carbon::parse($answer['answered_at']);
                $correct += (int) $answer['is_correct'];
                if ($at->lt($start) || $at->gt(now())) {
                    continue;
                }
                $day = $at->toDateString();
                $daily[$day] ??= ['total' => 0, 'correct' => 0];
                $daily[$day]['total']++;
                $daily[$day]['correct'] += (int) $answer['is_correct'];
            }
            if ($attempt->completed_at === null || $attempt->completed_at->lt($start) || $attempt->completed_at->gt(now())) {
                continue;
            }
            $completed++;
            $accuracy = (int) round($correct / max(1, count($attempt->answers)) * 100);
            $modules[$attempt->lesson_id] ??= ['title' => $attempt->lesson->title, 'attempts' => 0, 'first_accuracy' => $accuracy];
            $modules[$attempt->lesson_id]['attempts']++;
            $modules[$attempt->lesson_id]['latest_accuracy'] = $accuracy;
        }

        return ['daily' => $daily, 'completed' => $completed,
            'modules' => collect($modules)->sortBy('latest_accuracy')->take(10)->values()->all()];
    }

    /** @param array<string, mixed> $data */
    public function answer(Lesson $lesson, string $deviceId, ?User $user, array $data): void
    {
        DB::transaction(function () use ($lesson, $deviceId, $user, $data): void {
            $lesson = Lesson::query()->lockForUpdate()->findOrFail($lesson->id);
            $questions = $lesson->exerciseQuestions();
            $attempt = $this->latest($lesson, $deviceId, $user);
            abort_if($attempt?->completed_at !== null, 409, 'Percobaan sudah selesai. Pilih ulang kuis untuk mulai lagi.');
            abort_if(isset($data['attempt_id']) && $attempt?->id !== (int) $data['attempt_id'], 409, 'Percobaan berubah. Muat ulang halaman.');
            $index = count($attempt?->answers ?? []);
            abort_unless((int) ($data['question_index'] ?? 0) === $index && isset($questions[$index]), 409, 'Soal berubah. Muat ulang halaman.');
            $question = new Lesson($questions[$index]);
            $grader = app(QuestionAnswerService::class);
            $correct = $grader->isCorrect($question, $data);
            $selected = match ($question->question_type) {
                'essay' => $data['answer_text'],
                'multiple' => implode(', ', array_map(fn ($item): string => $question->exercise_options[(int) $item], $data['answer_indexes'])),
                default => $question->exercise_options[(int) $data['answer_index']],
            };
            $attempt ??= LessonAttempt::create(['lesson_id' => $lesson->id, 'user_id' => $user?->id, 'visitor_id' => $deviceId,
                'version' => $lesson->exerciseVersion(), 'questions' => $questions, 'answers' => []]);
            $answers = [...$attempt->answers, ['prompt' => $question->exercise_prompt, 'type' => $question->question_type,
                'selected' => $selected, 'correct' => $grader->correctText($question), 'is_correct' => $correct,
                'explanation' => $question->exercise_explanation, 'answered_at' => now()->toIso8601String()]];
            $finished = count($answers) === count($questions);
            $attempt->update(['answers' => $answers, 'completed_at' => $finished ? now() : null]);
            if ($finished) {
                app(LearningProgressService::class)->completeLesson($deviceId, $lesson, $user);
            }
        });
    }

    public function retry(Lesson $lesson, string $deviceId, ?User $user, ?int $attemptId): void
    {
        DB::transaction(function () use ($lesson, $deviceId, $user, $attemptId): void {
            $lesson = Lesson::query()->lockForUpdate()->findOrFail($lesson->id);
            $previous = $this->latest($lesson, $deviceId, $user);
            abort_unless($previous?->id === $attemptId && ($previous?->completed_at !== null || ($previous === null && $lesson->progress()->forLearner($deviceId, $user?->id)->whereNotNull('completed_at')->exists())), 409, 'Selesaikan percobaan sebelum mengulang kuis.');
            LessonAttempt::create(['lesson_id' => $lesson->id, 'user_id' => $user?->id, 'visitor_id' => $deviceId,
                'version' => $lesson->exerciseVersion(), 'questions' => $lesson->exerciseQuestions(), 'answers' => []]);
        });
    }
}
