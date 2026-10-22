<?php

use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\LearningProgressController;
use App\Http\Controllers\LessonController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LearningPathController::class, 'index'])->name('home');
Route::get('/belajar/{learningPath:slug}', [LearningPathController::class, 'show'])->name('learning.paths.show');
Route::get('/pelajaran/{lesson:slug}', [LessonController::class, 'show'])->name('learning.lessons.show');
Route::post('/pelajaran/{lesson:slug}/selesai', [LearningProgressController::class, 'store'])->name('learning.progress.store');
