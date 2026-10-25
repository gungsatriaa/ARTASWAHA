<?php

namespace Database\Factories;

use App\Models\AiReport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AiReport> */
class AiReportFactory extends Factory
{
    public function definition(): array
    {
        return ['period_start' => now()->subDays(13)->toDateString(), 'period_end' => now()->toDateString(), 'input_hash' => hash('sha256', (string) Str::uuid()), 'snapshot' => ['new_interviews' => 0], 'status' => 'unavailable'];
    }
}
