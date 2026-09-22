<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RolePermissionController extends Controller
{
    /**
     * Available ERP roles.
     */
    private function roles(): array
    {
        return [
            'reception' => 'Reception',
            'nursing' => 'Nursing',
            'doctor' => 'Doctor',
            'billing' => 'Billing',
            'finance' => 'Finance / Accounts',
            'laboratory' => 'Laboratory',
            'radiology' => 'Radiology',
            'pharmacy' => 'Pharmacy',
            'stores' => 'Stores / Inventory',
            'hr' => 'HR',
            'medical_records' => 'Medical Records',
            'emergency' => 'Emergency',
            'ipd' => 'IPD / Ward',
            'management' => 'Management / Read Only',
        ];
    }

    /**
     * Display Role & Permission Dashboard.
     */
    public function index(Request $request): View
    {
        $roles = $this->roles();

        $selectedRole = $request->string('role')->toString();

        if (
            $selectedRole === ''
            || ! array_key_exists($selectedRole, $roles)
        ) {
            $selectedRole = 'reception';
        }

        /*
        |--------------------------------------------------------------------------
        | Permissions grouped by module
        |--------------------------------------------------------------------------
        */

        $permissions = Permission::query()
            ->orderBy('module')
            ->orderBy('label')
            ->get();

        $permissionsByModule = $permissions->groupBy('module');

        /*
        |--------------------------------------------------------------------------
        | Permissions currently assigned to selected role
        |--------------------------------------------------------------------------
        */

        $assignedPermissionIds = DB::table('role_permission')
            ->where('role', $selectedRole)
            ->pluck('permission_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view(
            'admin.role-permissions.index',
            compact(
                'roles',
                'selectedRole',
                'permissionsByModule',
                'assignedPermissionIds'
            )
        );
    }

    /**
     * Save permissions assigned to a role.
     */
    public function update(Request $request): RedirectResponse
    {
        $roles = $this->roles();

        $validated = $request->validate([
            'role' => [
                'required',
                'string',
                Rule::in(array_keys($roles)),
            ],

            'permissions' => [
                'nullable',
                'array',
            ],

            'permissions.*' => [
                'integer',
                'distinct',
                'exists:permissions,id',
            ],
        ]);

        $role = $validated['role'];

        $permissionIds = collect(
            $validated['permissions'] ?? []
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        DB::transaction(function () use (
            $role,
            $permissionIds
        ) {

            /*
            |--------------------------------------------------------------------------
            | Remove existing permissions for this role
            |--------------------------------------------------------------------------
            */

            DB::table('role_permission')
                ->where('role', $role)
                ->delete();

            /*
            |--------------------------------------------------------------------------
            | Insert new permission assignments
            |--------------------------------------------------------------------------
            */

            if ($permissionIds->isNotEmpty()) {

                $now = now();

                $rows = $permissionIds
                    ->map(function ($permissionId) use (
                        $role,
                        $now
                    ) {
                        return [
                            'role' => $role,
                            'permission_id' => $permissionId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    })
                    ->all();

                DB::table('role_permission')
                    ->insert($rows);
            }
        });

        return redirect()
            ->route(
                'admin.role-permissions.index',
                ['role' => $role]
            )
            ->with(
                'success',
                'Permissions for '.$roles[$role].' were updated successfully.'
            );
    }
}