<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileAvatarRequest;
use App\Services\ProfileAvatarService;
use Illuminate\Http\RedirectResponse;

class UpdateProfileAvatarController extends Controller
{
    public function __invoke(UpdateProfileAvatarRequest $request, ProfileAvatarService $avatarService): RedirectResponse
    {
        $avatarService->update($request->user(), $request->validated());

        return to_route('profile.show')->with('status', 'Avatar profil berhasil diperbarui.');
    }
}
