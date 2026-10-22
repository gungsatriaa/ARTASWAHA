<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShowProfileAvatarController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $user = $request->user();
        $avatar = $user instanceof User ? $user->avatar : null;

        abort_unless(is_string($avatar) && str_starts_with($avatar, 'avatars/') && Storage::disk('local')->exists($avatar), 404);

        return Storage::disk('local')->response($avatar, 'avatar', [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
