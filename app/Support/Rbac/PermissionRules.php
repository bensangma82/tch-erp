<?php

namespace App\Support\Rbac;

use Illuminate\Validation\ValidationException;

final class PermissionRules
{
    // Existing financial workflows manage these through individual grants.
    public const DIRECT_ONLY = [
        'billing.investigation-refund.process',
        'billing.investigation-refund.verify',
        'billing.investigation-cancellation.execute',
    ];

    public static function validateRoleSelection(array $selectedNames): void
    {
        if (array_intersect(self::DIRECT_ONLY, $selectedNames)) {
            throw ValidationException::withMessages(['permissions' => 'Investigation refund and cancellation execution permissions must be managed on the individual user account.']);
        }
        $hasAction = collect($selectedNames)->contains(fn ($name) => str_starts_with($name, 'administration.') && $name !== 'administration.view');
        if ($hasAction && ! in_array('administration.view', $selectedNames, true)) {
            throw ValidationException::withMessages(['permissions' => 'Administration actions require View Administration. Select View or remove the action permissions.']);
        }
    }

    public static function snapshot(string $role, int $version, array $assignedIds, array $catalogue): string
    {
        $assignedIds = array_map('intval', $assignedIds);
        sort($assignedIds, SORT_NUMERIC);
        ksort($catalogue, SORT_NUMERIC);
        return hash('sha256', json_encode([$role, $version, $assignedIds, $catalogue], JSON_THROW_ON_ERROR));
    }
}
