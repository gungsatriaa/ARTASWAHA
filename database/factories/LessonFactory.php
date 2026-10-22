<?php

namespace Database\Factories;

use App\Models\LearningPath;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'learning_path_id' => LearningPath::factory(),
            'title' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(),
            'summary' => fake()->sentence(),
            'content' => fake()->paragraph(),
            'sort_order' => 1,
            'is_published' => true,
            'reviewed_at' => now(),
        ];
    }
}
