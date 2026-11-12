<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_code', 20)->unique();
            $table->string('course_name', 150);
            $table->text('description')->nullable();
        });

        Schema::create('year_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30)->unique();
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('section_name', 30);
            $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();
            $table->foreignId('year_level_id')->constrained('year_levels')->restrictOnDelete();
            $table->unique(['section_name', 'course_id', 'year_level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
        Schema::dropIfExists('year_levels');
        Schema::dropIfExists('courses');
    }
};
