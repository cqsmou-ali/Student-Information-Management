<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staff personal profile (R9): name, position and office for staff accounts.
 * Additive only and nullable, so existing users (including students) are unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name', 120)->nullable();
            $table->string('position', 80)->nullable();
            $table->string('office', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['full_name', 'position', 'office']);
        });
    }
};
