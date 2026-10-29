<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use App\Services\PagePresenter;
use App\Services\ReactPage;

class PrivacyPolicyController extends Controller
{
    public function __invoke(): ReactPage
    {
        $learningPaths = LearningPath::query()
            ->where('is_published', true)
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();

        return app(PagePresenter::class)->render('privacy-policy', compact('learningPaths'));
    }
}
