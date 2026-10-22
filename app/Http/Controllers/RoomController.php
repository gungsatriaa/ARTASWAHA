<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinRoomRequest;
use App\Http\Requests\StoreRoomAnswerRequest;
use App\Models\Quiz;
use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Http\RedirectResponse;
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

    public function start(Room $room, RoomService $roomService): RedirectResponse
    {
        $roomService->start($room, request()->user());

        return to_route('rooms.show', $room->code);
    }

    public function answer(StoreRoomAnswerRequest $request, Room $room, RoomService $roomService): RedirectResponse
    {
        $roomService->answer($room, $request->user(), $request->validated());

        return to_route('rooms.show', $room->code);
    }

    public function advance(Room $room, RoomService $roomService): RedirectResponse
    {
        $roomService->advance($room, request()->user());

        return to_route('rooms.show', $room->code);
    }
}
