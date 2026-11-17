<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use App\Services\Rbac\RolePermissionManager;
use App\Support\Rbac\PermissionRules;
use App\Support\Rbac\RoleCatalogue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RolePermissionController extends Controller
{
    private function authorizeManager(Request $request): void
    {
        abort_unless($request->user()?->isActive() && $request->user()->isAdmin(), 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeManager($request);
        $roles = RoleCatalogue::configurableRoles();
        $request->validate(['role' => ['nullable', Rule::in(array_keys($roles))], 'user_id' => ['nullable', 'integer']]);
        $selectedRole = $request->input('role') ?: 'reception';
        $permissions = Permission::query()->orderBy('module')->orderBy('label')->get();
        $permissionsByModule = $permissions->groupBy('module');
        $assignedPermissionIds = DB::table('role_permission')->where('role', $selectedRole)->pluck('permission_id')->map(fn ($id) => (int) $id)->all();
        $snapshot = PermissionRules::snapshot($selectedRole,
            (int) (DB::table('rbac_role_states')->where('role', $selectedRole)->value('version') ?? 0),
            $assignedPermissionIds, $permissions->pluck('name', 'id')->all());
        $roleUsers = User::query()->where('role', $selectedRole)->orderBy('name')->get();
        $selectedUser = $request->filled('user_id') ? $roleUsers->firstWhere('id', (int) $request->input('user_id')) : null;
        abort_if($request->filled('user_id') && ! $selectedUser, 404);
        $directIds = $selectedUser ? $selectedUser->permissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->all() : [];
        $effectiveGrants = $permissions->filter(fn ($permission) => in_array((int) $permission->id, $assignedPermissionIds, true) || in_array((int) $permission->id, $directIds, true))
            ->map(function ($permission) use ($assignedPermissionIds, $directIds) {
                $roleGrant = in_array((int) $permission->id, $assignedPermissionIds, true);
                $directGrant = in_array((int) $permission->id, $directIds, true);
                return ['permission' => $permission, 'source' => $roleGrant && $directGrant ? 'Role + direct user' : ($directGrant ? 'Direct user' : 'Role')];
            });
        $audits = DB::table('rbac_role_audits')->where('role', $selectedRole)->orderByDesc('id')->limit(10)->get();
        $directOnly = PermissionRules::DIRECT_ONLY;
        return view('admin.role-permissions.index', compact('roles', 'selectedRole', 'permissionsByModule', 'assignedPermissionIds', 'snapshot', 'roleUsers', 'selectedUser', 'effectiveGrants', 'audits', 'directOnly'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);
        $validated = $request->validate([
            'role' => ['required', Rule::in(array_keys(RoleCatalogue::configurableRoles()))],
            'matrix_present' => ['required', 'in:1'],
            'snapshot' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'distinct', 'exists:permissions,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $changed = app(RolePermissionManager::class)->save($request->user(), $validated['role'], $validated['permissions'] ?? [], $validated['snapshot'], $validated['reason'] ?? null);
        return redirect()->route('admin.role-permissions.index', ['role' => $validated['role']])
            ->with('success', $changed ? 'Role permissions saved. The change reason, actor and permission differences were recorded.' : 'No permission changes were needed.');
    }
}
