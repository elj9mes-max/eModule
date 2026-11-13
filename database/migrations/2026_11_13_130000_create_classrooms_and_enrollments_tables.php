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
        Schema::create('classrooms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('grade_level', 50)->nullable();
            $table->char('class_code', 6)->unique();
            $table->string('theme', 30)->default('forest');
            $table->string('icon', 30)->default('🏫');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('max_students')->default(45);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index('class_code');
        });

        Schema::create('classroom_students', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['classroom_id', 'user_id']);
            $table->index('classroom_id');
            $table->index('user_id');
        });

        Schema::create('classroom_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            $table->unique(['classroom_id', 'module_id']);
            $table->index('classroom_id');
            $table->index('module_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classroom_modules');
        Schema::dropIfExists('classroom_students');
        Schema::dropIfExists('classrooms');
    }
};
