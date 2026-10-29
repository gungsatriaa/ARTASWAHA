<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Services\LearnerInterviewAnalyzer;
use App\Services\PagePresenter;
use App\Services\ReactUi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LearningPathController extends Controller
{
    public function catalog(Request $request): Response
    {
        $query = LearningPath::query()
            ->where('is_published', true)
            ->select(['id', 'slug', 'group', 'title', 'description'])
            ->withCount('lessons')
            ->orderBy('group')
            ->orderBy('sort_order')->orderBy('id');

        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'group' => ['nullable', 'in:paribasa,aksara']]);
        $search = trim($filters['q'] ?? '');
        $paths = fn () => $query->when($search !== '', fn (Builder $query): Builder => $query->where('title', 'like', '%'.$search.'%'))
            ->when($filters['group'] ?? null, fn (Builder $query, string $group): Builder => $query->where('group', $group))
            ->paginate(12)->withQueryString()->through(fn (LearningPath $path): array => [
                'id' => $path->id, 'title' => $path->title, 'description' => $path->description,
                'group' => $path->group, 'lessonsCount' => $path->lessons_count,
                'url' => route('learning.paths.show', $path->slug),
            ]);

        return Inertia::render('Learning/Catalog', [
            'paths' => $paths,
            'filters' => ['q' => $search, 'group' => $filters['group'] ?? ''],
            'meta' => ['title' => 'Materi belajar — ARTASWAHA', 'description' => 'Pilih materi bahasa dan aksara Bali untuk memulai perjalanan belajarmu.', 'robots' => 'index, follow', 'canonical' => route('learning.paths.index')],
        ]);
    }

    public function index(Request $request, LearnerInterviewAnalyzer $analyzer): Response
    {
        $user = $request->user();
        $profile = app(ReactUi::class)->profile($request);
        $showMobileDashboard = $user !== null || $profile !== null;

        $learningPathsQuery = LearningPath::query()
            ->where('is_published', true)
            ->withCount('lessons')
            ->orderBy('group')
            ->orderBy('sort_order');

        if ($showMobileDashboard) {
            $learningPathsQuery->withCount([
                'lessons as completed_lessons_count' => fn (Builder $query): Builder => $query->whereHas(
                    'progress',
                    fn (Builder $progressQuery): Builder => $progressQuery
                        ->when($user !== null, fn (Builder $query): Builder => $query->where('user_id', $user->id))
                        ->when($user === null, fn (Builder $query): Builder => $query->where('visitor_id', $profile->visitor_id)->whereNull('user_id'))
                        ->whereNotNull('completed_at'),
                ),
            ]);
        }

        $learningPaths = $learningPathsQuery->get();

        $lastLearningProgress = $showMobileDashboard ? LearningProgress::query()
            ->when($user !== null, fn (Builder $query): Builder => $query->where('user_id', $user->id))
            ->when($user === null, fn (Builder $query): Builder => $query->where('visitor_id', $profile->visitor_id)->whereNull('user_id'))
            ->whereNotNull('completed_at')
            ->whereHas('lesson', fn (Builder $query): Builder => $query
                ->where('is_published', true)
                ->whereNotNull('reviewed_at')
                ->whereHas('learningPath', fn (Builder $query): Builder => $query->where('is_published', true)))
            ->with('lesson.learningPath')
            ->latest('completed_at')
            ->latest('id')
            ->first() : null;

        $hasCompletedInterview = $profile !== null;
        if ($profile !== null && $analyzer->isConfigured()) {
            $analyzer->enqueue($profile);
            $profile->refresh();
        }
        $analysis = $analyzer->presentation($profile, $learningPaths);

        return app(PagePresenter::class)->render('welcome', compact('learningPaths', 'hasCompletedInterview', 'lastLearningProgress', 'profile', 'user', 'analysis'));
    }

    public function show(Request $request, LearningPath $learningPath, LearnerInterviewAnalyzer $analyzer, ReactUi $ui): Response
    {
        abort_unless($learningPath->is_published, 404);

        $user = $request->user();
        $profile = $ui->profile($request);
        $orderedLessons = $learningPath->lessons()->select('id');
        if ($user !== null || $profile !== null) {
            $orderedLessons->withExists(['progress as completed' => fn (Builder $query): Builder => $query
                ->when($user !== null, fn (Builder $query): Builder => $query->where('user_id', $user->id))
                ->when($user === null, fn (Builder $query): Builder => $query->where('visitor_id', $profile->visitor_id)->whereNull('user_id'))
                ->whereNotNull('completed_at')]);
        }
        $orderedLessons = $orderedLessons->get();
        $completedIds = $orderedLessons->where('completed', true)->pluck('id');
        $totalLessons = $orderedLessons->count();
        $orderedIds = $orderedLessons->pluck('id');
        $firstIncomplete = $orderedIds->search(fn (int $id): bool => ! $completedIds->contains($id));
        $lockedIds = $firstIncomplete === false ? collect() : $orderedIds->slice($firstIncomplete + 1);
        $progress = $totalLessons > 0 ? (int) round($completedIds->count() / $totalLessons * 100) : 0;
        $lessons = fn () => $learningPath->lessons()->select(['id', 'title', 'slug', 'summary'])->paginate(12)
            ->through(fn (Lesson $lesson): array => ['id' => $lesson->id, 'title' => $lesson->title, 'summary' => $lesson->summary, 'locked' => $lockedIds->contains($lesson->id), 'url' => $lockedIds->contains($lesson->id) ? null : route('learning.lessons.show', $lesson->slug), 'progress' => $completedIds->contains($lesson->id) ? 100 : 0]);
        $analysis = $analyzer->presentation($profile, collect([$learningPath]));

        return Inertia::render('Learning/Path', [
            'path' => ['id' => $learningPath->id, 'title' => $learningPath->title, 'description' => $learningPath->description, 'group' => $learningPath->group, 'progress' => $progress, 'completedLessons' => $completedIds->count()],
            'lessons' => $lessons,
            'recommendation' => $analysis['recommendations'][0]['reason'] ?? null,
            'meta' => ['title' => $learningPath->title.' — ARTASWAHA', 'description' => $learningPath->description, 'robots' => 'index, follow', 'canonical' => route('learning.paths.show', $learningPath->slug)],
        ]);
    }
}
