<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizQuestion>
 */
class QuizQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'prompt' => fake()->sentence(),
            'options' => ['Pilihan A', 'Pilihan B', 'Pilihan C', 'Pilihan D'],
            'correct_option' => 0,
            'explanation' => null,
            'sort_order' => 1,
        ];
    }
}
