<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\FinanceHead;
use App\Models\Service;
use Illuminate\Database\Seeder;

class EmergencyServiceSeeder extends Seeder
{
    public function run(): void
    {
        $emergencyDepartmentId = Department::query()
            ->where('name', 'Emergency')
            ->value('id');

        $emergencyFinanceHeadId = FinanceHead::query()
            ->where('code', 'INC-EMERGENCY')
            ->where('head_type', 'income')
            ->where('is_active', true)
            ->value('id');

        Service::updateOrCreate(
            [
                'code' => 'EMG-CONS',
            ],
            [
                'name' => 'Emergency Consultation',
                'category' => 'consultation',
                'department_id' => $emergencyDepartmentId,
                'finance_head_id' => $emergencyFinanceHeadId,
                'price' => 500.00,
                'is_active' => true,
                'requires_sample' => false,
                'requires_report' => false,
                'unit' => null,
                'description' => 'Emergency consultation fee',
            ]
        );
    }
}