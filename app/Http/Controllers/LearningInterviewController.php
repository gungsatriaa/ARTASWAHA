<?php

namespace App\Http\Controllers;

use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Services\LearnerInterviewAnalyzer;
use App\Services\LearningProgressService;
use App\Services\PagePresenter;
use App\Services\ProfileAvatarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class LearningInterviewController extends Controller
{
    public function __construct(private LearnerInterviewAnalyzer $analyzer) {}

    private const ROLES = [
        'murid' => 'Murid',
        'guru' => 'Guru',
        'pendatang' => 'Pendatang',
        'masyarakat' => 'Masyarakat umum',
    ];

    public function show(Request $request): InertiaResponse|RedirectResponse
    {
        if (LearnerProfile::completedFor($request->user()?->id, $request->cookie('artaswaha_device')) !== null) {
            return to_route('home');
        }

        $learningPaths = LearningPath::query()->where('is_published', true)->orderBy('group')->orderBy('sort_order')->get();
        $answers = $request->session()->get('learning_interview', []);
        $requestedStep = (int) $request->query('step', 1);
        $availableStep = ! isset($answers['learning_path_ids']) ? 1 : (! isset($answers['role']) ? 2 : (! isset($answers['mode']) ? 3 : 4));

        return app(PagePresenter::class)->render('learning.interview', [
            'learningPaths' => $learningPaths,
            'answers' => $answers,
            'step' => max(1, min($requestedStep, $availableStep)),
            'roles' => self::ROLES,
        ]);
    }

    public function store(Request $request, LearningProgressService $progressService): RedirectResponse
    {
        if (LearnerProfile::completedFor($request->user()?->id, $request->cookie('artaswaha_device')) !== null) {
            return to_route('home');
        }

        $step = (int) $request->input('step');
        $answers = $request->session()->get('learning_interview', []);

        if ($step === 1) {
            $data = $request->validate([
                'learning_path_ids' => ['required', 'array', 'min:1'],
                'learning_path_ids.*' => ['required', 'integer', 'distinct', Rule::exists('learning_paths', 'id')->where('is_published', true)],
            ], [
                'learning_path_ids.required' => 'Silakan pilih minimal satu materi yang ingin kamu pelajari untuk melanjutkan, ya.',
                'learning_path_ids.min' => 'Silakan pilih minimal satu materi yang ingin kamu pelajari untuk melanjutkan, ya.',
            ]);
            $request->session()->forget('learning_interview_destination');
            $request->session()->put('learning_interview', ['learning_path_ids' => array_map('intval', array_values($data['learning_path_ids']))]);

            return to_route('learning.interview.show', ['step' => 2]);
        }

        if (! isset($answers['learning_path_ids'])) {
            return to_route('learning.interview.show');
        }

        if (LearningPath::query()->whereKey($answers['learning_path_ids'])->where('is_published', true)->count() !== count($answers['learning_path_ids'])) {
            $request->session()->forget('learning_interview');

            return to_route('learning.interview.show')->withErrors(['learning_path_ids' => 'Salah satu materi sudah tidak tersedia. Silakan pilih ulang.']);
        }

        if ($step === 2) {
            $data = $request->validate(['role' => ['required', Rule::in(array_keys(self::ROLES))]]);
            $request->session()->put('learning_interview', ['learning_path_ids' => $answers['learning_path_ids'], 'role' => $data['role']]);

            return to_route('learning.interview.show', ['step' => 3]);
        }

        if (! isset($answers['role'])) {
            return to_route('learning.interview.show', ['step' => 2]);
        }

        if ($step === 3) {
            $data = $request->validate(['mode' => ['required', Rule::in($request->user() ? ['account'] : ['account', 'anonymous'])]]);
            $answers['mode'] = $data['mode'];
            $request->session()->put('learning_interview', $answers);

            if ($data['mode'] === 'anonymous') {
                return to_route('learning.interview.show', ['step' => 4]);
            }

            $profile = $this->saveProfile($request, $progressService, $answers);
            $request->session()->forget('learning_interview');
            if ($request->user() !== null) {
                return to_route('learning.paths.show', LearningPath::findOrFail($profile->learning_path_id)->slug)
                    ->withCookie($this->deviceCookie($request, $profile->visitor_id));
            }

            $request->session()->put('learning_interview_destination', $profile->learning_path_id);

            return to_route('login')->withCookie($this->deviceCookie($request, $profile->visitor_id));
        }

        if ($step !== 4 || ($answers['mode'] ?? null) !== 'anonymous') {
            return to_route('learning.interview.show', ['step' => 3]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'avatar_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'dimensions:max_width=2048,max_height=2048', 'max:2048'],
        ]);

        $storedAvatar = null;
        if (isset($data['avatar_upload'])) {
            $storedAvatar = $data['avatar_upload']->store('avatars', 'local');
            abort_if($storedAvatar === false, 500, 'Avatar gagal disimpan.');
        }

        try {
            $profile = $this->saveProfile($request, $progressService, $answers, trim($data['name']), $storedAvatar);
        } catch (Throwable $exception) {
            if ($storedAvatar !== null) {
                Storage::disk('local')->delete($storedAvatar);
            }

            throw $exception;
        }

        $request->session()->forget('learning_interview');
        $request->session()->forget('learning_interview_destination');

        return to_route('learning.paths.show', LearningPath::findOrFail($profile->learning_path_id)->slug)
            ->with('status', 'Siap belajar, '.$profile->name.'!')
            ->withCookie($this->deviceCookie($request, $profile->visitor_id));
    }

    public function avatar(Request $request): StreamedResponse
    {
        $deviceId = $request->cookie('artaswaha_device');
        abort_unless(is_string($deviceId), 404);

        $profile = LearnerProfile::query()->where('visitor_id', $deviceId)->firstOrFail();
        abort_unless($profile->user_id === null || $profile->user_id === $request->user()?->id, 404);
        abort_unless(is_string($profile->avatar) && str_starts_with($profile->avatar, 'avatars/') && Storage::disk('local')->exists($profile->avatar), 404);

        return Storage::disk('local')->response($profile->avatar, 'avatar', ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    /** @param array{learning_path_ids: list<int>, role: string, mode: string} $answers */
    private function saveProfile(Request $request, LearningProgressService $progressService, array $answers, ?string $name = null, ?string $uploadedAvatar = null): LearnerProfile
    {
        $deviceId = $progressService->deviceId($request);
        $profile = LearnerProfile::query()->firstOrNew(['visitor_id' => $deviceId]);
        $previousAvatar = $profile->avatar;
        $previousUserId = $profile->user_id;
        $profile->fill([
            'user_id' => $request->user()?->id,
            'learning_path_id' => $answers['learning_path_ids'][0],
            'learning_path_ids' => $answers['learning_path_ids'],
            'role' => $answers['role'],
            'mode' => $answers['mode'],
            'name' => $name ?? $request->user()?->name,
            'avatar' => $uploadedAvatar ?? ($answers['mode'] === 'anonymous' ? Arr::random(array_keys(ProfileAvatarService::DEFAULT_AVATARS)) : $request->user()?->avatar),
        ])->save();

        if ($previousUserId === null && $previousAvatar !== $profile->avatar && str_starts_with($previousAvatar ?? '', 'avatars/')) {
            Storage::disk('local')->delete($previousAvatar);
        }

        $this->analyzer->enqueue($profile);

        return $profile;
    }

    private function deviceCookie(Request $request, string $deviceId): \Symfony\Component\HttpFoundation\Cookie
    {
        return Cookie::make('artaswaha_device', $deviceId, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax');
    }
}
