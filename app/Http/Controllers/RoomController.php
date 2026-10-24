<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinRoomRequest;
use App\Http\Requests\StoreRoomAnswerRequest;
use App\Models\Quiz;
use App\Models\Room;
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
        $room = $roomService->join($request->validated('code'), $request->user());

        return to_route('rooms.show', $room->code);
    }

    public function show(Room $room, RoomService $roomService): View
    {
        return view('rooms.show', $roomService->viewData($room, request()->user()));
    }

    public function status(Room $room, RoomService $roomService): Response
    {
        $user = request()->user();
        abort_unless($room->host_id === $user->id || $room->participants()->where('user_id', $user->id)->exists(), 403);
        $roomService->syncTimedRoom($room);

        return response()->noContent();
    }

    public function leave(Room $room, RoomService $roomService): RedirectResponse
    {
        $roomService->leave($room, request()->user());

        return to_route('quizzes.index')->with('status', 'Kamu sudah keluar dari room.');
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
        $answer = $roomService->answer($room, $request->user(), $request->validated());

        if ($room->quiz->show_answers_immediately) {
            return to_route('rooms.show', ['room' => $room->code, 'feedback' => $answer->id]);
        }

        return to_route('rooms.show', $room->code);
    }
}
