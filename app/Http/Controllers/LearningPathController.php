<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use Illuminate\Contracts\View\View;

class LearningPathController extends Controller
{
    public function index(): View
    {
        $learningPaths = LearningPath::query()
            ->where('is_published', true)
            ->withCount('lessons')
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();

        return view('welcome', compact('learningPaths'));
    }

    public function show(LearningPath $learningPath): View
    {
        abort_unless($learningPath->is_published, 404);

        $learningPath->load('lessons');

        return view('learning.paths.show', compact('learningPath'));
    }
}
