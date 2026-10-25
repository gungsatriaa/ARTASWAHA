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
        Schema::table('learner_profiles', function (Blueprint $table) {
            $table->json('analysis_input')->nullable();
            $table->string('analysis_hash', 64)->nullable();
            $table->string('analysis_status', 20)->nullable();
            $table->json('analysis_result')->nullable();
            $table->timestamp('analysis_requested_at')->nullable();
            $table->timestamp('analysis_completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learner_profiles', function (Blueprint $table) {
            $table->dropColumn(['analysis_input', 'analysis_hash', 'analysis_status', 'analysis_result', 'analysis_requested_at', 'analysis_completed_at']);
        });
    }
};
