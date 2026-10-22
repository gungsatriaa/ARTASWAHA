<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLearningPathRequest;
use App\Http\Requests\UpdateLearningPathRequest;
use App\Models\LearningPath;
use App\Services\AdminLearningContentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class LearningPathController extends Controller
{
    public function index(AdminLearningContentService $contentService): View
    {
        $learningPaths = $contentService->learningPaths();

        return view('admin.learning-paths.index', compact('learningPaths'));
    }

    public function create(): View
    {
        return view('admin.learning-paths.form', ['learningPath' => new LearningPath]);
    }

    public function store(StoreLearningPathRequest $request, AdminLearningContentService $contentService): RedirectResponse
    {
        $contentService->storeLearningPath($request->validated());

        return to_route('admin.learning-paths.index')->with('status', 'Jalur belajar berhasil dibuat.');
    }

    public function edit(LearningPath $learningPath): View
    {
        return view('admin.learning-paths.form', compact('learningPath'));
    }

    public function update(UpdateLearningPathRequest $request, LearningPath $learningPath, AdminLearningContentService $contentService): RedirectResponse
    {
        $contentService->updateLearningPath($learningPath, $request->validated());

        return to_route('admin.learning-paths.index')->with('status', 'Jalur belajar berhasil diperbarui.');
    }
}
