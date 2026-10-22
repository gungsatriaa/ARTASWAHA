<?php

use App\Http\Controllers\Admin\LearningPathController as AdminLearningPathController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\LearningProgressController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ShowProfileAvatarController;
use App\Http\Controllers\UpdateProfileAvatarController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LearningPathController::class, 'index'])->name('home');
Route::get('/belajar/{learningPath:slug}', [LearningPathController::class, 'show'])->name('learning.paths.show');
Route::get('/pelajaran/{lesson:slug}', [LessonController::class, 'show'])->name('learning.lessons.show');
Route::post('/pelajaran/{lesson:slug}/selesai', [LearningProgressController::class, 'store'])->name('learning.progress.store');
Route::post('/pelajaran/{lesson:slug}/latihan', [LearningProgressController::class, 'answerExercise'])->middleware('throttle:15,1')->name('learning.exercises.answer');

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

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/', fn () => to_route('admin.lessons.index'))->name('index');

    Route::get('/jalur', [AdminLearningPathController::class, 'index'])->name('learning-paths.index');
    Route::get('/jalur/buat', [AdminLearningPathController::class, 'create'])->name('learning-paths.create');
    Route::post('/jalur', [AdminLearningPathController::class, 'store'])->name('learning-paths.store');
    Route::get('/jalur/{learningPath}/edit', [AdminLearningPathController::class, 'edit'])->name('learning-paths.edit');
    Route::put('/jalur/{learningPath}', [AdminLearningPathController::class, 'update'])->name('learning-paths.update');

    Route::get('/pelajaran', [AdminLessonController::class, 'index'])->name('lessons.index');
    Route::get('/pelajaran/buat', [AdminLessonController::class, 'create'])->name('lessons.create');
    Route::post('/pelajaran', [AdminLessonController::class, 'store'])->name('lessons.store');
    Route::get('/pelajaran/{lesson}/edit', [AdminLessonController::class, 'edit'])->name('lessons.edit');
    Route::put('/pelajaran/{lesson}', [AdminLessonController::class, 'update'])->name('lessons.update');
    Route::post('/pelajaran/{lesson}/terbitkan', [AdminLessonController::class, 'publish'])->name('lessons.publish');
    Route::post('/pelajaran/{lesson}/arsipkan', [AdminLessonController::class, 'archive'])->name('lessons.archive');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/profil', ProfileController::class)->name('profile.show');
    Route::get('/profil/tentang', [ProfileController::class, 'about'])->name('profile.about');
    Route::get('/profil/avatar', ShowProfileAvatarController::class)->name('profile.avatar.show');
    Route::put('/profil/avatar', UpdateProfileAvatarController::class)->middleware('throttle:10,1')->name('profile.avatar.update');
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
