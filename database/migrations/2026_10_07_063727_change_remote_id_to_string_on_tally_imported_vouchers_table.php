<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE tally_imported_vouchers
             ALTER COLUMN remote_id TYPE VARCHAR(150)
             USING remote_id::text'
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE tally_imported_vouchers
             ALTER COLUMN remote_id TYPE UUID
             USING remote_id::uuid'
        );
    }
};