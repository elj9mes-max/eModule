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
        Schema::table('modules', function (Blueprint $table): void {
            $table->index(['created_at', 'id'], 'modules_created_at_id_idx');
            $table->index(['user_id', 'created_at', 'id'], 'modules_user_created_at_id_idx');
        });

        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->index(['quiz_id', 'status', 'id'], 'quiz_attempts_quiz_status_id_idx');
            $table->index(['status', 'id'], 'quiz_attempts_status_id_idx');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->index(['role', 'name'], 'users_role_name_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table): void {
            $table->dropIndex('modules_created_at_id_idx');
            $table->dropIndex('modules_user_created_at_id_idx');
        });

        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->dropIndex('quiz_attempts_quiz_status_id_idx');
            $table->dropIndex('quiz_attempts_status_id_idx');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_role_name_idx');
        });
    }
};
