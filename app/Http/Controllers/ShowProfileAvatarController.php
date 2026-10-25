<?php

namespace App\Http\Controllers;

use App\Models\LearnerProfile;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShowProfileAvatarController extends Controller
{
    public function __invoke(Request $request): StreamedResponse|RedirectResponse
    {
        $person = $request->user() ?? LearnerProfile::completedFor(null, $request->cookie('artaswaha_device'));
        if ($person === null) {
            return to_route('login');
        }

        return $this->respond($person);
    }

    public function forRoom(Request $request, Room $room, User $user): StreamedResponse
    {
        $viewer = $request->user() ?? LearnerProfile::completedFor(null, $request->cookie('artaswaha_device'));
        abort_unless($viewer && (($viewer instanceof User && $room->host_id === $viewer->id) || $room->participants()->where($viewer instanceof User ? 'user_id' : 'learner_profile_id', $viewer->id)->exists()), 403);
        abort_unless($room->participants()->where('user_id', $user->id)->exists(), 404);

        return $this->respond($user);
    }

    public function forAnonymousRoom(Request $request, Room $room, LearnerProfile $learnerProfile): StreamedResponse
    {
        $viewer = $request->user() ?? LearnerProfile::completedFor(null, $request->cookie('artaswaha_device'));
        abort_unless($viewer && (($viewer instanceof User && $room->host_id === $viewer->id) || $room->participants()->where($viewer instanceof User ? 'user_id' : 'learner_profile_id', $viewer->id)->exists()), 403);
        abort_unless($room->participants()->where('learner_profile_id', $learnerProfile->id)->exists(), 404);

        return $this->respond($learnerProfile);
    }

    private function respond(User|LearnerProfile $user): StreamedResponse
    {
        $avatar = $user->avatar;

        abort_unless(is_string($avatar) && str_starts_with($avatar, 'avatars/') && Storage::disk('local')->exists($avatar), 404);

        return Storage::disk('local')->response($avatar, 'avatar', [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
