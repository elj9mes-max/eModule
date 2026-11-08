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
            $table->string('layout_style')->default('standard')->after('type');
            $table->string('narration_audio_disk')->nullable()->default('public')->after('caption');
            $table->string('narration_audio_path')->nullable()->after('narration_audio_disk');
            $table->string('narration_audio_name')->nullable()->after('narration_audio_path');
            $table->json('vocabulary_glosses')->nullable()->after('narration_audio_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lecture_contents', function (Blueprint $table) {
            $table->dropColumn([
                'layout_style',
                'narration_audio_disk',
                'narration_audio_path',
                'narration_audio_name',
                'vocabulary_glosses',
            ]);
        });
    }
};
