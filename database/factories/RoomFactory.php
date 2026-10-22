<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'code' => Str::upper(Str::random(10)),
            'status' => 'waiting',
            'leaderboard_enabled' => true,
        ];
    }
}
