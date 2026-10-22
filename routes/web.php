<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\LearningProgressController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\RoomController;
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

Route::middleware('auth')->group(function (): void {
    Route::get('/kuis', [QuizController::class, 'index'])->name('quizzes.index');
    Route::get('/kuis/buat', [QuizController::class, 'create'])->name('quizzes.create');
    Route::post('/kuis', [QuizController::class, 'store'])->middleware('throttle:10,1')->name('quizzes.store');
    Route::get('/kuis/{quiz}', [QuizController::class, 'show'])->name('quizzes.show');
    Route::post('/kuis/{quiz}/room', [RoomController::class, 'store'])->middleware('throttle:10,1')->name('rooms.store');
    Route::post('/room/gabung', [RoomController::class, 'join'])->middleware('throttle:10,1')->name('rooms.join');
    Route::get('/room/{room:code}', [RoomController::class, 'show'])->name('rooms.show');
    Route::post('/room/{room:code}/mulai', [RoomController::class, 'start'])->name('rooms.start');
    Route::post('/room/{room:code}/jawab', [RoomController::class, 'answer'])->middleware('throttle:30,1')->name('rooms.answer');
    Route::post('/room/{room:code}/selanjutnya', [RoomController::class, 'advance'])->name('rooms.advance');
});
