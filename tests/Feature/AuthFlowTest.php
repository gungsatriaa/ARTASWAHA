<?php

namespace Tests\Feature;

use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\AssertablePage;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_registration_use_their_dedicated_layout(): void
    {
        $path = LearningPath::factory()->create();
        $this->post(route('learning.interview.store'), ['step' => 1, 'mode' => 'account']);
        $this->post(route('learning.interview.store'), ['step' => 2, 'learning_path_ids' => [$path->id]]);
        $this->post(route('learning.interview.store'), ['step' => 3, 'role' => 'murid']);
        $profile = LearnerProfile::query()->sole();
        $this->withCookie('artaswaha_device', $profile->visitor_id);
        foreach ([route('login'), route('register')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertReactContains(route('auth.google.redirect'));
        }

        $this->get(route('password.request'))
            ->assertOk()
            ->assertReact(fn (AssertablePage $page) => $page->etc());
    }

    public function test_a_user_can_register_and_log_out(): void
    {
        $lesson = Lesson::factory()->create();
        $visitorId = (string) Str::uuid();
        LearnerProfile::create(['visitor_id' => $visitorId, 'learning_path_id' => $lesson->learning_path_id, 'role' => 'murid', 'mode' => 'anonymous', 'name' => 'Putu']);
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
            ])->assertRedirect(route('home'))->assertCookie(Auth::guard()->getRecallerName());

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'putu@example.test']);
        $this->assertDatabaseHas('learning_progress', [
            'visitor_id' => $visitorId,
            'user_id' => auth()->id(),
        ]);
        $this->withSession(['status' => 'Akun berhasil dibuat. Selamat belajar!'])
            ->get(route('home'))
            ->assertOk()
            ->assertReactContains('Akun berhasil dibuat. Selamat belajar!')
            ->assertReact(fn (AssertablePage $page) => $page->etc());

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_login_rejects_bad_credentials_and_accepts_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'safe-password']);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'safe-password', 'remember' => true])
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_email_login_restores_the_account_after_session_expiry_and_logout_revokes_it(): void
    {
        $user = User::factory()->create(['password' => 'safe-password']);
        $response = $this->post(route('login.store'), ['email' => $user->email, 'password' => 'safe-password']);
        $cookieName = Auth::guard()->getRecallerName();
        $response->assertRedirectToRoute('home')->assertCookie($cookieName);
        $cookie = $response->getCookie($cookieName);
        $this->assertGreaterThan(now()->addDays(30)->timestamp, $cookie->getExpiresTime());
        $this->assertTrue($cookie->isHttpOnly());

        $this->flushSession();
        Auth::forgetGuards();
        $this->withCookie($cookieName, $cookie->getValue())->get(route('home'))
            ->assertOk()->assertReactValue('viewer.name', $user->name);
        $this->assertTrue(Auth::viaRemember());

        $this->post(route('logout'))->assertCookieExpired($cookieName);
        $this->flushSession();
        Auth::forgetGuards();
        $this->withCookie($cookieName, $cookie->getValue())->get(route('home'))
            ->assertRedirectToRoute('learning.interview.show');
        $this->assertGuest();
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

    public function test_google_oauth_can_create_a_verified_account_and_sign_in(): void
    {
        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
        Socialite::fake('google', GoogleUser::fake([
            'id' => 'google-user-123',
            'name' => 'Putu Bali',
            'email' => 'putu@gmail.com',
            'verified_email' => true,
        ]));

        $this->get(route('auth.google.redirect'))->assertRedirect();
        $this->get(route('auth.google.callback'))->assertRedirectToRoute('learning.interview.show', ['step' => 2]);
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $path = LearningPath::factory()->create(['is_published' => true]);
        $this->post(route('learning.interview.store'), ['step' => 2, 'learning_path_ids' => [$path->id]]);
        $this->post(route('learning.interview.store'), ['step' => 3, 'role' => 'murid'])->assertRedirectToRoute('register');
        $profile = LearnerProfile::query()->sole();
        $response = $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('register'))
            ->assertRedirect(route('learning.paths.show', $path->slug));
        $cookieName = Auth::guard()->getRecallerName();
        $response->assertCookie($cookieName);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Putu Bali',
            'email' => 'putu@gmail.com',
            'google_id' => 'google-user-123',
        ]);
        $this->assertNotNull(User::query()->where('email', 'putu@gmail.com')->firstOrFail()->email_verified_at);
        $this->flushSession();
        Auth::forgetGuards();
        $this->withCookie($cookieName, $response->getCookie($cookieName)->getValue())->get(route('home'))
            ->assertOk()->assertReactValue('viewer.name', 'Putu Bali');
        $this->assertTrue(Auth::viaRemember());
    }

    public function test_existing_email_account_can_login_without_answering_interview(): void
    {
        $user = User::factory()->create(['password' => 'safe-password']);
        $this->withSession(['learning_interview' => ['mode' => 'account']])->get(route('login'))->assertOk();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'safe-password'])
            ->assertRedirectToRoute('home')->assertSessionMissing('learning_interview');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('learner_profiles', 0);
        $this->get(route('home'))->assertOk();
    }

    public function test_registration_link_starts_questions_and_then_opens_registration_form(): void
    {
        $path = LearningPath::factory()->create(['is_published' => true]);
        $this->get(route('register'))->assertRedirectToRoute('learning.interview.show', ['step' => 2]);
        $this->get(route('learning.interview.show', ['step' => 2]))->assertReactValue('step', 2);
        $this->post(route('learning.interview.store'), ['step' => 2, 'learning_path_ids' => [$path->id]]);
        $this->post(route('learning.interview.store'), ['step' => 3, 'role' => 'guru'])->assertRedirectToRoute('register');
        $profile = LearnerProfile::query()->sole();
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('register'))->assertOk();
        $this->assertGuest();
    }

    public function test_expired_google_registration_requires_google_again_without_repeating_answers(): void
    {
        Socialite::fake('google', GoogleUser::fake(['id' => 'new-google', 'email' => 'new@gmail.com', 'verified_email' => true]));
        $this->get(route('auth.google.callback'))->assertRedirectToRoute('learning.interview.show', ['step' => 2]);
        $this->travel(61)->minutes();
        $path = LearningPath::factory()->create(['is_published' => true]);
        $this->post(route('learning.interview.store'), ['step' => 2, 'learning_path_ids' => [$path->id]]);
        $this->post(route('learning.interview.store'), ['step' => 3, 'role' => 'murid']);
        $profile = LearnerProfile::query()->sole();
        $this->withCookie('artaswaha_device', $profile->visitor_id)->get(route('register'))
            ->assertRedirectToRoute('login')->assertSessionHasErrors('google');
        $this->assertDatabaseCount('users', 0);
        $this->get(route('auth.google.callback'))->assertRedirect(route('learning.paths.show', $path->slug));
        $this->assertAuthenticated();
        $this->assertSame([$path->id], $profile->fresh()->learning_path_ids);
    }

    public function test_google_oauth_links_a_verified_email_to_an_existing_account(): void
    {
        $user = User::factory()->create(['email' => 'putu@gmail.com']);
        Socialite::fake('google', GoogleUser::fake([
            'id' => 'google-user-123',
            'name' => 'Different Google Name',
            'email' => 'putu@gmail.com',
            'verified_email' => true,
        ]));

        $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'google_id' => 'google-user-123',
        ]);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_google_oauth_rejects_an_unverified_email(): void
    {
        Socialite::fake('google', GoogleUser::fake([
            'id' => 'google-user-123',
            'email' => 'putu@gmail.com',
            'verified_email' => false,
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_google_oauth_does_not_link_an_email_to_a_different_google_account(): void
    {
        User::factory()->create([
            'email' => 'putu@gmail.com',
            'google_id' => 'another-google-user',
        ]);
        Socialite::fake('google', GoogleUser::fake([
            'id' => 'google-user-123',
            'email' => 'putu@gmail.com',
            'verified_email' => true,
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_google_oauth_redirect_requires_provider_configuration(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
            'services.google.redirect' => null,
        ]);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
    }
}
