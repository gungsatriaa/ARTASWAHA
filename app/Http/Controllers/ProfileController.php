<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class ProfileController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user()->loadCount([
            'learningProgress as completed_lessons_count' => fn (Builder $query): Builder => $query->whereNotNull('completed_at'),
            'quizzes',
        ]);

        $learningPaths = LearningPath::query()
            ->where('is_published', true)
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();

        return view('profile.show', compact('learningPaths', 'user'));
    }
}
