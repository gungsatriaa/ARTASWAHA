<?php

namespace App\Http\Middleware;

use App\Models\LearnerProfile;
use App\Services\ReactUi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireLearningInterview
{
    public function handle(Request $request, Closure $next): Response
    {
        $interview = $request->routeIs('learning.interview.show', 'learning.interview.store');
        $registration = $request->routeIs('register', 'register.store');
        if ($request->user() !== null || (! $interview && ! $registration && ! $request->routeIs(
            'home', 'learning.paths.*', 'learning.lessons.*', 'learning.progress.*', 'learning.exercises.*',
            'quizzes.index', 'rooms.show', 'rooms.join', 'profile.show', 'profile.about', 'content-audio.show',
        ))) {
            return $next($request);
        }

        if (app(ReactUi::class)->profile($request) !== null) {
            return $next($request);
        }

        $deviceId = $request->cookie('artaswaha_device');
        $awaitingAccount = is_string($deviceId) && LearnerProfile::query()->where('visitor_id', $deviceId)
            ->whereNull('user_id')->where('mode', 'account')->exists();
        if ($awaitingAccount) {
            return $registration ? $next($request) : to_route('register');
        }

        if ($interview) {
            return $next($request);
        }

        if ($request->isMethod('GET') && ! $registration && ! $request->session()->has('url.intended')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        if ($registration) {
            if ($request->session()->get('learning_interview.mode') !== 'account') {
                $request->session()->put('learning_interview', ['mode' => 'account']);
            }

            return to_route('learning.interview.show', ['step' => 2]);
        }

        return to_route('learning.interview.show');
    }
}
