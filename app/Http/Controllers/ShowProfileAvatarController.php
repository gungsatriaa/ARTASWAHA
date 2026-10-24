<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShowProfileAvatarController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $this->respond($user);
    }

    public function forRoom(Request $request, Room $room, User $user): StreamedResponse
    {
        $viewerId = $request->user()->id;
        abort_unless($room->host_id === $viewerId || $room->participants()->where('user_id', $viewerId)->exists(), 403);
        abort_unless($room->participants()->where('user_id', $user->id)->exists(), 404);

        return $this->respond($user);
    }

    private function respond(User $user): StreamedResponse
    {
        $avatar = $user->avatar;

        abort_unless(is_string($avatar) && str_starts_with($avatar, 'avatars/') && Storage::disk('local')->exists($avatar), 404);

        return Storage::disk('local')->response($avatar, 'avatar', [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
