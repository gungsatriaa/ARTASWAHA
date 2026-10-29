<?php

namespace App\Http\Middleware;

use App\Models\LearningPath;
use App\Services\ReactUi;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleReactRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('react.shared', $this->share($request));
        $response = $next($request);
        if ($request->header('X-React-Page') === 'true' && ! $request->isMethod('GET') && $response->getStatusCode() === 302) {
            $response->setStatusCode(303);
        }

        return $response;
    }

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        return [
            'errors' => fn (): array => collect($request->session()->get('errors')?->getBag('default')->messages() ?? [])->map(fn (array $messages): string => $messages[0])->all(),
            'materials' => function (): array {
                $query = fn (string $group): Builder => LearningPath::query()->where('is_published', true)->where('group', $group)
                    ->orderBy('sort_order')->orderBy('id')->limit(12)->select(['id', 'title', 'slug', 'group']);
                $paths = $query('paribasa')->unionAll($query('aksara'))->get();

                return collect(['paribasa', 'aksara'])->mapWithKeys(fn (string $group): array => [$group => $paths->where('group', $group)
                    ->map(fn (LearningPath $path): array => ['id' => $path->id, 'title' => $path->title, 'url' => route('learning.paths.show', $path->slug)])->values()->all()])->all();
            },
            'viewer' => fn (): ?array => app(ReactUi::class)->viewer($request),
            'flash' => fn (): array => [
                'message' => $request->session()->get('status'),
                'tone' => $request->session()->get('status_tone', 'success'),
            ],
            'navigation' => fn (): array => [
                'home' => route('home'), 'learning' => route('learning.paths.index'),
                'quizzes' => route('quizzes.index'), 'profile' => route('profile.show'),
                'login' => route('login'), 'register' => route('register'),
                'logout' => route('logout'), 'admin' => route('admin.index'),
                'privacy' => route('privacy-policy'), 'presence' => route('presence.ping'),
            ],
            'brand' => fn (): array => ['avatarBase' => asset('avatar'), 'logo' => asset('image/maskot.webp'), 'mascot' => asset('image/asset1.webp')],
            'csrfToken' => fn (): string => csrf_token(),
            'routeName' => fn (): ?string => $request->route()?->getName(),
            'links' => fn (): array => collect([
                'learning.interview.show', 'learning.interview.store', 'login.store', 'register.store', 'password.request', 'password.email', 'password.update', 'auth.google.redirect',
                'profile.about', 'profile.destroy', 'profile.analysis.show', 'profile.avatar.update', 'quizzes.create', 'quizzes.store', 'quizzes.drafts.store', 'rooms.join',
                'admin.index', 'admin.analytics.index', 'admin.analytics.online', 'admin.learning-paths.index', 'admin.learning-paths.create', 'admin.learning-paths.store',
                'admin.lessons.index', 'admin.lessons.create', 'admin.lessons.store', 'admin.audio.index', 'admin.ai.index', 'admin.ai.reports.store',
            ])->mapWithKeys(fn (string $name): array => [$name => route($name)])->all(),
        ];
    }
}
