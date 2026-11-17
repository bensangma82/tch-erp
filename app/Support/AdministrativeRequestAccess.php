<?php

namespace App\Support;

use App\Models\AdministrativeRequest;
use App\Models\User;

final class AdministrativeRequestAccess
{
    public static function allows(?User $user, string $action, ?AdministrativeRequest $item = null): bool
    {
        if (! $user || ! $user->isActive() || ! $user->hasPermission('administration.view')) {
            return false;
        }

        // Keep the existing ERP admin override, but fail closed for unknown actions.
        $permission = match ($action) {
            'view' => 'administration.view',
            'create', 'submit' => 'administration.create',
            'verify' => 'administration.verify',
            'verify-completion' => 'administration.verify-completion',
            'close' => 'administration.close',
            'approve', 'higher' => 'administration.approve',
            'assign' => 'administration.assign',
            'complete' => 'administration.complete',
            'upload' => null,
            default => false,
        };
        if ($permission === false) {
            return false;
        }
        if ($permission !== null && ! $user->hasPermission($permission)) {
            return false;
        }
        if (in_array($action, ['submit', 'complete', 'verify-completion', 'upload'], true) && ! $item) {
            return false;
        }

        $owner = $item && $item->created_by !== null
            && (string) $item->created_by === (string) $user->getKey();
        $assignee = $item && $item->assigned_to !== null
            && (string) $item->assigned_to === (string) $user->getKey();

        return match ($action) {
            'submit' => $user->isAdmin() || $owner,
            'complete' => $user->isAdmin() || $assignee,
            'verify-completion' => $user->isAdmin() || ($item->executed_by !== null && (string) $item->executed_by !== (string) $user->getKey()),
            'upload' => ! in_array($item->status, AdministrativeRequest::TERMINAL_STATUSES, true)
                && ($user->isAdmin()
                    || ($owner && $user->hasPermission('administration.create'))
                    || $user->hasPermission('administration.verify')
                    || $user->hasPermission('administration.approve')
                    || $user->hasPermission('administration.assign')
                    || $user->hasPermission('administration.verify-completion')
                    || $user->hasPermission('administration.close')
                    || ($assignee && $user->hasPermission('administration.complete'))),
            default => true,
        };
    }
}
