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
        Schema::create('room_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_question_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('answer_index');
            $table->boolean('is_correct');
            $table->unsignedSmallInteger('points_awarded')->default(0);
            $table->timestamp('answered_at')->useCurrent();
            $table->timestamps();
            $table->unique(['room_participant_id', 'quiz_question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_answers');
    }
};
