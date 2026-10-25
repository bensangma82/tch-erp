<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        foreach ([
            ['billing.investigation-cancellation.request', 'Request investigation cancellation', 'Submit an investigation cancellation request'],
            ['billing.investigation-cancellation.approve', 'Approve investigation cancellation', 'Review investigation cancellation requests; exceptional approval requires explicit MS designation and direct permission'],
            ['billing.investigation-refund.process', 'Process investigation refund', 'Record authorized investigation cancellation accounting and refunds'],
        ] as [$name, $label, $description]) {
            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'label' => $label, 'module' => 'billing', 'description' => $description, 'updated_at' => $now,
                'created_at' => $now,
            ]);
        }
        // Deliberately do NOT assign any permission to a role or user.
    }

    public function down(): void
    {
        // Deliberately preserve permissions/assignments for audit and avoid destructive rollback.
    }
};
