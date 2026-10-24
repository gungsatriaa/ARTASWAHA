<?php

use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    $roomService = app(RoomService::class);

    Room::query()->whereIn('status', ['waiting', 'active'])
        ->where(fn ($query) => $query->whereNull('host_last_seen_at')->orWhere('host_last_seen_at', '<', now()->subSeconds(90)))
        ->chunkById(100, function ($rooms) use ($roomService): void {
            foreach ($rooms as $room) {
                $roomService->deactivateIfHostAbsent($room);
            }
        });
})->name('rooms:deactivate-absent-hosts')->everyMinute();
