<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permission = 'billing.investigation-refund.void';

        if (! DB::table('permissions')
            ->where('name', $permission)
            ->exists()) {

            DB::table('permissions')->insert([
                'name' => $permission,
                'label' => 'Void investigation refund settlement',
                'module' => 'billing',
                'description' =>
                    'Independently authorize cancellation or voiding ' .
                    'of an investigation refund settlement with audit evidence',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Never grant this permission automatically.
    }

    public function down(): void
    {
        // Preserve financial authorization and audit references.
    }
};
