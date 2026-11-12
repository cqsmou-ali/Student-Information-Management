<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('student_number', 20)->unique();
            $table->string('first_name', 60);
            $table->string('middle_name', 60)->nullable();
            $table->string('last_name', 60);
            $table->date('birthdate');
            $table->enum('sex', ['Male', 'Female']);
            $table->string('email', 120);
            $table->string('contact_number', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('emergency_contact', 150)->nullable();
            $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();
            $table->foreignId('year_level_id')->constrained('year_levels')->restrictOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->enum('enrollment_status', ['Enrolled', 'Not Enrolled', 'Graduated', 'Dropped', 'LOA'])->default('Enrolled');
            $table->date('date_enrolled');
            $table->boolean('is_deleted')->default(false); // soft delete
            $table->timestamps();
            $table->index(['last_name', 'first_name']);
            $table->index(['course_id', 'year_level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
