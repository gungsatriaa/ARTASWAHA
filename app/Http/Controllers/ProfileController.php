<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use Illuminate\Contracts\View\View;

class ProfileController extends Controller
{
    public function __invoke(): View
    {
        $learningPaths = LearningPath::query()
            ->where('is_published', true)
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();

        return view('profile.show', compact('learningPaths'));
    }
}
