<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

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
