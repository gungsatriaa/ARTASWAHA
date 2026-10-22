<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\LearningProgressController;
use App\Http\Controllers\LessonController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LearningPathController::class, 'index'])->name('home');
Route::get('/belajar/{learningPath:slug}', [LearningPathController::class, 'show'])->name('learning.paths.show');
Route::get('/pelajaran/{lesson:slug}', [LessonController::class, 'show'])->name('learning.lessons.show');
Route::post('/pelajaran/{lesson:slug}/selesai', [LearningProgressController::class, 'store'])->name('learning.progress.store');

Route::middleware('guest')->group(function (): void {
    Route::get('/masuk', [AuthController::class, 'createLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/daftar', [AuthController::class, 'createRegistration'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/lupa-kata-sandi', [AuthController::class, 'createPasswordReset'])->name('password.request');
    Route::post('/lupa-kata-sandi', [AuthController::class, 'sendPasswordResetLink'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-kata-sandi/{token}', [AuthController::class, 'createPasswordResetForm'])->name('password.reset');
    Route::post('/reset-kata-sandi', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
});

Route::post('/keluar', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
