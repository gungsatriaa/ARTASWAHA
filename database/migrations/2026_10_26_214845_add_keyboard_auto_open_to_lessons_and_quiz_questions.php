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
        foreach (['lessons', 'quiz_questions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->boolean('keyboard_auto_open')->default(false);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['lessons', 'quiz_questions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('keyboard_auto_open');
            });
        }
    }
};
