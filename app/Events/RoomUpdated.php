<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomUpdated implements ShouldBroadcastNow, ShouldRescue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @param array{status: string, question: array{id: int, prompt: string, options: array<int, string>, question_type: string, sort_order: int}|null} $payload */
    public function __construct(public string $roomCode, public array $payload) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('rooms.'.$this->roomCode);
    }

    public function broadcastAs(): string
    {
        return 'room.updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
