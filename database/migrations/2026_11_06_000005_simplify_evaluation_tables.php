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
        // 1. Add stance_value to evaluation_item_options
        Schema::table('evaluation_item_options', function (Blueprint $table) {
            $table->string('stance_value', 100)->nullable()->after('option_text');
        });

        // 2. Drop the unused blanks config table
        Schema::dropIfExists('evaluation_item_blanks');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('evaluation_item_blanks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('evaluation_items')->cascadeOnDelete();
            $table->unsignedInteger('blank_index')->default(1);
            $table->string('hint_label', 150)->nullable();
            $table->text('acceptable_answers')->nullable();
            $table->string('blank_size', 20)->default('short');
            $table->timestamps();

            $table->index(['item_id', 'blank_index']);
        });

        Schema::table('evaluation_item_options', function (Blueprint $table) {
            $table->dropColumn('stance_value');
        });
    }
};
