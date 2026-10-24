<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileAvatarRequest;
use App\Models\LearnerProfile;
use App\Services\ProfileAvatarService;
use Illuminate\Http\RedirectResponse;

class UpdateProfileAvatarController extends Controller
{
    public function __invoke(UpdateProfileAvatarRequest $request, ProfileAvatarService $avatarService): RedirectResponse
    {
        $profile = $request->user() ?? LearnerProfile::completedFor(null, $request->cookie('artaswaha_device'));
        if ($profile === null) {
            return to_route('login');
        }

        $avatarService->update($profile, $request->validated());

        return to_route('profile.show')->with('status', 'Avatar profil berhasil diperbarui.');
    }
}
