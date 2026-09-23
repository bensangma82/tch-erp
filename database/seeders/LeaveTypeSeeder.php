<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Seed the initial hospital leave types.
     *
     * Annual entitlement values are intentionally set to 0.
     * Configure actual entitlements later according to
     * Tura Christian Hospital policy.
     */
    public function run(): void
    {
        $leaveTypes = [

            [
                'code' => 'CL',
                'name' => 'Casual Leave',
                'default_annual_entitlement' => 0,
                'is_paid' => true,
                'allow_carry_forward' => false,
                'max_carry_forward' => 0,
                'requires_approval' => true,
                'is_active' => true,
                'sort_order' => 10,
                'description' => 'Short-duration leave for personal or urgent requirements.',
            ],

            [
                'code' => 'EL',
                'name' => 'Earned Leave',
                'default_annual_entitlement' => 0,
                'is_paid' => true,
                'allow_carry_forward' => true,
                'max_carry_forward' => 0,
                'requires_approval' => true,
                'is_active' => true,
                'sort_order' => 20,
                'description' => 'Earned or privilege leave as defined by hospital policy.',
            ],

            [
                'code' => 'SL',
                'name' => 'Sick Leave',
                'default_annual_entitlement' => 0,
                'is_paid' => true,
                'allow_carry_forward' => false,
                'max_carry_forward' => 0,
                'requires_approval' => true,
                'is_active' => true,
                'sort_order' => 30,
                'description' => 'Leave for illness or medical reasons.',
            ],

            [
                'code' => 'ML',
                'name' => 'Maternity Leave',
                'default_annual_entitlement' => 0,
                'is_paid' => true,
                'allow_carry_forward' => false,
                'max_carry_forward' => 0,
                'requires_approval' => true,
                'is_active' => true,
                'sort_order' => 40,
                'description' => 'Maternity leave. Configure according to applicable policy and law.',
            ],

            [
                'code' => 'PL',
                'name' => 'Paternity Leave',
                'default_annual_entitlement' => 0,
                'is_paid' => true,
                'allow_carry_forward' => false,
                'max_carry_forward' => 0,
                'requires_approval' => true,
                'is_active' => true,
                'sort_order' => 50,
                'description' => 'Paternity leave as permitted under hospital policy.',
            ],

            [
                'code' => 'COMP',
                'name' => 'Compensatory Leave',
                'default_annual_entitlement' => 0,
                'is_paid' => true,
                'allow_carry_forward' => false,
                'max_carry_forward' => 0,
                'requires_approval' => true,
                'is_active' => true,
                'sort_order' => 60,
                'description' => 'Compensatory leave granted against approved extra or holiday duty.',
            ],

            [
                'code' => 'STUDY',
                'name' => 'Study / Training Leave',
                'default_annual_entitlement' => 0,
                'is_paid' => true,
                'allow_carry_forward' => false,
                'max_carry_forward' => 0,
                'requires_approval' => true,
                'is_active' => true,
                'sort_order' => 70,
                'description' => 'Leave for study, training, CME, conference or professional development.',
            ],

            [
                'code' => 'LWP',
                'name' => 'Leave Without Pay',
                'default_annual_entitlement' => 0,
                'is_paid' => false,
                'allow_carry_forward' => false,
                'max_carry_forward' => 0,
                'requires_approval' => true,
                'is_active' => true,
                'sort_order' => 80,
                'description' => 'Unpaid leave approved when applicable.',
            ],

        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::updateOrCreate(
                [
                    'code' => $leaveType['code'],
                ],
                $leaveType
            );
        }
    }
}