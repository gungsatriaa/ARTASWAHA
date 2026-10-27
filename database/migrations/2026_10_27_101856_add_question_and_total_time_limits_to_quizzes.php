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
        foreach (['quizzes', 'quiz_questions', 'rooms'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedSmallInteger('time_limit_seconds')->nullable();
            });
        }
        Schema::table('rooms', function (Blueprint $table): void {
            $table->timestamp('ends_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropColumn('ends_at');
        });
        foreach (['rooms', 'quiz_questions', 'quizzes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('time_limit_seconds');
            });
        }
    }
};
