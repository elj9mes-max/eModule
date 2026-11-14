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
        Schema::table('lecture_contents', function (Blueprint $table) {
            $table->string('paper_theme', 32)->default('classic')->after('layout_style');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lecture_contents', function (Blueprint $table) {
            $table->dropColumn('paper_theme');
        });
    }
};
