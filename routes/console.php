<?php

use App\Models\LearnerProfile;
use App\Models\Room;
use App\Services\LearnerInterviewAnalyzer;
use App\Services\RoomService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('learning:analyze', function (LearnerInterviewAnalyzer $analyzer): void {
    Cache::forever('learner-analysis-last-check', now()->toIso8601String());
    if (! $analyzer->isConfigured()) {
        $this->info('AI belum dikonfigurasi.');

        return;
    }

    LearnerProfile::forAnalysis()
        ->chunkById(100, function ($profiles) use ($analyzer): void {
            foreach ($profiles as $profile) {
                $analyzer->enqueue($profile);
            }
        });
    $this->info('Analisis belajar yang jatuh tempo sudah dijadwalkan.');
})->purpose('Perbarui analisis progres dan kebiasaan belajar pengguna');

Schedule::command('learning:analyze')->hourly()->withoutOverlapping();

Schedule::call(function (): void {
    $roomService = app(RoomService::class);

    Room::query()->whereIn('status', ['waiting', 'active'])
        ->where(fn (Builder $query): Builder => $query
            ->where(fn (Builder $query): Builder => $query->whereNull('host_last_seen_at')->orWhere('host_last_seen_at', '<', now()->subSeconds(90)))
            ->orWhere(fn (Builder $query): Builder => $query->where('status', 'active')->whereNotNull('ends_at')->where('ends_at', '<=', now())))
        ->chunkById(100, function ($rooms) use ($roomService): void {
            foreach ($rooms as $room) {
                $roomService->deactivateIfHostAbsent($room);
            }
        });
})->name('rooms:deactivate-absent-hosts')->everyMinute();
