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
        Schema::table('conflict_map_events', function (Blueprint $table): void {
            $table->text('event_text')->nullable()->change();
            $table->string('image_path')->nullable()->after('event_text');
            $table->string('image_disk')->nullable()->after('image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conflict_map_events', function (Blueprint $table): void {
            $table->dropColumn(['image_path', 'image_disk']);
            $table->text('event_text')->nullable(false)->change();
        });
    }
};
