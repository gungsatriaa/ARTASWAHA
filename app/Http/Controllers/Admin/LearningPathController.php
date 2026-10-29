<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLearningPathRequest;
use App\Http\Requests\UpdateLearningPathRequest;
use App\Models\LearningPath;
use App\Services\AdminLearningContentService;
use App\Services\PagePresenter;
use App\Services\ReactPage;
use Illuminate\Http\RedirectResponse;

class LearningPathController extends Controller
{
    public function index(AdminLearningContentService $contentService): ReactPage
    {
        $learningPaths = $contentService->learningPaths();

        return app(PagePresenter::class)->render('admin.learning-paths.index', compact('learningPaths'));
    }

    public function create(): ReactPage
    {
        return app(PagePresenter::class)->render('admin.learning-paths.form', ['learningPath' => new LearningPath]);
    }

    public function store(StoreLearningPathRequest $request, AdminLearningContentService $contentService): RedirectResponse
    {
        $contentService->storeLearningPath($request->validated());

        return to_route('admin.learning-paths.index')->with('status', 'Jalur belajar berhasil dibuat.');
    }

    public function edit(LearningPath $learningPath): ReactPage
    {
        return app(PagePresenter::class)->render('admin.learning-paths.form', compact('learningPath'));
    }

    public function update(UpdateLearningPathRequest $request, LearningPath $learningPath, AdminLearningContentService $contentService): RedirectResponse
    {
        $contentService->updateLearningPath($learningPath, $request->validated());

        return to_route('admin.learning-paths.index')->with('status', 'Jalur belajar berhasil diperbarui.');
    }
}
