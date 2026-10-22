<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SendPasswordResetLinkRequest;
use App\Services\AuthService;
use App\Services\LearningProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function createLogin(): View
    {
        return view('auth.login');
    }

    public function createRegistration(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request, AuthService $authService, LearningProgressService $progressService): RedirectResponse
    {
        $authService->register($request->validated(), $request, $progressService);

        return to_route('home')->with('status', 'Akun berhasil dibuat. Selamat belajar!');
    }

    public function login(LoginRequest $request, AuthService $authService, LearningProgressService $progressService): RedirectResponse
    {
        if (! $authService->login($request->validated(), $request, $progressService)) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak cocok.'])->onlyInput('email');
        }

        return to_route('home');
    }

    public function logout(Request $request, AuthService $authService): RedirectResponse
    {
        $authService->logout($request);

        return to_route('home');
    }

    public function createPasswordReset(): View
    {
        return view('auth.forgot-password');
    }

    public function sendPasswordResetLink(SendPasswordResetLinkRequest $request, AuthService $authService): RedirectResponse
    {
        $authService->sendPasswordResetLink($request->validated());

        return back()->with('status', 'Jika email terdaftar, tautan pemulihan akan dikirim.');
    }

    public function createPasswordResetForm(string $token): View
    {
        return view('auth.reset-password', compact('token'));
    }

    public function resetPassword(ResetPasswordRequest $request, AuthService $authService): RedirectResponse
    {
        $status = $authService->resetPassword($request->validated());

        return $status === Password::PASSWORD_RESET
            ? to_route('login')->with('status', 'Kata sandi berhasil diperbarui. Silakan masuk.')
            : back()->withErrors(['email' => 'Tautan pemulihan tidak valid atau sudah kedaluwarsa.']);
    }
}
