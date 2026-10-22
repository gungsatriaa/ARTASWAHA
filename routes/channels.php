<?php

use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('rooms.{roomCode}', function (User $user, string $roomCode): bool {
    return Room::query()
        ->where('code', $roomCode)
        ->where(function (Builder $query) use ($user): void {
            $query->where('host_id', $user->id)
                ->orWhereHas('participants', fn (Builder $participants): Builder => $participants->where('user_id', $user->id));
        })
        ->exists();
});
