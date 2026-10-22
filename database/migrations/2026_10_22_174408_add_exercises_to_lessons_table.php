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
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('exercise_prompt')->nullable();
            $table->json('exercise_options')->nullable();
            $table->unsignedTinyInteger('correct_option')->nullable();
            $table->text('exercise_explanation')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['exercise_prompt', 'exercise_options', 'correct_option', 'exercise_explanation']);
        });
    }
};
