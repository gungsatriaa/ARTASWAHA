<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinRoomRequest;
use App\Http\Requests\StoreRoomAnswerRequest;
use App\Models\LearnerProfile;
use App\Models\Quiz;
use App\Models\Room;
use App\Models\User;
use App\Services\ContentAudioService;
use App\Services\RoomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function store(Quiz $quiz, RoomService $roomService): RedirectResponse
    {
        $room = $roomService->create($quiz, request()->user());

        return to_route('rooms.show', $room->code);
    }

    public function join(JoinRoomRequest $request, RoomService $roomService): RedirectResponse
    {
        $player = $this->player();
        if ($player === null) {
            return to_route('learning.interview.show');
        }
        $room = $roomService->join($request->validated('code'), $player);

        return to_route('rooms.show', $room->code);
    }

    public function show(Room $room, RoomService $roomService, ContentAudioService $audioService): View|RedirectResponse
    {
        $player = $this->player();
        if ($player === null) {
            return to_route('learning.interview.show');
        }

        $data = $roomService->viewData($room, $player);
        $audio = $data['activeQuestion'] !== null ? $audioService->forContent($data['activeQuestion']) : null;
        $data['audio'] = $audioService->isPlayable($audio) ? $audio : null;

        return view('rooms.show', $data);
    }

    public function status(Room $room, RoomService $roomService): Response
    {
        $player = $this->player();
        abort_unless($player && (($player instanceof User && $room->host_id === $player->id) || $room->participants()->where($roomService->participantKey($player))->exists()), 403);
        $roomService->syncTimedRoom($room);
        $room->refresh();
        abort_if($room->status === 'inactive', 410, 'Room sudah tidak aktif.');

        return response()->noContent(headers: [
            'X-Room-Status' => $room->status,
            'X-Room-Participants' => (string) $room->participants()->count(),
        ]);
    }

    public function leave(Room $room, RoomService $roomService): RedirectResponse
    {
        $player = $this->player();
        abort_unless($player, 403);
        $isActiveHost = $player instanceof User && $room->host_id === $player->id && in_array($room->status, ['waiting', 'active'], true);
        $roomService->leave($room, $player);

        return to_route('quizzes.index')->with('status', $isActiveHost ? 'Room dinonaktifkan. Peserta tidak dapat melanjutkan kuis.' : 'Kamu sudah keluar dari room.');
    }

    public function heartbeat(Room $room, RoomService $roomService): Response
    {
        $roomService->heartbeat($room, request()->user());

        return response()->noContent();
    }

    public function destroy(Room $room, RoomService $roomService): RedirectResponse
    {
        $roomService->destroy($room, request()->user());

        return to_route('quizzes.index')->with('status', 'Room berhasil dihapus.');
    }

    public function start(Room $room, RoomService $roomService): RedirectResponse
    {
        $roomService->start($room, request()->user());

        return to_route('rooms.show', $room->code);
    }

    public function answer(StoreRoomAnswerRequest $request, Room $room, RoomService $roomService): RedirectResponse
    {
        $player = $this->player();
        abort_unless($player, 403);
        $answer = $roomService->answer($room, $player, $request->validated());

        if ($room->quiz->show_answers_immediately) {
            return to_route('rooms.show', ['room' => $room->code, 'feedback' => $answer->id]);
        }

        return to_route('rooms.show', $room->code);
    }

    private function player(): User|LearnerProfile|null
    {
        return request()->user() ?? LearnerProfile::completedFor(null, request()->cookie('artaswaha_device'));
    }
}
