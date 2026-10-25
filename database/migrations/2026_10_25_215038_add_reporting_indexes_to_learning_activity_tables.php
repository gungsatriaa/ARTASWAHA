<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->index('created_at');
        });
        Schema::table('learner_profiles', function (Blueprint $table): void {
            $table->index('created_at');
            $table->index(['analysis_status', 'created_at']);
        });
        Schema::table('learning_progress', function (Blueprint $table): void {
            $table->index('completed_at');
        });
        Schema::table('room_answers', function (Blueprint $table): void {
            $table->index('answered_at');
        });
    }

    public function down(): void
    {
        Schema::table('room_answers', function (Blueprint $table): void {
            $table->dropIndex(['answered_at']);
        });
        Schema::table('learning_progress', function (Blueprint $table): void {
            $table->dropIndex(['completed_at']);
        });
        Schema::table('learner_profiles', function (Blueprint $table): void {
            $table->dropIndex(['analysis_status', 'created_at']);
            $table->dropIndex(['created_at']);
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
        });
    }
};
