<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->timestamp('host_last_seen_at')->nullable();
            $table->index(['status', 'host_last_seen_at']);
        });

        DB::table('rooms')->whereIn('status', ['waiting', 'active'])->update(['host_last_seen_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex(['status', 'host_last_seen_at']);
            $table->dropColumn('host_last_seen_at');
        });
    }
};
