<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('content_audio', function (Blueprint $table): void {
            $table->timestamp('generation_started_at')->nullable();
            $table->timestamp('generation_finished_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_audio', function (Blueprint $table): void {
            $table->dropColumn(['generation_started_at', 'generation_finished_at']);
        });
    }
};
