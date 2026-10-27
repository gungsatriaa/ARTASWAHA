<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Models\LearnerProfile;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Services\ContentAudioService;
use App\Services\PagePresenter;
use App\Services\QuizService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Response as InertiaResponse;

class QuizController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', 'in:active,archived']]);
        $profile = Auth::guest() ? LearnerProfile::completedFor(null, request()->cookie('artaswaha_device')) : null;
        $quizzes = Quiz::query()
            ->where('user_id', Auth::id() ?? 0)
            ->where('is_archived', ($filters['status'] ?? 'active') === 'archived')
            ->when($filters['q'] ?? null, fn (Builder $query, string $text): Builder => $query->where('title', 'like', '%'.$text.'%'))
            ->withCount(['questions', 'rooms'])
            ->latest()
            ->paginate(12)->withQueryString();

        return app(PagePresenter::class)->render('quizzes.index', compact('quizzes', 'profile', 'filters'));
    }

    public function create(): InertiaResponse
    {
        return app(PagePresenter::class)->render('quizzes.create');
    }

    public function store(StoreQuizRequest $request, QuizService $quizService): RedirectResponse
    {
        $quiz = $quizService->create($request->user(), $request->validated());

        return to_route('quizzes.show', $quiz)->with('status', 'Kuis berhasil dibuat.');
    }

    public function show(Quiz $quiz, ContentAudioService $audioService): InertiaResponse
    {
        abort_unless($quiz->user_id === Auth::id(), 404);
        $quiz->load('questions.audio');
        $audioByQuestion = $quiz->questions->mapWithKeys(function (QuizQuestion $question) use ($audioService): array {
            $audio = $audioService->forContent($question);

            return $audioService->isPlayable($audio) ? [$question->id => $audio] : [];
        });

        return app(PagePresenter::class)->render('quizzes.show', compact('quiz', 'audioByQuestion'));
    }

    public function edit(Quiz $quiz): InertiaResponse
    {
        abort_unless($quiz->user_id === Auth::id() && ! $quiz->is_archived, 404);
        $quiz->load('questions.audio');
        $hasRooms = $quiz->rooms()->exists();

        return app(PagePresenter::class)->render('quizzes.create', compact('quiz', 'hasRooms'));
    }

    public function update(UpdateQuizRequest $request, Quiz $quiz, QuizService $quizService): RedirectResponse
    {
        $updatedQuiz = $quizService->update($quiz, $request->user(), $request->validated());

        return to_route('quizzes.show', $updatedQuiz)->with('status', $updatedQuiz->id !== $quiz->id
            ? 'Perubahan disimpan sebagai versi baru. Room lama tetap memakai versi sebelumnya.'
            : 'Kuis berhasil diperbarui.');
    }

    public function duplicate(Request $request, Quiz $quiz, QuizService $quizService): RedirectResponse
    {
        abort_unless($quiz->user_id === $request->user()->id, 404);
        $copy = $quizService->duplicate($quiz, $request->user());

        return to_route('quizzes.edit', $copy)->with('status', 'Salinan kuis dibuat. Keyboard dan audio yang masih berlaku ikut disalin; room dan jawaban tidak disalin.');
    }

    public function archive(Request $request, Quiz $quiz): RedirectResponse
    {
        abort_unless($quiz->user_id === $request->user()->id, 404);
        $quiz->update(['is_archived' => true]);

        return to_route('quizzes.index')->with('status', 'Kuis diarsipkan. Room dan jawaban yang sudah ada tetap tersimpan.');
    }
}
