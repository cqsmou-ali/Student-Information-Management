<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('subject_code', 20)->unique();
            $table->string('subject_title', 150);
            $table->unsignedTinyInteger('units')->default(3);
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->foreignId('year_level_id')->nullable()->constrained('year_levels')->nullOnDelete();
            $table->enum('semester', ['1st', '2nd', 'Summer'])->default('1st');
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->string('school_year', 9);               // e.g. 2025-2026
            $table->enum('semester', ['1st', '2nd', 'Summer']);
            $table->decimal('grade', 3, 2)->nullable();     // 1.00 - 5.00
            $table->string('remarks', 20)->nullable();
            $table->unique(['student_id', 'subject_id', 'school_year', 'semester']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role', 20)->nullable();
            $table->string('action', 60)->index();
            $table->text('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('subjects');
    }
};
