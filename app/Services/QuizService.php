<?php

namespace App\Services;

use App\Models\ContentAudio;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class QuizService
{
    /** @param array{title: string, description?: string|null, leaderboard_enabled?: bool, show_answers_immediately?: bool, seconds_per_question?: int|null, time_limit_minutes?: int|null, questions: array<int, array{id?: int|null, prompt: string, options?: array<int, string>, correct_option?: int|null, question_type?: string, correct_options?: array<int, int>, accepted_answers?: array<int, string>, explanation?: string|null, keyboard_auto_open?: bool, time_limit_seconds?: int|null}>} $data */
    public function create(User $user, array $data): Quiz
    {
        $this->validateQuestions($data['questions']);

        return DB::transaction(function () use ($data, $user): Quiz {
            $quiz = $user->quizzes()->create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'leaderboard_enabled' => $data['leaderboard_enabled'] ?? true,
                'show_answers_immediately' => $data['show_answers_immediately'] ?? false,
                'seconds_per_question' => $data['seconds_per_question'] ?? null,
                'time_limit_seconds' => isset($data['time_limit_minutes']) ? $data['time_limit_minutes'] * 60 : null,
            ]);

            $this->saveQuestions($quiz, $data['questions']);

            return $quiz;
        });
    }

    /** @param array{title: string, description?: string|null, show_answers_immediately?: bool, seconds_per_question?: int|null, time_limit_minutes?: int|null, questions: array<int, array{id?: int|null, prompt: string, options?: array<int, string>, correct_option?: int|null, question_type?: string, correct_options?: array<int, int>, accepted_answers?: array<int, string>, explanation?: string|null, keyboard_auto_open?: bool, time_limit_seconds?: int|null}>} $data */
    public function update(Quiz $quiz, User $user, array $data): Quiz
    {
        $this->validateQuestions($data['questions']);

        $copiedPaths = [];
        try {
            return DB::transaction(function () use ($quiz, $user, $data, &$copiedPaths): Quiz {
                $quiz = Quiz::query()->whereKey($quiz->id)->lockForUpdate()->firstOrFail();
                abort_unless($quiz->user_id === $user->id && ! $quiz->is_archived, 403);
                $existing = $quiz->questions()->with('audio')->get()->keyBy('id');
                foreach ($data['questions'] as $index => $question) {
                    if (! empty($question['id']) && ! $existing->has($question['id'])) {
                        throw ValidationException::withMessages(["questions.{$index}.id" => 'Soal tidak termasuk kuis ini.']);
                    }
                }

                $attributes = [
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'show_answers_immediately' => $data['show_answers_immediately'] ?? false,
                    'seconds_per_question' => $data['seconds_per_question'] ?? null,
                    'time_limit_seconds' => isset($data['time_limit_minutes']) ? $data['time_limit_minutes'] * 60 : null,
                ];

                if ($quiz->rooms()->exists()) {
                    $updatedQuiz = $user->quizzes()->create([
                        ...$attributes,
                        'leaderboard_enabled' => $quiz->leaderboard_enabled,
                    ]);
                    $quiz->update(['is_archived' => true]);
                } else {
                    $quiz->update($attributes);
                    $updatedQuiz = $quiz;
                    $quiz->questions()->increment('sort_order', 20);
                    foreach ($existing as $question) {
                        $question->sort_order += 20;
                        $question->syncOriginalAttribute('sort_order');
                    }
                }

                $retainedIds = [];
                $sortOrder = 1;
                foreach ($data['questions'] as $index => $attributes) {
                    $source = $existing->get($attributes['id'] ?? null);
                    unset($attributes['id']);
                    $attributes = app(QuestionAnswerService::class)->attributes($attributes);
                    $attributes['keyboard_auto_open'] = (bool) ($attributes['keyboard_auto_open'] ?? false);
                    $attributes['explanation'] = $attributes['explanation'] ?? null;
                    $attributes['time_limit_seconds'] = $attributes['time_limit_seconds'] ?? null;
                    $attributes['sort_order'] = $sortOrder++;
                    if ($updatedQuiz->id === $quiz->id && $source !== null) {
                        $source->fill($attributes)->save();
                        if ($source->wasChanged(['prompt', 'options'])) {
                            $source->audio()->update(['status' => 'stale', 'reviewed_at' => null, 'version' => (string) Str::uuid()]);
                        }
                        $question = $source;
                    } else {
                        $question = $updatedQuiz->questions()->create($attributes);
                        if ($source !== null) {
                            $this->copyAudio($source, $question, $copiedPaths);
                        }
                    }
                    $retainedIds[] = $question->id;
                }
                if ($updatedQuiz->id === $quiz->id) {
                    $removed = $quiz->questions()->whereNotIn('id', $retainedIds);
                    $obsoleteAudio = ContentAudio::whereIn('quiz_question_id', (clone $removed)->select('id'))->whereNotNull('path')->pluck('path')->all();
                    $removed->delete();
                    DB::afterCommit(fn () => Storage::disk('local')->delete($obsoleteAudio));
                }

                return $updatedQuiz;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($copiedPaths);
            throw $exception;
        }
    }

    public function duplicate(Quiz $quiz, User $user): Quiz
    {
        $copiedPaths = [];
        try {
            return DB::transaction(function () use ($quiz, $user, &$copiedPaths): Quiz {
                $quiz = Quiz::query()->whereKey($quiz->id)->lockForUpdate()->firstOrFail();
                abort_unless($quiz->user_id === $user->id, 404);
                $copy = $user->quizzes()->create([
                    'title' => mb_substr($quiz->title, 0, 110).' (salinan)', 'description' => $quiz->description,
                    'seconds_per_question' => $quiz->seconds_per_question, 'leaderboard_enabled' => $quiz->leaderboard_enabled,
                    'show_answers_immediately' => $quiz->show_answers_immediately,
                    'time_limit_seconds' => $quiz->time_limit_seconds,
                ]);
                foreach ($quiz->questions()->with('audio')->get() as $source) {
                    $question = $copy->questions()->create($source->only(['question_type', 'correct_options', 'accepted_answers', 'prompt', 'options', 'correct_option', 'explanation', 'sort_order', 'keyboard_auto_open', 'time_limit_seconds']));
                    $this->copyAudio($source, $question, $copiedPaths);
                }

                return $copy;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($copiedPaths);
            throw $exception;
        }
    }

    /** @param array<int, string> $copiedPaths */
    private function copyAudio(QuizQuestion $source, QuizQuestion $destination, array &$copiedPaths): void
    {
        $audio = $source->audio;
        $disk = Storage::disk('local');
        if ($audio === null || ! in_array($audio->status, ['ready', 'review'], true) || $audio->path === null
            || ! str_starts_with($audio->path, 'content-audio/') || ! $disk->exists($audio->path)
            || $audio->source_hash !== app(ContentAudioService::class)->sourceHash($destination)) {
            return;
        }
        $path = 'content-audio/'.Str::uuid().'.'.pathinfo($audio->path, PATHINFO_EXTENSION);
        $copiedPaths[] = $path;
        if (! $disk->copy($audio->path, $path)) {
            throw new RuntimeException('Audio gagal disalin. Kuis belum disimpan; coba kembali.');
        }
        $copy = $audio->replicate(['quiz_question_id', 'lesson_id', 'generation_started_at', 'generation_finished_at']);
        $copy->fill(['quiz_question_id' => $destination->id, 'version' => (string) Str::uuid(), 'path' => $path])->save();
    }

    /** @param array<int, array{options?: array<int, string>, correct_option?: int|null, question_type?: string, correct_options?: array<int, int>, accepted_answers?: array<int, string>}> $questions */
    private function validateQuestions(array $questions): void
    {
        foreach ($questions as $index => $question) {
            Validator::make($question, app(QuestionAnswerService::class)->definitionRules($question))->validate();
        }
    }

    /** @param array<int, array{id?: int|null, prompt: string, options?: array<int, string>, correct_option?: int|null, question_type?: string, correct_options?: array<int, int>, accepted_answers?: array<int, string>, explanation?: string|null, keyboard_auto_open?: bool, time_limit_seconds?: int|null}> $questions */
    private function saveQuestions(Quiz $quiz, array $questions): void
    {
        $sortOrder = 1;

        foreach ($questions as $question) {
            $quiz->questions()->create([...app(QuestionAnswerService::class)->attributes($question), 'sort_order' => $sortOrder++]);
        }
    }
}
