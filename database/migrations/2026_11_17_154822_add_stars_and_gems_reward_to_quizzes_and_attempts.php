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
            $table->unsignedInteger('stars_per_correct')->default(1)->after('xp_reward');
            $table->unsignedInteger('gems_per_correct')->default(10)->after('stars_per_correct');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->unsignedInteger('gems_earned')->default(0)->after('stars');
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->unsignedInteger('total_gems')->default(0)->after('total_stars');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn('total_gems');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn('gems_earned');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['stars_per_correct', 'gems_per_correct']);
        });
    }
};
