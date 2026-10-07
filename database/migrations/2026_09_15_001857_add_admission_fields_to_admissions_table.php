<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * This migration is intentionally a no-op.
     *
     * The admissions fields originally added here are already created
     * by the earlier create_admissions_table migration:
     *
     * 2026_09_14_233929_create_admissions_table.php
     *
     * Keeping this migration in the history preserves compatibility
     * with existing installations while allowing a fresh database to
     * migrate from zero without attempting to create duplicate columns,
     * foreign keys and indexes.
     */
    public function up(): void
    {
        //
    }

    /**
     * Nothing is rolled back here because this migration no longer
     * owns any admissions columns. Those columns belong to the original
     * create_admissions_table migration.
     */
    public function down(): void
    {
        //
    }
};
