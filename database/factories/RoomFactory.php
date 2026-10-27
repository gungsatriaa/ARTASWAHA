<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
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
            'host_id' => User::factory(),
            'code' => fake()->unique()->numerify('######'),
            'status' => 'waiting',
            'host_last_seen_at' => now(),
            'leaderboard_enabled' => true,
        ];
    }
}
