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
        Schema::table('quizzes', function (Blueprint $table) {
            $table->unsignedSmallInteger('seconds_per_question')->nullable();
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedSmallInteger('seconds_per_question')->nullable();
            $table->timestamp('question_ends_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['seconds_per_question', 'question_ends_at']);
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('seconds_per_question');
        });
    }
};
