<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SendPasswordResetLinkRequest;
use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\User;
use App\Services\AuthService;
use App\Services\LearningProgressService;
use App\Services\PagePresenter;
use App\Services\ReactPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Throwable;

class AuthController extends Controller
{
    public function createLogin(): ReactPage
    {
        return app(PagePresenter::class)->render('auth.login');
    }

    public function createRegistration(Request $request, AuthService $authService, LearningProgressService $progressService): ReactPage|RedirectResponse
    {
        $pending = $request->session()->pull('google_registration');
        if (is_array($pending)) {
            if (($pending['expires_at'] ?? 0) <= now()->timestamp) {
                return to_route('login')->withErrors(['google' => 'Konfirmasi Google kedaluwarsa. Silakan masuk dengan Google kembali.']);
            }
            $googleUser = (new GoogleUser)->map($pending)->setRaw(['verified_email' => true]);
            if (! $authService->loginWithGoogle($googleUser, $request, $progressService)) {
                return to_route('login')->withErrors(['google' => 'Akun Google tidak dapat ditautkan. Silakan masuk kembali.']);
            }

            return $this->learningDestination($request)->with('status', 'Akun Google berhasil dibuat. Selamat belajar!');
        }

        return app(PagePresenter::class)->render('auth.register');
    }

    public function register(RegisterRequest $request, AuthService $authService, LearningProgressService $progressService): RedirectResponse
    {
        $authService->register($request->validated(), $request, $progressService);

        return $this->learningDestination($request)->with('status', 'Akun berhasil dibuat. Selamat belajar!');
    }

    public function login(LoginRequest $request, AuthService $authService, LearningProgressService $progressService): RedirectResponse
    {
        if (! $authService->login($request->validated(), $request, $progressService)) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak cocok.'])->onlyInput('email');
        }

        return $this->learningDestination($request);
    }

    public function redirectToGoogle(): RedirectResponse
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret') || ! config('services.google.redirect')) {
            return to_route('login')->withErrors(['google' => 'Login Google belum dikonfigurasi.']);
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request, AuthService $authService, LearningProgressService $progressService): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            report($exception);

            return to_route('login')->withErrors(['google' => 'Login Google gagal. Silakan coba lagi.']);
        }

        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();
        $verified = is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL)
            && is_string($googleId) && $googleId !== '' && ($googleUser->user['verified_email'] ?? false) === true;
        $existingAccount = $verified && User::query()->where('google_id', $googleId)->orWhere('email', $email)->exists();
        $deviceId = $request->cookie('artaswaha_device');
        $hasInterview = is_string($deviceId) && LearnerProfile::query()->where('visitor_id', $deviceId)
            ->whereNull('user_id')->whereIn('mode', ['account', 'anonymous'])->exists();
        if ($verified && ! $existingAccount && ! $hasInterview) {
            $request->session()->put('google_registration', [
                'id' => $googleId, 'email' => $email, 'name' => $googleUser->getName(), 'expires_at' => now()->addHour()->timestamp,
            ]);
            $answers = $request->session()->get('learning_interview', []);
            $request->session()->put('learning_interview', ['mode' => 'account', ...array_intersect_key($answers, array_flip(['learning_path_ids', 'role']))]);

            return to_route('learning.interview.show', ['step' => 2]);
        }

        if (! $authService->loginWithGoogle($googleUser, $request, $progressService)) {
            return to_route('login')->withErrors(['google' => 'Akun Google tidak memiliki email terverifikasi atau tidak dapat ditautkan.']);
        }

        return $this->learningDestination($request)->with('status', 'Berhasil masuk dengan Google. Selamat belajar!');
    }

    public function logout(Request $request, AuthService $authService): RedirectResponse
    {
        $authService->logout($request);

        return to_route('home');
    }

    public function createPasswordReset(): ReactPage
    {
        return app(PagePresenter::class)->render('auth.forgot-password');
    }

    public function sendPasswordResetLink(SendPasswordResetLinkRequest $request, AuthService $authService): RedirectResponse
    {
        $authService->sendPasswordResetLink($request->validated());

        return back()->with('status', 'Jika email terdaftar, tautan pemulihan akan dikirim.');
    }

    public function createPasswordResetForm(string $token): ReactPage
    {
        return app(PagePresenter::class)->render('auth.reset-password', compact('token'));
    }

    public function resetPassword(ResetPasswordRequest $request, AuthService $authService): RedirectResponse
    {
        $status = $authService->resetPassword($request->validated());

        return $status === Password::PASSWORD_RESET
            ? to_route('login')->with('status', 'Kata sandi berhasil diperbarui. Silakan masuk.')
            : back()->withErrors(['email' => 'Tautan pemulihan tidak valid atau sudah kedaluwarsa.']);
    }

    private function learningDestination(Request $request): RedirectResponse
    {
        $pathId = $request->session()->pull('learning_interview_destination');
        $path = is_numeric($pathId) ? LearningPath::query()->where('is_published', true)->find($pathId) : null;

        return redirect()->intended($path ? route('learning.paths.show', $path->slug) : route('home'));
    }
}
