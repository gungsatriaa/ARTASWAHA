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
        Schema::table('learning_progress', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('visitor_id')->constrained()->nullOnDelete();
            $table->index(['user_id', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_progress', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'completed_at']);
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
