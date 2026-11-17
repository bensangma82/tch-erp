<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class AdministrationPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Register only; never reset role_permission or permission_user assignments.
        foreach ([
            'view' => ['View Administration', 'View all administrative requests, documents and assigned work.'],
            'create' => ['Create Administrative Request', 'Create requests and submit own drafts.'],
            'verify' => ['Verify Administrative Request', 'Verify submitted requests before the MS decision.'],
            'approve' => ['Approve Administrative Request', 'Record MS decisions and higher authority approval evidence.'],
            'execute' => ['Legacy Administrative Execute (inactive)', 'Retained for grant history; use the split workflow permissions.'],
            'assign' => ['Assign Administrative Work', 'Assign approved administrative requests to the designated executor.'],
            'complete' => ['Complete Assigned Administrative Work', 'Record completion of work assigned to the current user.'],
            'verify-completion' => ['Verify Administrative Completion', 'Verify completion before closure; ordinary users cannot verify their own execution.'],
            'close' => ['Close Administrative Request', 'Close work only after recorded completion verification.'],
        ] as $suffix => [$label, $description]) {
            Permission::updateOrCreate(['name' => 'administration.'.$suffix], [
                'label' => $label,
                'module' => 'administration',
                'description' => $description,
            ]);
        }
    }
}
