<?php

namespace Tests\Feature;

use Database\Seeders\LearningPathSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_new_visitors_are_directed_to_the_required_interview(): void
    {
        $this->seed(LearningPathSeeder::class);

        $response = $this->get('/');

        $response->assertRedirectToRoute('learning.interview.show');
    }
}
