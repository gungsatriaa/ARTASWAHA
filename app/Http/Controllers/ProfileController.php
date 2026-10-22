<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class ProfileController extends Controller
{
    public function about(): View
    {
        $user = auth()->user();
        $learningPaths = LearningPath::query()
            ->where('is_published', true)
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();

        return view('profile.about', compact('learningPaths', 'user'));
    }

    public function __invoke(): View
    {
        $user = auth()->user();

        $learningPaths = LearningPath::query()
            ->where('is_published', true)
            ->withCount([
                'lessons',
                'lessons as completed_lessons_count' => fn (Builder $query): Builder => $query->whereHas(
                    'progress',
                    fn (Builder $progressQuery): Builder => $progressQuery
                        ->where('user_id', $user->id)
                        ->whereNotNull('completed_at'),
                ),
            ])
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();

        $learningPathsByGroup = $learningPaths->groupBy('group');
        $totalLessons = $learningPaths->sum('lessons_count');
        $completedLessons = $learningPaths->sum('completed_lessons_count');

        return view('profile.show', compact('completedLessons', 'learningPaths', 'learningPathsByGroup', 'totalLessons', 'user'));
    }
}
