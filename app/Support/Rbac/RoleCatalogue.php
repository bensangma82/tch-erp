<?php

namespace App\Support\Rbac;

final class RoleCatalogue
{
    public static function userRoles(bool $includeSuperAdmin = false): array
    {
        $roles = self::all();
        return $includeSuperAdmin ? ['super_admin' => 'Super Administrator', ...$roles] : $roles;
    }

    public static function configurableRoles(): array
    {
        return array_diff_key(self::all(), ['admin' => true]);
    }

    private static function all(): array
    {
        return [
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
    }
}
