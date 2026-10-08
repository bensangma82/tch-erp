
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            ALTER TABLE tally_exports
            ALTER COLUMN remote_id TYPE VARCHAR(255)
            USING remote_id::text
        ');
    }

    public function down(): void
    {
        // Deliberately left empty.
        // Tally identifiers may not be valid UUIDs,
        // so reverting could cause data loss.
    }
};
