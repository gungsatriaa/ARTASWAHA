<?php

use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('rooms.{roomCode}', function (User $user, string $roomCode): array|false {
    $room = Room::query()->where('code', $roomCode)->first();

    if ($room === null) {
        return false;
    }

    $participant = $room->participants()->where('user_id', $user->id)->first();

    if ($room->host_id !== $user->id && $participant === null) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});
