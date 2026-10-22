<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminLessonRequest;
use App\Http\Requests\UpdateAdminLessonRequest;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Services\AdminLearningContentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class LessonController extends Controller
{
    public function index(AdminLearningContentService $contentService): View
    {
        $lessons = $contentService->lessons();

        return view('admin.lessons.index', compact('lessons'));
    }

    public function create(): View
    {
        $learningPaths = LearningPath::query()->orderBy('group')->orderBy('sort_order')->get(['id', 'title', 'group']);

        return view('admin.lessons.form', ['lesson' => new Lesson, 'learningPaths' => $learningPaths]);
    }

    public function store(StoreAdminLessonRequest $request, AdminLearningContentService $contentService): RedirectResponse
    {
        $contentService->storeLesson($request->validated());

        return to_route('admin.lessons.index')->with('status', 'Pelajaran tersimpan sebagai draf. Tinjau sebelum menerbitkan.');
    }

    public function edit(Lesson $lesson): View
    {
        $learningPaths = LearningPath::query()->orderBy('group')->orderBy('sort_order')->get(['id', 'title', 'group']);

        return view('admin.lessons.form', compact('lesson', 'learningPaths'));
    }

    public function update(UpdateAdminLessonRequest $request, Lesson $lesson, AdminLearningContentService $contentService): RedirectResponse
    {
        $contentService->updateLesson($lesson, $request->validated());

        return to_route('admin.lessons.index')->with('status', 'Perubahan tersimpan. Pelajaran kembali menjadi draf dan perlu ditinjau.');
    }

    public function publish(Lesson $lesson, AdminLearningContentService $contentService): RedirectResponse
    {
        $contentService->publishLesson($lesson);

        return to_route('admin.lessons.index')->with('status', 'Pelajaran telah ditinjau dan diterbitkan.');
    }

    public function archive(Lesson $lesson, AdminLearningContentService $contentService): RedirectResponse
    {
        $contentService->archiveLesson($lesson);

        return to_route('admin.lessons.index')->with('status', 'Pelajaran diarsipkan dan tidak lagi tampil untuk pengunjung.');
    }
}
