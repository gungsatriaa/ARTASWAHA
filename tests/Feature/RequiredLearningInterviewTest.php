<?php

namespace Tests\Feature;

use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RequiredLearningInterviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_visitors_must_finish_interview_before_accessing_content_or_registering(): void
    {
        $lesson = Lesson::factory()->create();
        foreach ([route('home'), route('learning.paths.index'), route('learning.paths.show', $lesson->learningPath->slug),
            route('learning.lessons.show', $lesson->slug), route('quizzes.index'), route('profile.show'), route('register')] as $url) {
            $this->get($url)->assertRedirectToRoute('learning.interview.show');
        }
        $this->post(route('learning.progress.store', $lesson->slug))->assertRedirectToRoute('learning.interview.show');
        $this->post(route('register.store'), ['name' => 'Made', 'email' => 'made@example.com', 'password' => 'password123', 'password_confirmation' => 'password123'])
            ->assertRedirectToRoute('learning.interview.show');
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('learning_progress', 0);
    }

    public function test_login_privacy_and_recovery_remain_accessible_before_interview(): void
    {
        foreach (['login', 'password.request', 'privacy-policy', 'learning.interview.show', 'robots', 'sitemap'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_anonymous_completion_restores_original_lesson_and_does_not_repeat_interview(): void
    {
        $lesson = Lesson::factory()->create();
        $path = $lesson->learningPath;
        $this->get(route('learning.lessons.show', $lesson->slug))->assertRedirectToRoute('learning.interview.show');
        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_ids' => [$path->id]]);
        $this->get(route('learning.interview.show'))->assertInertiaValue('step', 2);
        $this->post(route('learning.interview.store'), ['step' => 2, 'role' => 'murid']);
        $this->post(route('learning.interview.store'), ['step' => 3, 'mode' => 'anonymous']);
        $this->get(route('home'))->assertRedirectToRoute('learning.interview.show');
        $this->get(route('learning.interview.show'))->assertInertiaValue('step', 4);
        $this->post(route('learning.interview.store'), ['step' => 4, 'name' => 'Made'])
            ->assertRedirectToRoute('learning.lessons.show', $lesson->slug)->assertCookie('artaswaha_device');
        $profile = LearnerProfile::query()->sole();
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('home'))->assertOk();
        $this->get(route('learning.interview.show'))->assertRedirectToRoute('home');
    }

    public function test_account_choice_is_not_treated_as_completed_until_login_and_can_register(): void
    {
        $path = LearningPath::factory()->create();
        $this->get(route('quizzes.index'))->assertRedirectToRoute('learning.interview.show');
        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_ids' => [$path->id]]);
        $this->post(route('learning.interview.store'), ['step' => 2, 'role' => 'guru']);
        $this->post(route('learning.interview.store'), ['step' => 3, 'mode' => 'account'])->assertRedirectToRoute('login');
        $profile = LearnerProfile::query()->sole();
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('home'))->assertRedirectToRoute('login');
        $this->get(route('learning.interview.show'))->assertRedirectToRoute('login');
        $this->get(route('register'))->assertOk();
        $this->post(route('register.store'), ['name' => 'Made', 'email' => 'made@example.com', 'password' => 'password123', 'password_confirmation' => 'password123'])
            ->assertRedirectToRoute('quizzes.index');
        $this->assertAuthenticated();
        $this->assertSame(User::query()->sole()->id, $profile->fresh()->user_id);
    }

    public function test_existing_account_or_completed_anonymous_profile_can_open_platform_directly(): void
    {
        $path = LearningPath::factory()->create();
        $profile = LearnerProfile::create(['visitor_id' => (string) Str::uuid(), 'learning_path_id' => $path->id,
            'role' => 'murid', 'mode' => 'anonymous', 'name' => 'Made']);
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('home'))->assertOk();
        $this->get(route('quizzes.index'))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('home'))->assertOk();
    }
}
