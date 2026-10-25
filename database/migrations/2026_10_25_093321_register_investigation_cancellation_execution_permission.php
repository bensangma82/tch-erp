<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permission = 'billing.investigation-cancellation.execute';

        // Preserve existing permissions and assignments.
        if (
            DB::table('permissions')
                ->where('name', $permission)
                ->exists()
        ) {
            return;
        }

        DB::table('permissions')->insert([
            'name' => $permission,
            'label' => 'Execute approved investigation cancellations',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Do not delete permissions during rollback.
        // They may already be assigned to hospital users.
    }
};
