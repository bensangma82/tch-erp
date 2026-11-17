<?php

namespace App\Services\Rbac;

use App\Models\Permission;
use App\Models\User;
use App\Support\Rbac\PermissionRules;
use App\Support\Rbac\RoleCatalogue;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RolePermissionManager
{
    public function snapshot(string $role): string
    {
        return PermissionRules::snapshot($role,
            (int) (DB::table('rbac_role_states')->where('role', $role)->value('version') ?? 0),
            DB::table('role_permission')->where('role', $role)->pluck('permission_id')->all(),
            Permission::query()->pluck('name', 'id')->all());
    }

    public function save(User $actor, string $role, array $selectedIds, string $snapshot, ?string $reason): bool
    {
        abort_unless($actor->isActive() && $actor->isAdmin(), 403);
        if (! array_key_exists($role, RoleCatalogue::configurableRoles())) {
            throw ValidationException::withMessages(['role' => 'This role cannot be configured through the permission matrix.']);
        }
        return DB::transaction(function () use ($actor, $role, $selectedIds, $snapshot, $reason) {
            DB::table('rbac_role_states')->insertOrIgnore(['role' => $role, 'version' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $state = DB::table('rbac_role_states')->where('role', $role)->lockForUpdate()->first();
            $before = DB::table('role_permission')->where('role', $role)->pluck('permission_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            $catalogue = Permission::query()->pluck('name', 'id')->all();
            $current = PermissionRules::snapshot($role, (int) $state->version, $before, $catalogue);
            if (! hash_equals($current, $snapshot)) {
                throw ValidationException::withMessages(['snapshot' => 'These permissions or the permission catalogue changed after you opened the page. Review the refreshed assignments before saving again.']);
            }
            $selectedIds = array_values(array_unique(array_map('intval', $selectedIds)));
            if (array_diff($selectedIds, array_map('intval', array_keys($catalogue)))) {
                throw ValidationException::withMessages(['permissions' => 'An unknown permission was submitted. Reload the page.']);
            }
            $names = array_map(fn ($id) => $catalogue[$id], $selectedIds);
            PermissionRules::validateRoleSelection($names);
            // Preserve any historical role grants for direct-only permissions. They
            // are displayed read-only and are never created or revoked here.
            $protected = array_map('intval', array_keys(array_filter($catalogue, fn ($name) => in_array($name, PermissionRules::DIRECT_ONLY, true))));
            $after = array_values(array_unique([...$selectedIds, ...array_intersect($before, $protected)]));
            sort($after, SORT_NUMERIC);
            if ($before === $after) {
                return false;
            }
            $reason = trim($reason ?? '');
            if (mb_strlen($reason) < 10 || mb_strlen($reason) > 1000) {
                throw ValidationException::withMessages(['reason' => 'Enter a change reason between 10 and 1000 characters.']);
            }
            $added = array_values(array_diff($after, $before));
            $removed = array_values(array_diff($before, $after));
            if ($removed) {
                DB::table('role_permission')->where('role', $role)->whereIn('permission_id', $removed)->delete();
            }
            foreach ($added as $id) {
                DB::table('role_permission')->insert(['role' => $role, 'permission_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('rbac_role_states')->where('role', $role)->update(['version' => (int) $state->version + 1, 'updated_at' => now()]);
            DB::table('rbac_role_audits')->insert([
                'role' => $role, 'actor_id' => $actor->getKey(), 'actor_name' => $actor->name,
                'reason' => $reason, 'before_permission_ids' => json_encode($before), 'after_permission_ids' => json_encode($after),
                'added_permissions' => json_encode(array_map(fn ($id) => $catalogue[$id] ?? 'Permission #'.$id, $added)),
                'removed_permissions' => json_encode(array_map(fn ($id) => $catalogue[$id] ?? 'Permission #'.$id, $removed)),
                'created_at' => now(),
            ]);
            return true;
        });
    }
}
