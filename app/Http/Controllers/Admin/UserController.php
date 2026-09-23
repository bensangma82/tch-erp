<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display ERP users.
     */
    public function index(): View
    {
        $users = User::query()
            ->orderBy('name')
            ->get();

        return view(
            'admin.users.index',
            compact('users')
        );
    }


    /**
     * Show user creation form.
     */
    public function create(): View
    {
        $roles = $this->roles();

        $designations = $this->designations();

        $pharmacyPermissions = Permission::query()
            ->where(
                'module',
                'pharmacy'
            )
            ->orderBy('label')
            ->get();

        return view(
            'admin.users.create',
            compact(
                'roles',
                'designations',
                'pharmacyPermissions'
            )
        );
    }


    /**
     * Create ERP user.
     */
    public function store(
        Request $request
    ): RedirectResponse {

        $validated = $request->validate([

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'role' => [
                'required',

                Rule::in(
                    array_keys(
                        $this->roles()
                    )
                ),
            ],

            'designation' => [
                'nullable',
                'string',

                Rule::in(
                    array_keys(
                        $this->designations()
                    )
                ),
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'pharmacy_permissions' => [
                'nullable',
                'array',
            ],

            'pharmacy_permissions.*' => [
                'string',
                'distinct',
                'exists:permissions,name',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Protect Super Administrator Assignment
        |--------------------------------------------------------------------------
        |
        | Only an existing Super Administrator may create another
        | Super Administrator account.
        |
        */

        if (
            $validated['role'] === 'super_admin'
            &&
            ! $this->currentUserIsSuperAdmin()
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'role' =>
                        'Only a Super Administrator can assign the Super Administrator role.',
                ]);
        }


        $user = DB::transaction(
            function () use (
                $validated
            ) {

                /*
                |--------------------------------------------------------------------------
                | Create User
                |--------------------------------------------------------------------------
                */

                $user = User::create([

                    'name' =>
                        $validated['name'],

                    'email' =>
                        $validated['email'],

                    'role' =>
                        $validated['role'],

                    'designation' =>
                        $validated['designation'] ?? null,

                    'password' =>
                        Hash::make(
                            $validated['password']
                        ),

                    'email_verified_at' =>
                        now(),

                    'is_active' =>
                        true,
                ]);


                /*
                |--------------------------------------------------------------------------
                | Pharmacy Permissions
                |--------------------------------------------------------------------------
                |
                | Permissions are assigned only when the selected role is
                | Pharmacy.
                |
                | New pharmacy users do NOT automatically receive every
                | permission.
                |
                */

                if (
                    $validated['role']
                    ===
                    'pharmacy'
                ) {

                    $selectedPermissionNames =
                        $validated['pharmacy_permissions']
                        ?? [];


                    if (
                        count(
                            $selectedPermissionNames
                        ) > 0
                    ) {

                        $permissionIds = Permission::query()
                            ->where(
                                'module',
                                'pharmacy'
                            )
                            ->whereIn(
                                'name',
                                $selectedPermissionNames
                            )
                            ->pluck('id');


                        $user->permissions()
                            ->syncWithoutDetaching(
                                $permissionIds
                            );
                    }
                }


                return $user;
            }
        );


        return redirect()
            ->route(
                'admin.users.index'
            )
            ->with(
                'success',
                'User '
                . $user->name
                . ' created successfully.'
            );
    }


    /**
     * Show edit form.
     */
    public function edit(
        User $user
    ): View {

        /*
        |--------------------------------------------------------------------------
        | Protect Super Administrator Account
        |--------------------------------------------------------------------------
        |
        | Ordinary administrators must not be able to open the edit screen
        | for a Super Administrator account.
        |
        */

        $this->ensureCanManageUser(
            $user
        );


        $roles = $this->roles();

        $designations = $this->designations();

        $pharmacyPermissions = Permission::query()
            ->where(
                'module',
                'pharmacy'
            )
            ->orderBy('label')
            ->get();


        $userPermissionNames =
            $user->permissions()
                ->pluck(
                    'permissions.name'
                )
                ->all();


        return view(
            'admin.users.edit',
            compact(
                'user',
                'roles',
                'designations',
                'pharmacyPermissions',
                'userPermissionNames'
            )
        );
    }


    /**
     * Update user and module permissions.
     */
    public function update(
        Request $request,
        User $user
    ): RedirectResponse {

        /*
        |--------------------------------------------------------------------------
        | Protect Super Administrator Account
        |--------------------------------------------------------------------------
        */

        $this->ensureCanManageUser(
            $user
        );


        $validated = $request->validate([

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',

                Rule::unique(
                    'users',
                    'email'
                )->ignore(
                    $user->id
                ),
            ],

            'role' => [
                'required',

                Rule::in(
                    array_keys(
                        $this->roles()
                    )
                ),
            ],

            'designation' => [
                'nullable',
                'string',

                Rule::in(
                    array_keys(
                        $this->designations()
                    )
                ),
            ],

            'pharmacy_permissions' => [
                'nullable',
                'array',
            ],

            'pharmacy_permissions.*' => [
                'string',
                'distinct',
                'exists:permissions,name',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Protect Existing Super Administrator Role
        |--------------------------------------------------------------------------
        |
        | Once an account is a Super Administrator, this administration
        | screen must not be used to downgrade it.
        |
        */

        if (
            $user->isSuperAdmin()
            &&
            $validated['role'] !== 'super_admin'
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'role' =>
                        'The Super Administrator role cannot be removed from this account.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Protect Super Administrator Assignment
        |--------------------------------------------------------------------------
        */

        if (
            $validated['role'] === 'super_admin'
            &&
            ! $this->currentUserIsSuperAdmin()
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'role' =>
                        'Only a Super Administrator can assign the Super Administrator role.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Protect Current Administrator
        |--------------------------------------------------------------------------
        |
        | Existing administrator behaviour is preserved.
        |
        */

        if (
            auth()->id()
            ===
            $user->id
            &&
            ! in_array(
                $validated['role'],
                [
                    'admin',
                    'super_admin',
                ],
                true
            )
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'role' =>
                        'You cannot remove your own administrator access.',
                ]);
        }


        DB::transaction(
            function () use (
                $validated,
                $user
            ) {

                /*
                |--------------------------------------------------------------------------
                | Update Account
                |--------------------------------------------------------------------------
                */

                $user->update([

                    'name' =>
                        $validated['name'],

                    'email' =>
                        $validated['email'],

                    'role' =>
                        $validated['role'],

                    'designation' =>
                        $validated['designation'] ?? null,
                ]);


                /*
                |--------------------------------------------------------------------------
                | Remove Existing Pharmacy Permissions
                |--------------------------------------------------------------------------
                */

                $allPharmacyPermissionIds =
                    Permission::query()
                        ->where(
                            'module',
                            'pharmacy'
                        )
                        ->pluck('id');


                $user->permissions()
                    ->detach(
                        $allPharmacyPermissionIds
                    );


                /*
                |--------------------------------------------------------------------------
                | Reassign Selected Pharmacy Permissions
                |--------------------------------------------------------------------------
                */

                if (
                    $validated['role']
                    ===
                    'pharmacy'
                ) {

                    $selectedPermissionNames =
                        $validated['pharmacy_permissions']
                        ?? [];


                    if (
                        count(
                            $selectedPermissionNames
                        ) > 0
                    ) {

                        $selectedPermissionIds =
                            Permission::query()
                                ->where(
                                    'module',
                                    'pharmacy'
                                )
                                ->whereIn(
                                    'name',
                                    $selectedPermissionNames
                                )
                                ->pluck('id');


                        $user->permissions()
                            ->syncWithoutDetaching(
                                $selectedPermissionIds
                            );
                    }
                }
            }
        );


        return redirect()
            ->route(
                'admin.users.edit',
                $user
            )
            ->with(
                'success',
                'User details and permissions updated successfully.'
            );
    }


    /**
     * Reset user's password.
     */
    public function resetPassword(
        Request $request,
        User $user
    ): RedirectResponse {

        /*
        |--------------------------------------------------------------------------
        | Protect Super Administrator Account
        |--------------------------------------------------------------------------
        |
        | Ordinary administrators must not be able to reset the password of
        | a Super Administrator.
        |
        */

        $this->ensureCanManageUser(
            $user
        );


        $validated =
            $request->validate([

                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                ],
            ]);


        $user->update([

            'password' =>
                Hash::make(
                    $validated['password']
                ),
        ]);


        return redirect()
            ->route(
                'admin.users.edit',
                $user
            )
            ->with(
                'success',
                'Password reset successfully.'
            );
    }


    /**
     * Activate or deactivate user.
     */
    public function toggleStatus(
        User $user
    ): RedirectResponse {

        /*
        |--------------------------------------------------------------------------
        | Protect Super Administrator Account
        |--------------------------------------------------------------------------
        */

        if (
            $user->isSuperAdmin()
            &&
            ! $this->currentUserIsSuperAdmin()
        ) {

            return redirect()
                ->route(
                    'admin.users.index'
                )
                ->with(
                    'error',
                    'Only a Super Administrator can change the status of a Super Administrator account.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Prevent Self-Deactivation
        |--------------------------------------------------------------------------
        */

        if (
            auth()->id()
            ===
            $user->id
        ) {

            return redirect()
                ->route(
                    'admin.users.index'
                )
                ->with(
                    'error',
                    'You cannot deactivate your own account.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Keep Existing Super Administrator Active
        |--------------------------------------------------------------------------
        |
        | Even another Super Administrator cannot deactivate a protected
        | Super Administrator through this screen.
        |
        */

        if (
            $user->isSuperAdmin()
            &&
            $user->is_active
        ) {

            return redirect()
                ->route(
                    'admin.users.index'
                )
                ->with(
                    'error',
                    'A Super Administrator account cannot be deactivated from User Management.'
                );
        }


        $user->update([

            'is_active' =>
                ! $user->is_active,
        ]);


        $status =
            $user->is_active
                ? 'activated'
                : 'deactivated';


        return redirect()
            ->route(
                'admin.users.index'
            )
            ->with(
                'success',
                'User '
                . $user->name
                . ' '
                . $status
                . ' successfully.'
            );
    }


    /**
     * Determine whether the currently authenticated user is a
     * Super Administrator.
     */
    private function currentUserIsSuperAdmin(): bool
    {
        $currentUser = auth()->user();

        return $currentUser
            instanceof User
            &&
            $currentUser->isSuperAdmin();
    }


    /**
     * Ensure the current authenticated user is allowed to manage
     * the supplied user account.
     */
    private function ensureCanManageUser(
        User $user
    ): void {

        if (
            $user->isSuperAdmin()
            &&
            ! $this->currentUserIsSuperAdmin()
        ) {
            abort(
                403,
                'Only a Super Administrator can manage a Super Administrator account.'
            );
        }
    }


    /**
     * Available ERP roles.
     */
    private function roles(): array
    {
        $roles = [

            'admin' =>
                'Administrator',

            'reception' =>
                'Reception',

            'nursing' =>
                'Nursing',

            'doctor' =>
                'Doctor',

            'billing' =>
                'Billing',

            'finance' =>
                'Finance / Accounts',

            'laboratory' =>
                'Laboratory',

            'radiology' =>
                'Radiology',

            'pharmacy' =>
                'Pharmacy',

            'stores' =>
                'Stores / Inventory',

            'hr' =>
                'HR',

            'medical_records' =>
                'Medical Records',

            'emergency' =>
                'Emergency',

            'ipd' =>
                'IPD / Ward',

            'management' =>
                'Management / Read Only',
        ];


        /*
        |--------------------------------------------------------------------------
        | Super Administrator Role
        |--------------------------------------------------------------------------
        |
        | The role is intentionally hidden from ordinary administrators.
        | Only an existing Super Administrator can see or assign it.
        |
        */

        if (
            $this->currentUserIsSuperAdmin()
        ) {
            $roles = [
                'super_admin' =>
                    'Super Administrator',

                ...$roles,
            ];
        }


        return $roles;
    }


    /**
     * Available hospital designations.
     */
    private function designations(): array
    {
        return [

            'Medical Superintendent' =>
                'Medical Superintendent',

            'Deputy Medical Superintendent' =>
                'Deputy Medical Superintendent',

            'Administrator' =>
                'Administrator',

            'Nursing Superintendent' =>
                'Nursing Superintendent',

            'Manager' =>
                'Manager',

            'Accountant' =>
                'Accountant',

            'Assistant Accountant' =>
                'Assistant Accountant',

            'Pharmacy In-charge' =>
                'Pharmacy In-charge',

            'Laboratory In-charge' =>
                'Laboratory In-charge',

            'Radiology In-charge' =>
                'Radiology In-charge',

            'Reception In-charge' =>
                'Reception In-charge',

            'Stores In-charge' =>
                'Stores In-charge',

            'Maintenance In-charge' =>
                'Maintenance In-charge',

            'Other' =>
                'Other',
        ];
    }
}
