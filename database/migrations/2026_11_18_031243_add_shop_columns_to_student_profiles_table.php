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
        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->unsignedInteger('gems_spent')->default(0)->after('total_gems');
            $table->foreignId('equipped_avatar_item_id')->nullable()->after('gems_spent')->constrained('shop_items')->nullOnDelete();
            $table->foreignId('equipped_background_item_id')->nullable()->after('equipped_avatar_item_id')->constrained('shop_items')->nullOnDelete();
            $table->foreignId('equipped_console_item_id')->nullable()->after('equipped_background_item_id')->constrained('shop_items')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('equipped_avatar_item_id');
            $table->dropConstrainedForeignId('equipped_background_item_id');
            $table->dropConstrainedForeignId('equipped_console_item_id');
            $table->dropColumn('gems_spent');
        });
    }
};
