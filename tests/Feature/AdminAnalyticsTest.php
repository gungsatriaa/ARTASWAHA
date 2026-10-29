<?php

namespace Tests\Feature;

use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\RoomAnswer;
use App\Models\User;
use App\Services\AdminAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
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

        $response->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->missing('statistics')
            ->loadDeferredProps(fn (Assert $reload): Assert => $reload
                ->where('statistics.metrics.completedLessons', 1)
                ->where('statistics.metrics.learners', 1)
                ->has('statistics.chart.labels', 14)
                ->where('statistics.chart.completions.13', 1)
                ->where('statistics.chart.completions.0', 0)
                ->where('statistics.topPaths.0.title', 'Cecimpedan')));
    }

    public function test_weekly_comparisons_and_accuracy_use_real_recorded_activity_and_empty_days_stay_null(): void
    {
        $this->freezeTime();
        LearningProgress::factory()->count(2)->create(['completed_at' => now()]);
        LearningProgress::factory()->create(['completed_at' => now()->subDays(7)]);
        LearningProgress::factory()->create(['completed_at' => now()->subDays(14)]);
        LearningProgress::factory()->create(['completed_at' => null]);
        RoomAnswer::factory()->create(['answered_at' => now(), 'is_correct' => true]);
        RoomAnswer::factory()->create(['answered_at' => now(), 'is_correct' => false]);
        RoomAnswer::factory()->create(['answered_at' => now()->subDays(7), 'is_correct' => false]);

        $snapshot = app(AdminAnalyticsService::class)->snapshot();
        $this->assertSame(['current' => 2, 'previous' => 1], $snapshot['weekly']['completions']);
        $this->assertSame(['current' => 2, 'previous' => 1], $snapshot['weekly']['answers']);
        $this->assertSame(50.0, $snapshot['chart']['accuracy'][13]);
        $this->assertNull($snapshot['chart']['accuracy'][0]);
        $this->assertSame(0.0, $snapshot['chart']['accuracy'][6]);
        $this->assertSame(4, $snapshot['metrics']['completedLessons']);
    }

    public function test_learning_identities_are_deduplicated_across_account_devices_and_delayed_jobs_are_counted(): void
    {
        $user = User::factory()->create();
        LearningProgress::factory()->count(2)->create(['user_id' => $user->id]);
        $visitor = (string) Str::uuid();
        LearningProgress::factory()->count(2)->create(['visitor_id' => $visitor]);
        $path = LearningPath::factory()->create();
        foreach (['queued', 'processing', 'completed', 'failed'] as $index => $status) {
            $profile = LearnerProfile::create(['visitor_id' => (string) Str::uuid(), 'learning_path_id' => $path->id, 'name' => 'Pelajar', 'role' => 'murid', 'mode' => 'anonymous']);
            $profile->forceFill(['analysis_status' => $status, 'analysis_requested_at' => $index === 0 ? now()->subMinutes(11) : now()])->save();
        }
        $claimed = LearnerProfile::create(['visitor_id' => (string) Str::uuid(), 'user_id' => $user->id, 'learning_path_id' => $path->id, 'name' => 'Akun', 'role' => 'guru', 'mode' => 'anonymous']);

        $snapshot = app(AdminAnalyticsService::class)->snapshot();
        $this->assertSame(2, $snapshot['metrics']['learners']);
        $this->assertSame(4, $snapshot['metrics']['anonymousProfiles']);
        $this->assertSame(2, $snapshot['metrics']['pendingAnalysis']);
        $this->assertSame(1, $snapshot['metrics']['delayedAnalysis']);
        $this->assertSame(1, $snapshot['metrics']['failedAnalysis']);
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('admin.index'))
            ->assertOk()->assertInertiaValue('overview.metrics.delayedAnalysis', 1)
            ->assertInertiaValue('overview.metrics.learners', 2)->assertInertiaNotContains($claimed->visitor_id);
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
