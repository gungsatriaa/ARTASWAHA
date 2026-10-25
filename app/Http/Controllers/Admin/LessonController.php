<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminLessonRequest;
use App\Http\Requests\UpdateAdminLessonRequest;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Services\AdminLearningContentService;
use App\Services\ContentAudioService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function index(Request $request, AdminLearningContentService $contentService): View
    {
        $filters = $request->validate(['learning_path_id' => ['nullable', 'integer'], 'status' => ['nullable', 'in:draft,published,archived']]);
        $lessons = $contentService->lessons(isset($filters['learning_path_id']) ? (int) $filters['learning_path_id'] : null, $filters['status'] ?? null);
        $learningPaths = $contentService->learningPaths();

        return view('admin.lessons.index', compact('lessons', 'learningPaths'));
    }

    public function create(Request $request): View
    {
        $learningPaths = LearningPath::query()->orderBy('group')->orderBy('sort_order')->get(['id', 'title', 'group']);

        $pathId = $request->integer('learning_path_id');
        $lesson = new Lesson(['learning_path_id' => $learningPaths->contains('id', $pathId) ? $pathId : null]);
        if ($lesson->learning_path_id !== null) {
            $lesson->sort_order = min(65535, (int) Lesson::where('learning_path_id', $pathId)->max('sort_order') + 1);
        }

        return view('admin.lessons.form', compact('lesson', 'learningPaths'));
    }

    public function preview(Lesson $lesson, ContentAudioService $audioService): View
    {
        $lesson->load('learningPath');
        $audio = $audioService->forContent($lesson);
        $audioCurrent = $audio === null || $audioService->isCurrent($audio);

        return view('admin.lessons.preview', compact('lesson', 'audio', 'audioCurrent'));
    }

    public function store(StoreAdminLessonRequest $request, AdminLearningContentService $contentService): RedirectResponse
    {
        $lesson = $contentService->storeLesson($request->validated());

        return to_route('admin.lessons.preview', $lesson)->with('status', 'Modul tersimpan sebagai draf. Tinjau sebelum menerbitkan.');
    }

    public function edit(Lesson $lesson): View
    {
        $learningPaths = LearningPath::query()->orderBy('group')->orderBy('sort_order')->get(['id', 'title', 'group']);

        return view('admin.lessons.form', compact('lesson', 'learningPaths'));
    }

    public function update(UpdateAdminLessonRequest $request, Lesson $lesson, AdminLearningContentService $contentService): RedirectResponse
    {
        $contentService->updateLesson($lesson, $request->validated());

        return to_route('admin.lessons.preview', $lesson)->with('status', 'Perubahan tersimpan. Modul kembali menjadi draf dan perlu ditinjau.');
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
