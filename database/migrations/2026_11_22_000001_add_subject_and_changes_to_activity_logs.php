<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit detail: which record an entry is about (subject_type + subject_id),
 * and the old / new value of every changed field (field_changes).
 * Additive only: existing log rows keep their data, the new columns are null for them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->nullableMorphs('subject');          // subject_type, subject_id + index
            $table->json('field_changes')->nullable();  // {"field": {"from": ..., "to": ...}}
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropMorphs('subject');
            $table->dropColumn('field_changes');
        });
    }
};
