<?php

namespace Database\Factories;

use App\Models\LearningProgress;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LearningProgress>
 */
class LearningProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visitor_id' => (string) Str::uuid(),
            'lesson_id' => Lesson::factory(),
            'points' => 10,
            'completed_at' => now(),
        ];
    }
}
