<?php

namespace Tests\Feature;

use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_authentication_pages_share_mobile_navigation(): void
    {
        foreach ([route('login'), route('register'), route('password.request')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('aria-controls="mobile-navigation"', false)
                ->assertSee('data-mobile-nav', false);
        }
    }

    public function test_a_user_can_register_and_log_out(): void
    {
        $lesson = Lesson::factory()->create();
        $visitorId = (string) Str::uuid();
        LearningProgress::factory()->create([
            'visitor_id' => $visitorId,
            'lesson_id' => $lesson->id,
        ]);

        $this->withCookie('artaswaha_device', $visitorId)
            ->post(route('register.store'), [
                'name' => 'Putu',
                'email' => 'putu@example.test',
                'password' => 'safe-password',
                'password_confirmation' => 'safe-password',
            ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'putu@example.test']);
        $this->assertDatabaseHas('learning_progress', [
            'visitor_id' => $visitorId,
            'user_id' => auth()->id(),
        ]);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_login_rejects_bad_credentials_and_accepts_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'safe-password']);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'safe-password'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_reset_uses_laravels_secure_reset_broker(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);

        $token = Password::createToken($user);
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-safe-password',
            'password_confirmation' => 'new-safe-password',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-safe-password', $user->fresh()->password));
    }

    public function test_login_form_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), ['email' => 'missing@example.test', 'password' => 'wrong-password']);
        }

        $this->post(route('login.store'), ['email' => 'missing@example.test', 'password' => 'wrong-password'])
            ->assertTooManyRequests();
    }
}
