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
        Schema::create('conflict_map_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->string('conflict_type');
            $table->string('label');
            $table->string('subtitle')->nullable();
            $table->string('color_theme')->default('blue');
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['quiz_id', 'sort_order']);
        });

        Schema::create('conflict_map_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('conflict_map_zones')->nullOnDelete();
            $table->string('card_letter', 10)->nullable();
            $table->text('event_text');
            $table->string('target_conflict_type');
            $table->text('hint')->nullable();
            $table->text('explanation')->nullable();
            $table->unsignedInteger('points')->default(10);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['quiz_id', 'sort_order']);
            $table->index(['quiz_id', 'target_conflict_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conflict_map_events');
        Schema::dropIfExists('conflict_map_zones');
    }
};
