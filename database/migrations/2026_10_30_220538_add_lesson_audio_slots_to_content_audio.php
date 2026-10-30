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
        Schema::table('content_audio', function (Blueprint $table): void {
            $table->unsignedSmallInteger('lesson_audio_slot')->default(0);
            $table->unique(['lesson_id', 'lesson_audio_slot']);
        });
        Schema::table('content_audio', function (Blueprint $table): void {
            $table->dropUnique(['lesson_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('content_audio')->where('lesson_audio_slot', '>', 0)->exists()) {
            throw new RuntimeException('Audio latihan masih tersimpan. Gunakan perbaikan maju agar data tidak terhapus.');
        }
        Schema::table('content_audio', function (Blueprint $table): void {
            $table->unique('lesson_id');
        });
        Schema::table('content_audio', function (Blueprint $table): void {
            $table->dropUnique(['lesson_id', 'lesson_audio_slot']);
            $table->dropColumn('lesson_audio_slot');
        });
    }
};
