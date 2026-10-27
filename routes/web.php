<?php

use App\Http\Controllers\Admin\AiController as AdminAiController;
use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\ContentAudioController as AdminContentAudioController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LearningPathController as AdminLearningPathController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContentAudioController;
use App\Http\Controllers\DestroyProfileController;
use App\Http\Controllers\LearnerAnalysisController;
use App\Http\Controllers\LearningInterviewController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\LearningProgressController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizDraftController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ShowProfileAvatarController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\UpdateProfileAvatarController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LearningPathController::class, 'index'])->name('home');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/kebijakan-privasi', PrivacyPolicyController::class)->name('privacy-policy');
Route::post('/presence', PresenceController::class)->middleware('throttle:30,1')->name('presence.ping');
Route::get('/mulai-belajar', [LearningInterviewController::class, 'show'])->name('learning.interview.show');
Route::post('/mulai-belajar', [LearningInterviewController::class, 'store'])->middleware('throttle:20,1')->name('learning.interview.store');
Route::get('/mulai-belajar/avatar', [LearningInterviewController::class, 'avatar'])->name('learning.interview.avatar');
Route::redirect('/profil-anonim', '/profil');
Route::redirect('/profil-anonim/tentang', '/profil/tentang');
Route::get('/profil', ProfileController::class)->name('profile.show');
Route::delete('/profil', DestroyProfileController::class)->middleware('throttle:5,1')->name('profile.destroy');
Route::get('/audio/{contentAudio}', ContentAudioController::class)->name('content-audio.show');
Route::get('/profil/analisis', [LearnerAnalysisController::class, 'show'])->middleware('throttle:learner-analysis-read')->name('profile.analysis.show');
Route::post('/profil/analisis', [LearnerAnalysisController::class, 'store'])->middleware('throttle:learner-analysis-write')->name('profile.analysis.store');
Route::get('/profil/tentang', [ProfileController::class, 'about'])->name('profile.about');
Route::get('/profil/avatar', ShowProfileAvatarController::class)->name('profile.avatar.show');
Route::put('/profil/avatar', UpdateProfileAvatarController::class)->middleware('throttle:10,1')->name('profile.avatar.update');
Route::get('/belajar', [LearningPathController::class, 'catalog'])->name('learning.paths.index');
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

Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->middleware(['guest', 'throttle:10,1'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->middleware('throttle:10,1')->name('auth.google.callback');

Route::post('/keluar', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('index');
    Route::get('/analitik', [AdminAnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/analitik/online', [AdminAnalyticsController::class, 'online'])->name('analytics.online');
    Route::get('/ai', [AdminAiController::class, 'index'])->name('ai.index');
    Route::post('/ai/interview/tes-semua', [AdminAiController::class, 'testAll'])->middleware('throttle:admin-ai-write')->name('ai.profiles.test-all');
    Route::get('/ai/interview/{learnerProfile}', [AdminAiController::class, 'profile'])->name('ai.profiles.show');
    Route::post('/ai/interview/{learnerProfile}', [AdminAiController::class, 'retry'])->middleware('throttle:admin-ai-write')->name('ai.profiles.retry');
    Route::get('/ai/draf/{quizDraft}', [AdminAiController::class, 'draft'])->name('ai.drafts.show');
    Route::post('/ai/laporan', [AdminAiController::class, 'requestReport'])->middleware('throttle:admin-ai-write')->name('ai.reports.store');
    Route::get('/ai/laporan/{aiReport}', [AdminAiController::class, 'report'])->name('ai.reports.show');
    Route::get('/audio', [AdminContentAudioController::class, 'index'])->name('audio.index');
    Route::get('/audio/{contentAudio}/status', [AdminContentAudioController::class, 'status'])->middleware('throttle:60,1')->name('audio.status');
    Route::get('/audio/modul/{lesson}', [AdminContentAudioController::class, 'lesson'])->name('audio.lessons.edit');
    Route::post('/audio/modul/{lesson}', [AdminContentAudioController::class, 'saveLesson'])->middleware('throttle:admin-ai-write')->name('audio.lessons.store');
    Route::get('/audio/soal/{question}', [AdminContentAudioController::class, 'question'])->name('audio.questions.edit');
    Route::post('/audio/soal/{question}', [AdminContentAudioController::class, 'saveQuestion'])->middleware('throttle:admin-ai-write')->name('audio.questions.store');
    Route::post('/audio/{contentAudio}/setujui', [AdminContentAudioController::class, 'approve'])->name('audio.approve');

    Route::get('/jalur', [AdminLearningPathController::class, 'index'])->name('learning-paths.index');
    Route::get('/jalur/buat', [AdminLearningPathController::class, 'create'])->name('learning-paths.create');
    Route::post('/jalur', [AdminLearningPathController::class, 'store'])->name('learning-paths.store');
    Route::get('/jalur/{learningPath}/edit', [AdminLearningPathController::class, 'edit'])->name('learning-paths.edit');
    Route::put('/jalur/{learningPath}', [AdminLearningPathController::class, 'update'])->name('learning-paths.update');

    Route::get('/pelajaran', [AdminLessonController::class, 'index'])->name('lessons.index');
    Route::get('/pelajaran/buat', [AdminLessonController::class, 'create'])->name('lessons.create');
    Route::post('/pelajaran', [AdminLessonController::class, 'store'])->name('lessons.store');
    Route::get('/pelajaran/{lesson}/edit', [AdminLessonController::class, 'edit'])->name('lessons.edit');
    Route::get('/pelajaran/{lesson}/preview', [AdminLessonController::class, 'preview'])->name('lessons.preview');
    Route::put('/pelajaran/{lesson}', [AdminLessonController::class, 'update'])->name('lessons.update');
    Route::post('/pelajaran/{lesson}/terbitkan', [AdminLessonController::class, 'publish'])->name('lessons.publish');
    Route::post('/pelajaran/{lesson}/arsipkan', [AdminLessonController::class, 'archive'])->name('lessons.archive');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/kuis/buat', [QuizController::class, 'create'])->name('quizzes.create');
    Route::post('/kuis/draf-ai', [QuizDraftController::class, 'store'])->middleware('throttle:5,1')->name('quizzes.drafts.store');
    Route::get('/kuis/draf-ai/{quizDraft}', [QuizDraftController::class, 'show'])->name('quizzes.drafts.show');
    Route::post('/kuis', [QuizController::class, 'store'])->middleware('throttle:10,1')->name('quizzes.store');
    Route::get('/kuis/{quiz}', [QuizController::class, 'show'])->name('quizzes.show');
    Route::get('/kuis/{quiz}/edit', [QuizController::class, 'edit'])->name('quizzes.edit');
    Route::put('/kuis/{quiz}', [QuizController::class, 'update'])->name('quizzes.update');
    Route::post('/kuis/{quiz}/salin', [QuizController::class, 'duplicate'])->middleware('throttle:10,1')->name('quizzes.duplicate');
    Route::post('/kuis/{quiz}/arsipkan', [QuizController::class, 'archive'])->name('quizzes.archive');
    Route::post('/kuis/{quiz}/room', [RoomController::class, 'store'])->middleware('throttle:10,1')->name('rooms.store');
    Route::post('/room/{room:code}/heartbeat', [RoomController::class, 'heartbeat'])->middleware('throttle:10,1')->name('rooms.heartbeat');
    Route::delete('/room/{room:code}', [RoomController::class, 'destroy'])->name('rooms.destroy');
    Route::post('/room/{room:code}/mulai', [RoomController::class, 'start'])->name('rooms.start');
});

Route::get('/kuis', [QuizController::class, 'index'])->name('quizzes.index');
Route::post('/room/gabung', [RoomController::class, 'join'])->middleware('throttle:10,1')->name('rooms.join');
Route::get('/room/{room:code}/status', [RoomController::class, 'status'])->name('rooms.status');
Route::get('/room/{room:code}/avatar/{user}', [ShowProfileAvatarController::class, 'forRoom'])->name('rooms.participant-avatar');
Route::get('/room/{room:code}/avatar-anonim/{learnerProfile}', [ShowProfileAvatarController::class, 'forAnonymousRoom'])->name('rooms.anonymous-avatar');
Route::get('/room/{room:code}', [RoomController::class, 'show'])->name('rooms.show');
Route::post('/room/{room:code}/keluar', [RoomController::class, 'leave'])->name('rooms.leave');
Route::post('/room/{room:code}/jawab', [RoomController::class, 'answer'])->middleware('throttle:30,1')->name('rooms.answer');
