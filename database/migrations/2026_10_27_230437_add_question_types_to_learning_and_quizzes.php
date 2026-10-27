<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['lessons', 'quiz_questions'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->string('question_type', 16)->default('single');
                $table->json('correct_options')->nullable();
                $table->json('accepted_answers')->nullable();
            });
        }
        Schema::table('quiz_questions', function (Blueprint $table): void {
            $table->unsignedTinyInteger('correct_option')->nullable()->change();
        });
        Schema::table('room_answers', function (Blueprint $table): void {
            $table->unsignedTinyInteger('answer_index')->nullable()->change();
            $table->json('answer_indexes')->nullable();
            $table->text('answer_text')->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('lessons')->where('question_type', '!=', 'single')->exists()
            || DB::table('quiz_questions')->where('question_type', '!=', 'single')->exists()) {
            throw new RuntimeException('Rollback dibatalkan: masih ada soal esai atau pilihan banyak. Cadangkan dan konversi datanya terlebih dahulu.');
        }
        Schema::table('room_answers', function (Blueprint $table): void {
            $table->dropColumn(['answer_indexes', 'answer_text']);
        });
        foreach (['lessons', 'quiz_questions'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->dropColumn(['question_type', 'correct_options', 'accepted_answers']);
            });
        }
    }
};
