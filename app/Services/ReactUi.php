<?php

namespace App\Services;

use App\Models\LearnerProfile;
use Illuminate\Http\Request;

class ReactUi
{
    public function enabled(Request $request): bool
    {
        $enabled = config('ui.react_enabled') ?? app()->environment('local');
        if (! $enabled || (app()->environment('local') && $request->query('ui') === 'blade')) {
            return false;
        }

        return app()->environment('local') || $request->user() !== null || $this->profile($request) !== null;
    }

    public function profile(Request $request): ?LearnerProfile
    {
        if (! $request->attributes->has('react.learnerProfile')) {
            $request->attributes->set('react.learnerProfile', LearnerProfile::completedFor($request->user()?->id, $request->cookie('artaswaha_device')));
        }

        return $request->attributes->get('react.learnerProfile');
    }

    /** @return array{name: string, avatar: string, mode: string, isAdmin: bool}|null */
    public function viewer(Request $request): ?array
    {
        $identity = $request->user() ?? $this->profile($request);
        if ($identity === null) {
            return null;
        }
        $avatar = $identity->avatar ?? array_key_first(ProfileAvatarService::DEFAULT_AVATARS);

        return [
            'name' => $identity->name,
            'avatar' => str_starts_with($avatar, 'avatars/') ? route('profile.avatar.show') : asset($avatar),
            'mode' => $request->user() === null ? 'anonymous' : 'account',
            'isAdmin' => (bool) $request->user()?->is_admin,
        ];
    }
}
