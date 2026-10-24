<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizService
{
    /** @param array{title: string, description?: string|null, leaderboard_enabled?: bool, show_answers_immediately?: bool, seconds_per_question?: int|null, questions: array<int, array{prompt: string, options: array<int, string>, correct_option: int, explanation?: string|null}>} $data */
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
            ]);

            $this->saveQuestions($quiz, $data['questions']);

            return $quiz;
        });
    }

    /** @param array{title: string, description?: string|null, show_answers_immediately?: bool, seconds_per_question?: int|null, questions: array<int, array{prompt: string, options: array<int, string>, correct_option: int, explanation?: string|null}>} $data */
    public function update(Quiz $quiz, User $user, array $data): Quiz
    {
        $this->validateQuestions($data['questions']);

        return DB::transaction(function () use ($quiz, $user, $data): Quiz {
            $quiz = Quiz::query()->whereKey($quiz->id)->lockForUpdate()->firstOrFail();
            abort_unless($quiz->user_id === $user->id && ! $quiz->is_archived, 403);

            $attributes = [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'show_answers_immediately' => $data['show_answers_immediately'] ?? false,
                'seconds_per_question' => $data['seconds_per_question'] ?? null,
            ];

            if ($quiz->rooms()->exists()) {
                $updatedQuiz = $user->quizzes()->create([
                    ...$attributes,
                    'leaderboard_enabled' => $quiz->leaderboard_enabled,
                ]);
                $quiz->update(['is_archived' => true]);
            } else {
                $quiz->update($attributes);
                $quiz->questions()->delete();
                $updatedQuiz = $quiz;
            }

            $this->saveQuestions($updatedQuiz, $data['questions']);

            return $updatedQuiz;
        });
    }

    /** @param array<int, array{options: array<int, string>, correct_option: int}> $questions */
    private function validateQuestions(array $questions): void
    {
        foreach ($questions as $index => $question) {
            if ($question['correct_option'] >= count($question['options'])) {
                throw ValidationException::withMessages([
                    "questions.{$index}.correct_option" => 'Pilih salah satu opsi jawaban yang tersedia.',
                ]);
            }
        }
    }

    /** @param array<int, array{prompt: string, options: array<int, string>, correct_option: int, explanation?: string|null}> $questions */
    private function saveQuestions(Quiz $quiz, array $questions): void
    {
        $sortOrder = 1;

        foreach ($questions as $question) {
            $quiz->questions()->create([...$question, 'sort_order' => $sortOrder++]);
        }
    }
}
