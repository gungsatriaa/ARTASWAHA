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
        Schema::table('room_participants', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreignId('learner_profile_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unique(['room_id', 'learner_profile_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_participants', function (Blueprint $table) {
            $table->dropForeign(['learner_profile_id']);
            $table->dropUnique(['room_id', 'learner_profile_id']);
            $table->dropColumn('learner_profile_id');
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
