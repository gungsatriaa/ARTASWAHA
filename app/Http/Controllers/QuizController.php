<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Models\Quiz;
use App\Services\QuizService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(): View
    {
        $quizzes = Quiz::query()
            ->where('user_id', Auth::id())
            ->where('is_archived', false)
            ->withCount('questions')
            ->latest()
            ->paginate(12);

        return view('quizzes.index', compact('quizzes'));
    }

    public function create(): View
    {
        return view('quizzes.create');
    }

    public function store(StoreQuizRequest $request, QuizService $quizService): RedirectResponse
    {
        $quiz = $quizService->create($request->user(), $request->validated());

        return to_route('quizzes.show', $quiz)->with('status', 'Kuis berhasil dibuat.');
    }

    public function show(Quiz $quiz): View
    {
        abort_unless($quiz->user_id === Auth::id(), 404);
        $quiz->load('questions');

        return view('quizzes.show', compact('quiz'));
    }

    public function edit(Quiz $quiz): View
    {
        abort_unless($quiz->user_id === Auth::id() && ! $quiz->is_archived, 404);
        $quiz->load('questions');
        $hasRooms = $quiz->rooms()->exists();

        return view('quizzes.create', compact('quiz', 'hasRooms'));
    }

    public function update(UpdateQuizRequest $request, Quiz $quiz, QuizService $quizService): RedirectResponse
    {
        $updatedQuiz = $quizService->update($quiz, $request->user(), $request->validated());

        return to_route('quizzes.show', $updatedQuiz)->with('status', $updatedQuiz->id !== $quiz->id
            ? 'Perubahan disimpan sebagai versi baru. Room lama tetap memakai versi sebelumnya.'
            : 'Kuis berhasil diperbarui.');
    }
}
