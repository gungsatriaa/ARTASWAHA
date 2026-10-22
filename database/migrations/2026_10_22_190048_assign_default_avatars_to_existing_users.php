<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $avatars = [
            'avatar/anjing.webp',
            'avatar/gajah.webp',
            'avatar/jalak_bali.webp',
            'avatar/kucing.webp',
            'avatar/kupu_kupu.webp',
            'avatar/monyet.webp',
            'avatar/penyu.webp',
            'avatar/rusa.webp',
            'avatar/tokek.webp',
        ];

        DB::table('users')
            ->whereNull('avatar')
            ->orderBy('id')
            ->chunkById(100, static function ($users) use ($avatars): void {
                foreach ($users as $user) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['avatar' => Arr::random($avatars)]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Avatar choices should remain intact if this migration is rolled back.
    }
};
