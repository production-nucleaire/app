<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a unique (reactor_id, date) index so imports can use a batched
     * upsert() instead of one updateOrCreate() per row, and so the frequent
     * whereBetween('date')/latestOfMany('date') reads are index-backed.
     */
    public function up(): void
    {
        // Drop any pre-existing duplicates (keeping the lowest id) so the
        // unique index can be created without error. Written to run on both
        // MySQL (prod) and SQLite (tests): the extra subquery wrapper is what
        // lets MySQL delete from a table it also selects from.
        DB::statement(
            'DELETE FROM records WHERE id NOT IN (
                SELECT id FROM (
                    SELECT MIN(id) AS id FROM records GROUP BY reactor_id, date
                ) AS keep
            )'
        );

        Schema::table('records', function (Blueprint $table) {
            $table->unique(['reactor_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropUnique(['reactor_id', 'date']);
        });
    }
};
