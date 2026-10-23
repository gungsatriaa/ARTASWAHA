<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuizDraftRequest;
use App\Jobs\GenerateQuizDraft;
use App\Models\QuizDraft;
use Illuminate\Http\JsonResponse;

class QuizDraftController extends Controller
{
    public function store(StoreQuizDraftRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $path = $file->store('quiz-drafts/'.$request->user()->id, 'local');
        $draft = QuizDraft::create(['user_id' => $request->user()->id, 'source_path' => $path]);
        GenerateQuizDraft::dispatch($draft->id);

        return response()->json(['id' => $draft->id, 'status_url' => route('quizzes.drafts.show', $draft)], 202);
    }

    public function show(QuizDraft $quizDraft): JsonResponse
    {
        abort_unless($quizDraft->user_id === auth()->id(), 404);

        return response()->json($quizDraft->only(['status', 'source_text', 'questions', 'error']));
    }
}
