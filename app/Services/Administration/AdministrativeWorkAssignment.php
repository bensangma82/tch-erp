<?php

namespace App\Services\Administration;

use App\Models\AdministrativeRequest;
use App\Models\User;
use App\Support\AdministrativeRequestAccess;
use App\Support\Rbac\RoleCatalogue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdministrativeWorkAssignment
{
    public const CATEGORIES = [
        'clinical' => 'Clinical / Medical', 'nursing' => 'Nursing',
        'hr' => 'Human Resources', 'finance' => 'Finance / Accounts',
        'pharmacy' => 'Pharmacy', 'laboratory' => 'Laboratory',
        'radiology' => 'Radiology / Sonology', 'reception' => 'Reception / Patient Services',
        'stores' => 'Stores / Inventory', 'procurement' => 'Procurement / Purchasing',
        'maintenance' => 'Maintenance / Facilities', 'projects' => 'Projects / Construction',
        'it' => 'IT / Digital Systems', 'housekeeping' => 'Housekeeping',
        'security' => 'Security', 'non_clinical' => 'General Administration', 'other' => 'Other',
    ];

    public function eligibleUsers(): Builder
    {
        return User::query()->where('is_active', true)->where(function ($query) {
            $query->whereIn('role', ['admin', 'super_admin'])->orWhere(function ($member) {
                foreach (['administration.view', 'administration.complete'] as $name) {
                    $member->where(function ($grant) use ($name) {
                        $grant->whereExists(function ($direct) use ($name) {
                            $direct->selectRaw('1')->from('permission_user as assignment_direct')
                                ->join('permissions as assignment_dp', 'assignment_dp.id', '=', 'assignment_direct.permission_id')
                                ->whereColumn('assignment_direct.user_id', 'users.id')->where('assignment_dp.name', $name);
                        })->orWhereExists(function ($role) use ($name) {
                            $role->selectRaw('1')->from('role_permission as assignment_role')
                                ->join('permissions as assignment_rp', 'assignment_rp.id', '=', 'assignment_role.permission_id')
                                ->whereColumn('assignment_role.role', 'users.role')->where('assignment_rp.name', $name);
                        });
                    });
                }
            });
        })->orderBy('name')->orderBy('id');
    }

    public function assign(User $actor, int $requestId, array $data, bool $reassign = false): void
    {
        DB::transaction(function () use ($actor, $requestId, $data, $reassign) {
            $item = AdministrativeRequest::query()->lockForUpdate()->findOrFail($requestId);
            abort_unless(AdministrativeRequestAccess::allows($actor, 'assign', $item), 403);
            $allowedStates = $reassign ? ['execution_in_progress'] : ['ms_approved', 'higher_approved'];
            if (! in_array($item->status, $allowedStates, true)) {
                throw ValidationException::withMessages(['status' => $reassign
                    ? 'Only work currently in progress can be reassigned.'
                    : 'Only approved requests can enter execution.']);
            }
            if (! array_key_exists($data['execution_category'] ?? '', self::CATEGORIES)) {
                throw ValidationException::withMessages(['execution_category' => 'Select a valid work category.']);
            }
            $latestId = (int) (DB::table('administrative_request_assignments')->where('administrative_request_id', $item->id)->max('id') ?? 0);
            if ($reassign && ((int) ($data['expected_assignment_id'] ?? -1) !== $latestId
                || (string) ($data['expected_assignee'] ?? '') !== (string) $item->assigned_to)) {
                throw ValidationException::withMessages(['assigned_to' => 'The assignment changed after this page was opened. Reload before reassigning.']);
            }
            $candidate = $this->eligibleUsers()->whereKey($data['assigned_to'] ?? 0)->lockForUpdate()->first();
            if (! $candidate) {
                throw ValidationException::withMessages(['assigned_to' => 'Select an active user with Administration View and Complete Assigned Work permissions.']);
            }
            if ($reassign && (string) $candidate->id === (string) $item->assigned_to) {
                throw ValidationException::withMessages(['assigned_to' => 'Select a different responsible person for reassignment.']);
            }
            $reason = $reassign ? trim($data['reassignment_reason'] ?? '') : 'Initial named assignment';
            if ($reassign && (mb_strlen($reason) < 5 || mb_strlen($reason) > 2000)) {
                throw ValidationException::withMessages(['reassignment_reason' => 'Enter a reassignment reason between 5 and 2000 characters.']);
            }
            $previous = $item->assigned_to ? User::query()->find($item->assigned_to) : null;
            $due = $data['required_by'] ?? $item->required_by?->toDateString();
            $instructions = array_key_exists('execution_remarks', $data) ? $data['execution_remarks'] : $item->execution_remarks;
            DB::table('administrative_request_assignments')->insert([
                'administrative_request_id' => $item->id,
                'event' => $reassign ? 'reassigned' : 'assigned',
                'previous_assignee_id' => $item->assigned_to,
                'previous_assignee_name' => $previous?->name,
                'assigned_to_id' => $candidate->id, 'assigned_to_name' => $candidate->name,
                'actor_id' => $actor->getKey(), 'actor_name' => $actor->name,
                'reason' => $reason, 'execution_category' => $data['execution_category'],
                'required_by' => $due, 'execution_remarks' => $instructions, 'created_at' => now(),
            ]);
            $item->update([
                'status' => 'execution_in_progress', 'assigned_to' => $candidate->id,
                'assigned_role' => mb_substr($candidate->designation ?: (RoleCatalogue::userRoles(true)[$candidate->role] ?? $candidate->role), 0, 100),
                'execution_category' => $data['execution_category'], 'required_by' => $due,
                // Keep the original start timestamp when handing work to another user.
                'execution_started_at' => $item->execution_started_at ?? now(),
                'execution_remarks' => $instructions,
            ]);
        });
    }
}
