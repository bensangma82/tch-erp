<?php

namespace App\Services\Administration;

use App\Models\Permission;
use Illuminate\Support\Facades\DB;

// One-time migration helper. Never run automatically on ordinary requests or seeding.
class AdministrationWorkflowGrantMapper
{
    public function apply(): void
    {
        DB::transaction(function () {
            $coordinators = ['hospital_administrator', 'medical_superintendent'];
            $ids = Permission::query()->pluck('id', 'name')->all();
            foreach (['role' => 'role_permission', 'user' => 'permission_user'] as $type => $table) {
                $key = $type === 'role' ? 'role' : 'user_id';
                $legacy = DB::table($table)->join('permissions', 'permissions.id', '=', $table.'.permission_id')
                    ->whereIn('permissions.name', ['administration.execute', 'administration.verify'])
                    ->get([$table.'.'.$key.' as target_key', 'permissions.name'])->groupBy('target_key');
                foreach ($legacy as $target => $rows) {
                    $role = $type === 'role' ? (string) $target : DB::table('users')->where('id', $target)->value('role');
                    if ($type === 'role') {
                        DB::table('rbac_role_states')->insertOrIgnore(['role' => $target, 'version' => 0, 'created_at' => now(), 'updated_at' => now()]);
                        $state = DB::table('rbac_role_states')->where('role', $target)->lockForUpdate()->first();
                    }
                    $before = DB::table($table)->where($key, $target)->pluck('permission_id')->map(fn ($id) => (int) $id)->all();
                    $names = [];
                    if ($rows->contains('name', 'administration.execute')) {
                        $names[] = 'administration.complete';
                        if (in_array($role, $coordinators, true)) {
                            $names[] = 'administration.assign';
                        }
                    }
                    if ($rows->contains('name', 'administration.verify')) {
                        $names[] = 'administration.verify-completion';
                        $names[] = 'administration.close';
                    }
                    $added = [];
                    foreach ($names as $name) {
                        if (! in_array((int) $ids[$name], $before, true)) {
                            DB::table($table)->insert(['permission_id' => $ids[$name], $key => $target, 'created_at' => now(), 'updated_at' => now()]);
                            $added[] = $name;
                        }
                    }
                    if (! $added) {
                        continue;
                    }
                    DB::table('administration_workflow_grant_mappings')->insert([
                        'target_type' => $type, 'target_key' => (string) $target,
                        'added_permissions' => json_encode($added), 'created_at' => now(),
                    ]);
                    if ($type === 'role') {
                        $after = [...$before, ...array_map(fn ($name) => (int) $ids[$name], $added)];
                        sort($before, SORT_NUMERIC);
                        sort($after, SORT_NUMERIC);
                        DB::table('rbac_role_states')->where('role', $target)->update(['version' => (int) $state->version + 1, 'updated_at' => now()]);
                        DB::table('rbac_role_audits')->insert([
                            'role' => $target, 'actor_id' => null, 'actor_name' => 'Administration workflow migration',
                            'reason' => 'One-time mapping of existing administrative responsibilities to split workflow permissions. Non-coordinator executors receive completion only.',
                            'before_permission_ids' => json_encode($before), 'after_permission_ids' => json_encode($after),
                            'added_permissions' => json_encode($added), 'removed_permissions' => '[]', 'created_at' => now(),
                        ]);
                    }
                }
            }
        });
    }
}
