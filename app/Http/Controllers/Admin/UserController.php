<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    private const REFUND_PROCESS =
        'billing.investigation-refund.process';

    private const REFUND_VERIFY =
        'billing.investigation-refund.verify';

    private const REFUND_PERMISSIONS = [
        self::REFUND_PROCESS,
        self::REFUND_VERIFY,
    ];

    public function index(): View
    {
        $users = User::query()
            ->orderBy('name')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $roles = $this->roles();
        $designations = $this->designations();

        $pharmacyPermissions = Permission::query()
            ->where('module', 'pharmacy')
            ->orderBy('label')
            ->get();

        return view('admin.users.create', compact(
            'roles',
            'designations',
            'pharmacyPermissions'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
            ],
            'email' => [
                'required', 'email', 'max:255',
                'unique:users,email',
            ],
            'role' => [
                'required',
                Rule::in(array_keys($this->roles())),
            ],
            'designation' => [
                'nullable', 'string',
                Rule::in(array_keys($this->designations())),
            ],
            'password' => [
                'required', 'string', 'min:8', 'confirmed',
            ],
            'pharmacy_permissions' => [
                'nullable', 'array',
            ],
            'pharmacy_permissions.*' => [
                'string',
                'distinct',
                'exists:permissions,name',
            ],
        ]);

        if (
            $validated['role'] === 'super_admin'
            && !$this->currentUserIsSuperAdmin()
        ) {
            return back()->withInput()->withErrors([
                'role' =>
                    'Only a Super Administrator can assign the Super Administrator role.',
            ]);
        }

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'designation' =>
                    $validated['designation'] ?? null,
                'password' => Hash::make(
                    $validated['password']
                ),
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            if ($validated['role'] === 'pharmacy') {
                $selected = $validated[
                    'pharmacy_permissions'
                ] ?? [];

                $permissionIds = Permission::query()
                    ->where('module', 'pharmacy')
                    ->whereIn('name', $selected)
                    ->pluck('id')
                    ->all();

                if ($permissionIds) {
                    $user->permissions()
                        ->syncWithoutDetaching($permissionIds);
                }
            }

            return $user;
        });

        return redirect()
            ->route('admin.users.index')
            ->with(
                'success',
                'User ' . $user->name .
                ' created successfully.'
            );
    }

    public function edit(User $user): View
    {
        $this->ensureCanManageUser($user);

        $roles = $this->roles();
        $designations = $this->designations();

        $pharmacyPermissions = Permission::query()
            ->where('module', 'pharmacy')
            ->orderBy('label')
            ->get();

        $userPermissionNames = $user->permissions()
            ->pluck('permissions.name')
            ->all();

        $canManageRefundPermissions =
            $this->canManageRefundPermissions($user);

        $refundPermissions = $canManageRefundPermissions
            ? Permission::query()
                ->whereIn(
                    'name',
                    self::REFUND_PERMISSIONS
                )
                ->orderBy('name')
                ->get()
            : collect();

        return view('admin.users.edit', compact(
            'user',
            'roles',
            'designations',
            'pharmacyPermissions',
            'userPermissionNames',
            'refundPermissions',
            'canManageRefundPermissions'
        ));
    }

    public function update(
        Request $request,
        User $user
    ): RedirectResponse {
        $this->ensureCanManageUser($user);

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
            ],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')
                    ->ignore($user->id),
            ],
            'role' => [
                'required',
                Rule::in(array_keys($this->roles())),
            ],
            'designation' => [
                'nullable', 'string',
                Rule::in(array_keys($this->designations())),
            ],
            'pharmacy_permissions' => [
                'nullable', 'array',
            ],
            'pharmacy_permissions.*' => [
                'string',
                'distinct',
                'exists:permissions,name',
            ],
            'manage_refund_permissions' => [
                'sometimes',
                'required',
                'in:1',
            ],
            'refund_permissions' => [
                'sometimes',
                'array',
            ],
            'refund_permissions.*' => [
                'string',
                'distinct',
                Rule::in(self::REFUND_PERMISSIONS),
            ],
        ]);

        if (
            $user->isSuperAdmin()
            && $validated['role'] !== 'super_admin'
        ) {
            return back()->withInput()->withErrors([
                'role' =>
                    'The Super Administrator role cannot be removed from this account.',
            ]);
        }

        if (
            $validated['role'] === 'super_admin'
            && !$this->currentUserIsSuperAdmin()
        ) {
            return back()->withInput()->withErrors([
                'role' =>
                    'Only a Super Administrator can assign the Super Administrator role.',
            ]);
        }

        if (
            auth()->id() === $user->id
            && !in_array(
                $validated['role'],
                ['admin', 'super_admin'],
                true
            )
        ) {
            return back()->withInput()->withErrors([
                'role' =>
                    'You cannot remove your own administrator access.',
            ]);
        }

        $refundFieldsPresent =
            $request->exists('manage_refund_permissions')
            || $request->exists('refund_permissions');

        if (
            $refundFieldsPresent
            && !$this->canManageRefundPermissions($user)
        ) {
            abort(
                403,
                'You cannot modify direct financial permissions.'
            );
        }

        $shouldUpdateRefunds =
            $this->canManageRefundPermissions($user)
            && $request->input(
                'manage_refund_permissions'
            ) === '1';

        if (
            $this->canManageRefundPermissions($user)
            && $request->exists('refund_permissions')
            && !$shouldUpdateRefunds
        ) {
            abort(
                403,
                'Invalid financial permission submission.'
            );
        }

        $selectedRefunds = $shouldUpdateRefunds
            ? ($validated['refund_permissions'] ?? [])
            : [];

        if (
            in_array(
                self::REFUND_PROCESS,
                $selectedRefunds,
                true
            )
            && in_array(
                self::REFUND_VERIFY,
                $selectedRefunds,
                true
            )
        ) {
            return back()->withInput()->withErrors([
                'refund_permissions' =>
                    'Refund processing and independent verification cannot both be directly assigned to one user.',
            ]);
        }

        if ($shouldUpdateRefunds) {
            $existingRefundPermissions = Permission::query()
                ->whereIn(
                    'name',
                    self::REFUND_PERMISSIONS
                )
                ->pluck('name')
                ->all();

            if (count($existingRefundPermissions) !== 2) {
                return back()->withInput()->withErrors([
                    'refund_permissions' =>
                        'Required refund permissions are missing from the permission registry.',
                ]);
            }
        }

        DB::transaction(function () use (
            $user,
            $validated,
            $shouldUpdateRefunds,
            $selectedRefunds
        ) {
            // Lock the account row while applying changes.
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedUser->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'designation' =>
                    $validated['designation'] ?? null,
            ]);

            /*
             * Preserve original pharmacy-permission workflow.
             * Only pharmacy permissions are replaced here.
             */
            $pharmacyIds = Permission::query()
                ->where('module', 'pharmacy')
                ->pluck('id')
                ->all();

            $lockedUser->permissions()->detach($pharmacyIds);

            if ($validated['role'] === 'pharmacy') {
                $selectedPharmacy = $validated[
                    'pharmacy_permissions'
                ] ?? [];

                $selectedPharmacyIds = Permission::query()
                    ->where('module', 'pharmacy')
                    ->whereIn('name', $selectedPharmacy)
                    ->pluck('id')
                    ->all();

                if ($selectedPharmacyIds) {
                    $lockedUser->permissions()
                        ->syncWithoutDetaching(
                            $selectedPharmacyIds
                        );
                }
            }

            /*
             * Financial permissions are independent of
             * pharmacy and other module permissions.
             */
            if ($shouldUpdateRefunds) {
                $refundIds = Permission::query()
                    ->whereIn(
                        'name',
                        self::REFUND_PERMISSIONS
                    )
                    ->pluck('id')
                    ->all();

                $before = $lockedUser->permissions()
                    ->whereIn(
                        'permissions.name',
                        self::REFUND_PERMISSIONS
                    )
                    ->pluck('permissions.name')
                    ->all();

                $lockedUser->permissions()
                    ->detach($refundIds);

                $selectedIds = Permission::query()
                    ->whereIn(
                        'name',
                        $selectedRefunds
                    )
                    ->pluck('id')
                    ->all();

                if ($selectedIds) {
                    $lockedUser->permissions()
                        ->syncWithoutDetaching(
                            $selectedIds
                        );
                }

                $added = array_values(
                    array_diff(
                        $selectedRefunds,
                        $before
                    )
                );

                $removed = array_values(
                    array_diff(
                        $before,
                        $selectedRefunds
                    )
                );

                if ($added || $removed) {
                    Log::notice(
                        'Direct investigation refund permissions changed',
                        [
                            'actor_id' => auth()->id(),
                            'target_user_id' => $lockedUser->id,
                            'added' => $added,
                            'removed' => $removed,
                            'recorded_at' => now()->toIso8601String(),
                        ]
                    );
                }
            }
        }, 3);

        return redirect()
            ->route('admin.users.edit', $user)
            ->with(
                'success',
                'User details and permissions updated successfully.'
            );
    }

    public function resetPassword(
        Request $request,
        User $user
    ): RedirectResponse {
        $this->ensureCanManageUser($user);

        $validated = $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $user->update([
            'password' => Hash::make(
                $validated['password']
            ),
        ]);

        return redirect()
            ->route('admin.users.edit', $user)
            ->with(
                'success',
                'Password reset successfully.'
            );
    }

    public function toggleStatus(
        User $user
    ): RedirectResponse {
        if (
            $user->isSuperAdmin()
            && !$this->currentUserIsSuperAdmin()
        ) {
            return redirect()
                ->route('admin.users.index')
                ->with(
                    'error',
                    'Only a Super Administrator can change the status of a Super Administrator account.'
                );
        }

        if (auth()->id() === $user->id) {
            return redirect()
                ->route('admin.users.index')
                ->with(
                    'error',
                    'You cannot deactivate your own account.'
                );
        }

        if (
            $user->isSuperAdmin()
            && $user->is_active
        ) {
            return redirect()
                ->route('admin.users.index')
                ->with(
                    'error',
                    'A Super Administrator account cannot be deactivated from User Management.'
                );
        }

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        $status = $user->is_active
            ? 'activated'
            : 'deactivated';

        return redirect()
            ->route('admin.users.index')
            ->with(
                'success',
                'User ' . $user->name . ' ' .
                $status . ' successfully.'
            );
    }

    private function currentUserIsSuperAdmin(): bool
    {
        $currentUser = auth()->user();

        return $currentUser instanceof User
            && $currentUser->isSuperAdmin();
    }

    private function canManageRefundPermissions(
        User $target
    ): bool {
        return $this->currentUserIsSuperAdmin()
            && (int) auth()->id() !== (int) $target->id;
    }

    private function ensureCanManageUser(
        User $user
    ): void {
        if (
            $user->isSuperAdmin()
            && !$this->currentUserIsSuperAdmin()
        ) {
            abort(
                403,
                'Only a Super Administrator can manage a Super Administrator account.'
            );
        }
    }

    private function roles(): array
    {
        $roles = [
            'admin' => 'Administrator',
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
            'hospital_administrator' => 'Hospital Administrator',
            'medical_superintendent' => 'Medical Superintendent',
            'reception_supervisor' => 'Reception Supervisor',
            'nursing_supervisor' => 'Nursing Supervisor',
            'ipd_billing' => 'IPD Billing',
            'cashier' => 'Cashier',
            'finance_manager' => 'Finance Manager',
            'pharmacy_manager' => 'Pharmacy Manager',
            'sonology' => 'Sonology',
            'asset_manager' => 'Asset Manager',
            'mis_reporting' => 'MIS / Reporting',
            'auditor' => 'Internal Auditor / Read Only',
            'management' => 'Management / Read Only',
        ];

        if ($this->currentUserIsSuperAdmin()) {
            $roles = [
                'super_admin' => 'Super Administrator',
                ...$roles,
            ];
        }

        return $roles;
    }

    private function designations(): array
    {
        return [
            'Medical Superintendent' => 'Medical Superintendent',
            'Deputy Medical Superintendent' => 'Deputy Medical Superintendent',
            'Administrator' => 'Administrator',
            'Nursing Superintendent' => 'Nursing Superintendent',
            'Manager' => 'Manager',
            'Accountant' => 'Accountant',
            'Assistant Accountant' => 'Assistant Accountant',
            'Pharmacy In-charge' => 'Pharmacy In-charge',
            'Laboratory In-charge' => 'Laboratory In-charge',
            'Radiology In-charge' => 'Radiology In-charge',
            'Reception In-charge' => 'Reception In-charge',
            'Stores In-charge' => 'Stores In-charge',
            'Maintenance In-charge' => 'Maintenance In-charge',
            'Other' => 'Other',
        ];
    }
}
