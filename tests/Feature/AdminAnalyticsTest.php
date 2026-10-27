<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_read_analytics_and_online_counts(): void
    {
        $this->get(route('admin.analytics.index'))->assertRedirect(route('login'));
        $this->get(route('admin.analytics.online'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create());
        $this->get(route('admin.analytics.index'))->assertForbidden();
        $this->get(route('admin.analytics.online'))->assertForbidden();
    }

    public function test_analytics_uses_stored_progress_and_zero_filled_daily_series(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $path = LearningPath::factory()->create(['title' => 'Cecimpedan']);
        $lesson = Lesson::factory()->create(['learning_path_id' => $path->id]);
        LearningProgress::factory()->create(['lesson_id' => $lesson->id, 'completed_at' => now()]);
        LearningProgress::factory()->create(['lesson_id' => $lesson->id, 'completed_at' => null]);

        $response = $this->actingAs($admin)->get(route('admin.analytics.index'));

        $response->assertOk()->assertInertiaContains('Analitik ARTASWAHA')->assertInertiaContains('Cecimpedan');
        $this->assertSame(1, $response->viewData('page')['props']['metrics']['completedLessons']);
        $this->assertSame(1, $response->viewData('page')['props']['metrics']['learners']);
        $this->assertCount(14, $response->viewData('page')['props']['chart']['labels']);
        $this->assertSame(1, $response->viewData('page')['props']['chart']['completions'][13]);
        $this->assertSame(0, $response->viewData('page')['props']['chart']['completions'][0]);
    }

    public function test_presence_counts_recent_unique_visitors_without_exposing_identity(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->post(route('presence.ping'))->assertOk();
        $this->assertDatabaseCount('active_visitors', 1);

        $this->actingAs($admin)->post(route('presence.ping'))->assertOk();
        $this->post(route('presence.ping'))->assertOk();

        DB::table('active_visitors')->insert([
            'visitor_key' => str_repeat('a', 64),
            'kind' => 'account',
            'last_seen_at' => now()->subMinutes(3),
        ]);
        $this->assertDatabaseCount('active_visitors', 3);

        $this->get(route('admin.analytics.online'))
            ->assertOk()
            ->assertExactJson(['total' => 2, 'accounts' => 1, 'anonymous' => 1])
            ->assertDontSee($admin->email);

        $this->assertSame(64, strlen(DB::table('active_visitors')->where('kind', 'account')->where('last_seen_at', '>=', now()->subMinute())->value('visitor_key')));
    }
}
