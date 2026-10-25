<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_audio', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lesson_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_question_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->string('source_hash', 64);
            $table->uuid('version');
            $table->text('spoken_text');
            $table->string('language', 10)->default('id');
            $table->unsignedSmallInteger('speed')->default(150);
            $table->string('engine', 20)->default('espeak');
            $table->string('status', 20)->default('draft')->index();
            $table->string('path')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_audio');
    }
};
