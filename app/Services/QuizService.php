<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizService
{
    /** @param array{title: string, description?: string|null, leaderboard_enabled?: bool, questions: array<int, array{prompt: string, options: array<int, string>, correct_option: int, explanation?: string|null}>} $data */
    public function create(User $user, array $data): Quiz
    {
        foreach ($data['questions'] as $index => $question) {
            if ($question['correct_option'] >= count($question['options'])) {
                throw ValidationException::withMessages([
                    "questions.{$index}.correct_option" => 'Pilih salah satu opsi jawaban yang tersedia.',
                ]);
            }
        }

        return DB::transaction(function () use ($data, $user): Quiz {
            $quiz = $user->quizzes()->create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'leaderboard_enabled' => $data['leaderboard_enabled'] ?? true,
            ]);

            foreach ($data['questions'] as $index => $question) {
                $quiz->questions()->create([...$question, 'sort_order' => $index + 1]);
            }

            return $quiz;
        });
    }
}
