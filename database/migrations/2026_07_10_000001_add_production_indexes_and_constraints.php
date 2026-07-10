<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            // Foreign key constraint (nullable since some records may not have user_id)
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            // Indexes for commonly queried columns
            $table->index('user_id');
            $table->index('waktu');
            $table->index('nama');
        });

        // Unique constraint to prevent duplicate attendance per user per day
        // Uses a partial index approach via raw SQL for SQLite compatibility
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS absensis_user_date_unique ON absensis (user_id, date(waktu)) WHERE user_id IS NOT NULL');

        Schema::table('attendances', function (Blueprint $table) {
            // Composite index for the most common query pattern
            $table->index(['student_id', 'month', 'year']);
        });

        Schema::table('notes', function (Blueprint $table) {
            // Index for journal lookups by class and date
            $table->index(['class', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex('absensis_waktu_index');
            $table->dropIndex('absensis_nama_index');
        });

        DB::statement('DROP INDEX IF EXISTS absensis_user_date_unique');

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['student_id', 'month', 'year']);
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->dropIndex(['class', 'date']);
        });
    }
};
