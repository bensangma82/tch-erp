<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'designation',
    'is_active',
])]

#[Hidden([
    'password',
    'remember_token',
])]

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;


    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Role Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Check whether the user has a specific role.
     *
     * Super Administrator is treated as an administrator for compatibility
     * with existing role-based checks throughout the ERP.
     */
    public function hasRole(string $role): bool
    {
        if ($this->isSuperAdmin()) {
            return in_array(
                $role,
                [
                    'super_admin',
                    'admin',
                ],
                true
            );
        }

        return $this->role === $role;
    }


    /**
     * Check whether the user has any one of the supplied roles.
     *
     * Super Administrator automatically satisfies checks for either
     * "super_admin" or "admin".
     */
    public function hasAnyRole(array $roles): bool
    {
        if ($this->isSuperAdmin()) {
            return ! empty(
                array_intersect(
                    $roles,
                    [
                        'super_admin',
                        'admin',
                    ]
                )
            );
        }

        return in_array(
            $this->role,
            $roles,
            true
        );
    }


    /**
     * Determine whether the user is the system-level Super Administrator.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }


    /**
     * Determine whether the user has administrator-level access.
     *
     * Super Administrator is intentionally included so all existing
     * $user->isAdmin() checks continue to grant full system access.
     */
    public function isAdmin(): bool
    {
        return in_array(
            $this->role,
            [
                'admin',
                'super_admin',
            ],
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'permission_user'
        )->withTimestamps();
    }


    /**
     * Check whether this user has a specific permission.
     *
     * Permission may come from:
     * 1. Super Administrator / Administrator override
     * 2. Direct user permission
     * 3. Role-level permission
     */
    public function hasPermission(string $permission): bool
    {
        /*
        |--------------------------------------------------------------------------
        | Administrator Override
        |--------------------------------------------------------------------------
        */

        if ($this->isAdmin()) {
            return true;
        }


        /*
        |--------------------------------------------------------------------------
        | Direct User Permission
        |--------------------------------------------------------------------------
        */

        $hasDirectPermission = $this->permissions()
            ->where(
                'permissions.name',
                $permission
            )
            ->exists();


        if ($hasDirectPermission) {
            return true;
        }


        /*
        |--------------------------------------------------------------------------
        | Role Permission
        |--------------------------------------------------------------------------
        */

        return Permission::query()
            ->where(
                'permissions.name',
                $permission
            )
            ->whereExists(function ($query) {

                $query
                    ->selectRaw('1')
                    ->from('role_permission')
                    ->whereColumn(
                        'role_permission.permission_id',
                        'permissions.id'
                    )
                    ->where(
                        'role_permission.role',
                        $this->role
                    );
            })
            ->exists();
    }


    /**
     * Check whether this user has at least one permission
     * from the supplied list.
     *
     * Permission may come from:
     * 1. Super Administrator / Administrator override
     * 2. Direct user permission
     * 3. Role-level permission
     */
    public function hasAnyPermission(array $permissions): bool
    {
        /*
        |--------------------------------------------------------------------------
        | Administrator Override
        |--------------------------------------------------------------------------
        */

        if ($this->isAdmin()) {
            return true;
        }


        /*
        |--------------------------------------------------------------------------
        | Empty Permission List
        |--------------------------------------------------------------------------
        */

        if (empty($permissions)) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Direct User Permissions
        |--------------------------------------------------------------------------
        */

        $hasDirectPermission = $this->permissions()
            ->whereIn(
                'permissions.name',
                $permissions
            )
            ->exists();


        if ($hasDirectPermission) {
            return true;
        }


        /*
        |--------------------------------------------------------------------------
        | Role Permissions
        |--------------------------------------------------------------------------
        */

        return Permission::query()
            ->whereIn(
                'permissions.name',
                $permissions
            )
            ->whereExists(function ($query) {

                $query
                    ->selectRaw('1')
                    ->from('role_permission')
                    ->whereColumn(
                        'role_permission.permission_id',
                        'permissions.id'
                    )
                    ->where(
                        'role_permission.role',
                        $this->role
                    );
            })
            ->exists();
    }


    /*
    |--------------------------------------------------------------------------
    | Account Status
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }
}
