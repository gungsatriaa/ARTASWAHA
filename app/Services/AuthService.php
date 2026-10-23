<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\User as GoogleUser;

class AuthService
{
    /** @param array{name: string, email: string, password: string} $data */
    public function register(array $data, Request $request, LearningProgressService $progressService): User
    {
        $user = User::query()->create($data);
        Auth::login($user);
        $request->session()->regenerate();
        $progressService->claimDeviceProgress($request, $user);

        return $user;
    }

    /** @param array{email: string, password: string, remember?: bool} $credentials */
    public function login(array $credentials, Request $request, LearningProgressService $progressService): bool
    {
        if (! Auth::attempt($credentials, $credentials['remember'] ?? false)) {
            return false;
        }

        $request->session()->regenerate();
        $progressService->claimDeviceProgress($request, Auth::user());

        return true;
    }

    public function loginWithGoogle(GoogleUser $googleUser, Request $request, LearningProgressService $progressService): bool
    {
        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();

        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ($googleUser->user['verified_email'] ?? false) !== true || ! is_string($googleId) || $googleId === '') {
            return false;
        }

        $user = User::query()->where('google_id', $googleId)->first()
            ?? User::query()->where('email', $email)->first();

        if ($user?->google_id !== null && $user->google_id !== $googleId) {
            return false;
        }

        if (! $user) {
            $name = $googleUser->getName();
            $user = new User;
            $user->forceFill([
                'name' => is_string($name) && trim($name) !== '' ? trim($name) : Str::before($email, '@'),
                'email' => $email,
                'password' => Str::random(64),
            ]);
        }

        $user->forceFill([
            'google_id' => $googleId,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();
        $progressService->claimDeviceProgress($request, $user);

        return true;
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /** @param array{email: string} $data */
    public function sendPasswordResetLink(array $data): string
    {
        return Password::sendResetLink($data);
    }

    /** @param array{token: string, email: string, password: string, password_confirmation: string} $data */
    public function resetPassword(array $data): string
    {
        return Password::reset($data, function (User $user, string $password): void {
            $user->forceFill(['password' => $password]);
            $user->setRememberToken(Str::random(60));
            $user->save();

            event(new PasswordReset($user));
        });
    }
}
