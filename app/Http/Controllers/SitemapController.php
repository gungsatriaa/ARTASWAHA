<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function robots(): Response
    {
        return response("User-agent: *\nAllow: /\nSitemap: ".route('sitemap')."\n")
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function __invoke(): Response
    {
        $learningPaths = LearningPath::query()
            ->where('is_published', true)
            ->with('lessons')
            ->orderBy('id')
            ->get();

        return response()
            ->view('sitemap', compact('learningPaths'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
